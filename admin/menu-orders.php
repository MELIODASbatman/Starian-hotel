<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// Get the logged-in admin's name
$adminName = $_SESSION["fullname"] ?? "Administrator";
$menuCategories = require __DIR__ . "/../php/menu_categories.php";

// Generate a CSRF token for menu deletion
if (empty($_SESSION["menu_delete_csrf_token"])) {
  $_SESSION["menu_delete_csrf_token"] = bin2hex(random_bytes(32));
}

// Display menu deletion feedback
$deleteMessage = "";
$deleteMessageType = "";

$deleteStatus = $_GET["delete_status"] ?? "";

switch ($deleteStatus) {
  case "deleted":
    $deleteMessage = "Menu item deleted successfully.";
    $deleteMessageType = "success";
    break;

  case "unavailable":
    $deleteMessage = "This item has previous orders, so it was marked unavailable instead.";
    $deleteMessageType = "success";
    break;

  case "not_found":
    $deleteMessage = "The selected menu item was not found.";
    $deleteMessageType = "error";
    break;

  case "invalid":
    $deleteMessage = "Invalid menu item selection.";
    $deleteMessageType = "error";
    break;

  case "error":
    $deleteMessage = "Something went wrong. The menu item could not be deleted.";
    $deleteMessageType = "error";
    break;
}

// Fetch menu items from the database
$menuItemsResult = $conn->query("
    SELECT id, food_name, category, price, image, is_available
    FROM food_menu
    ORDER BY id DESC
");

if (!$menuItemsResult) {
  die("Error fetching menu items: " . $conn->error);
}

// Fetch the five most recent food orders
$recentOrdersResult = $conn->query("
    SELECT
        fo.id,
        fo.total_amount,
        fo.order_status,
        fo.payment_status,
        fo.created_at,
        u.fullname,
        COALESCE(SUM(foi.quantity), 0) AS total_items
    FROM food_orders fo
    LEFT JOIN users u
        ON fo.user_id = u.id
    LEFT JOIN food_order_items foi
        ON fo.id = foi.order_id
    GROUP BY
        fo.id,
        fo.total_amount,
        fo.order_status,
        fo.payment_status,
        fo.created_at,
        u.fullname
    ORDER BY fo.created_at DESC, fo.id DESC
    LIMIT 5
");

if (!$recentOrdersResult) {
  die("Error fetching recent orders: " . $conn->error);
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage the hotel menu and food orders">
  <title>Menu &amp; Orders | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__ . "/admin.css"); ?>">
  <script src="../javascript/admin-search.js?v=20261118-menu-category-filter" defer></script>
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a class="active" href="menu-orders.php" aria-current="page"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><?php require __DIR__ . "/includes/notification-widget.php"; ?>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Food Menu &amp; Orders</h1>
            <p>Manage menu items and customer orders.</p>
          </div>
          <div class="heading-actions">
            <form class="search-box" role="search" data-table-search="menu-items-table">
              <button type="submit" aria-label="Search menu items"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
              <input type="search" name="search" placeholder="Search menu items..." aria-label="Search menu items">
            </form>
            <a href="add-menu-item.php" class="button-primary">
              <i class="fas fa-plus"></i>
              Add Menu Item
            </a>
          </div>
        </div>

        <?php if ($deleteMessage !== ""): ?>
          <div class="form-message <?php echo htmlspecialchars($deleteMessageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($deleteMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <nav class="filter-row" aria-label="Filter menu items" data-category-filter-list="menu-items-table">
          <button type="button" class="filter-pill active" data-category-filter="all" aria-pressed="true">All</button>
          <?php foreach ($menuCategories as $category): ?>
            <button type="button" class="filter-pill" data-category-filter="<?= htmlspecialchars(strtolower($category), ENT_QUOTES, "UTF-8") ?>" aria-pressed="false">
              <?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>
            </button>
          <?php endforeach; ?>
        </nav>

        <div class="menu-layout">
          <section class="panel">
            <nav class="tab-row" aria-label="Menu sections"><a class="tab active" href="#menu-items">Menu Items</a></nav>
            <div class="table-wrap" id="menu-items">
              <table class="data-table" id="menu-items-table">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price (KSh)</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($item = $menuItemsResult->fetch_assoc()): ?>

                    <tr data-search-row data-category="<?= htmlspecialchars(strtolower((string) $item["category"]), ENT_QUOTES, "UTF-8") ?>">
                      <td>
                        <div class="food-item-cell">
                          <img
                            src="../img/<?php echo htmlspecialchars(basename($item['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($item['food_name'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="food-thumbnail">

                          <span>
                            <?php echo htmlspecialchars($item['food_name'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        </div>
                      </td>

                      <td>
                        <?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?>
                      </td>

                      <td>
                        KSh <?php echo number_format((float) $item['price'], 2); ?>
                      </td>

                      <td>
                        <?php if ((int) $item['is_available'] === 1): ?>
                          <span class="badge">Available</span>
                        <?php else: ?>
                          <span class="badge">Unavailable</span>
                        <?php endif; ?>
                      </td>

                      <td class="actions">
                        <a class="icon-button"
                          href="edit-menu-item.php?id=<?php echo (int) $item['id']; ?>"
                          title="Edit"
                          aria-label="Edit <?php echo htmlspecialchars($item['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                          <i class="fas fa-edit"></i>
                        </a>

                        <form
                          method="POST"
                          action="delete-menu-item.php"
                          onsubmit="return confirm('Are you sure you want to delete this menu item?');"
                          style="display: inline;">
                          <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int) $item['id']; ?>">

                          <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars(
                                      $_SESSION['menu_delete_csrf_token'],
                                      ENT_QUOTES,
                                      'UTF-8'
                                    ); ?>">

                          <button
                            type="submit"
                            class="icon-button danger-action"
                            title="Delete"
                            aria-label="Delete <?php echo htmlspecialchars($item['food_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>

                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </section>
          <section class="panel" id="orders">
            <div class="panel-heading">
              <h2>Recent Orders</h2><a href="orders.php">View all <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <div class="mini-orders">

              <?php if ($recentOrdersResult->num_rows > 0): ?>

                <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>

                  <div class="mini-order">

                    <strong>
                      <i class="fa-solid fa-user"></i>
                      <?php
                      echo htmlspecialchars(
                        $order["fullname"] ?? "Unknown Customer",
                        ENT_QUOTES,
                        "UTF-8"
                      );
                      ?>
                    </strong>

                    <span>
                      <?php
                      $itemCount = (int) $order["total_items"];
                      echo $itemCount . ($itemCount === 1 ? " item" : " items");
                      ?>
                    </span>

                    <strong>
                      KSh <?php echo number_format((float) $order["total_amount"], 2); ?>
                    </strong>

                    <span class="badge">
                      <?php
                      echo htmlspecialchars(
                        ucfirst(str_replace("_", " ", $order["order_status"])),
                        ENT_QUOTES,
                        "UTF-8"
                      );
                      ?>
                    </span>

                    <small>
                      Payment:
                      <?php
                      echo htmlspecialchars(
                        ucfirst(str_replace("_", " ", $order["payment_status"])),
                        ENT_QUOTES,
                        "UTF-8"
                      );
                      ?>
                    </small>

                  </div>

                <?php endwhile; ?>

              <?php else: ?>

                <p>No food orders have been placed yet.</p>

              <?php endif; ?>

            </div>

          </section>
        </div>
      </main>
    </div>
  </div>
</body>

</html>