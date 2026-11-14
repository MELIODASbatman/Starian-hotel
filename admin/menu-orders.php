<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// Get the logged-in admin's name
$adminName = $_SESSION["fullname"] ?? "Administrator";

// Fetch menu items from the database
$menuItemsResult = $conn->query("
    SELECT id, food_name, category, price, image, is_available
    FROM food_menu
    ORDER BY id DESC
");

if (!$menuItemsResult) {
  die("Error fetching menu items: " . $conn->error);
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
  <link rel="stylesheet" href="admin.css">
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
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Food Menu &amp; Orders</h1>
            <p>Manage menu items and customer orders.</p>
          </div>
          <div class="heading-actions"><label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Search menu items..."></label>
            <a href="add-menu-item.php" class="button-primary">
              <i class="fas fa-plus"></i>
              Add Menu Item
            </a>
          </div>
        </div>
        <div class="menu-layout">
          <section class="panel">
            <nav class="tab-row" aria-label="Menu sections"><a class="tab active" href="#menu-items">Menu Items</a><a class="tab" href="#orders">Orders</a></nav>
            <div class="table-wrap" id="menu-items">
              <table class="data-table">
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

                    <tr>
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
                        <button type="button" class="icon-button" title="Edit">
                          <i class="fas fa-edit"></i>
                        </button>

                        <button type="button" class="icon-button danger-action" title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>

                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </section>
          <section class="panel" id="orders">
            <div class="panel-heading">
              <h2>Recent Orders</h2><a href="#">View all <i class="fa-solid fa-chevron-right"></i></a>
            </div>
            <div class="mini-orders">
              <div class="mini-order"><strong><i class="fa-solid fa-user"></i> Mary Atieno</strong><span>2 items</span><strong>KSh 1,200</strong><span class="badge warning">Preparing</span></div>
              <div class="mini-order"><strong><i class="fa-solid fa-user"></i> Peter Njuguna</strong><span>3 items</span><strong>KSh 1,560</strong><span class="badge warning">Preparing</span></div>
              <div class="mini-order"><strong><i class="fa-solid fa-user"></i> Faith Wanjiku</strong><span>1 item</span><strong>KSh 850</strong><span class="badge">Delivered</span></div>
              <div class="mini-order"><strong><i class="fa-solid fa-user"></i> Samuel Kamau</strong><span>2 items</span><strong>KSh 1,950</strong><span class="badge">Delivered</span></div>
              <div class="mini-order"><strong><i class="fa-solid fa-user"></i> Esther Achieng</strong><span>1 item</span><strong>KSh 450</strong><span class="badge">Delivered</span></div>
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</body>

</html>