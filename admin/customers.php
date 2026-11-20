<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";
require_once __DIR__ . "/../php/avatar_helpers.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Generate CSRF token for customer status changes
if (empty($_SESSION["customer_status_csrf_token"])) {
  $_SESSION["customer_status_csrf_token"] = bin2hex(random_bytes(32));
}

// Customer search
$search = trim($_GET["search"] ?? "");

// Fetch registered customers
$sql = "
    SELECT id, fullname, email, phone, avatar, created_at, status
FROM users
    WHERE role = 'customer'
";

if ($search !== "") {
  $sql .= "
        AND (
            fullname LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
  die("Error preparing customer query: " . $conn->error);
}

if ($search !== "") {
  $searchTerm = "%" . $search . "%";
  $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$customersResult = $stmt->get_result();
$stmt->close();
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel customers">
  <title>Customers | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a class="active" href="customers.php" aria-current="page"><i class="fa-solid fa-users"></i>Customers</a><a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a><a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
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
            <h1>Manage Customers</h1>
            <p>View and manage all registered customers.</p>
          </div>
          <div class="heading-actions">
            <form class="search-box" method="GET" action="customers.php">
              <i class="fa-solid fa-magnifying-glass"></i>

              <input
                type="search"
                name="search"
                placeholder="Search customers..."
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>">
            </form>
            <a class="button-primary" href="#"><i class="fa-solid fa-plus"></i>Add Customer</a>
          </div>
        </div>
        <section class="panel">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Member Since</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($customersResult->num_rows > 0): ?>

                  <?php while ($customer = $customersResult->fetch_assoc()): ?>
                    <?php $customerAvatar = getAvatarFilename($customer["avatar"] ?? null); ?>

                    <tr>
                      <td>
                        <span class="person-avatar">
                          <img
                            src="../img/avatars/<?php echo htmlspecialchars($customerAvatar, ENT_QUOTES, "UTF-8"); ?>"
                            alt="<?php echo htmlspecialchars($customer["fullname"], ENT_QUOTES, "UTF-8"); ?> avatar">
                        </span>
                      </td>

                      <td>
                        <strong>
                          <?php echo htmlspecialchars($customer["fullname"], ENT_QUOTES, "UTF-8"); ?>
                        </strong>
                      </td>

                      <td>
                        <?php echo htmlspecialchars($customer["email"], ENT_QUOTES, "UTF-8"); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars($customer["phone"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?>
                      </td>

                      <td>
                        <?php echo date("d M Y", strtotime($customer["created_at"])); ?>
                      </td>

                      <td>
                        <?php if ($customer["status"] === "active"): ?>
                          <span class="badge">Active</span>
                        <?php else: ?>
                          <span class="badge">Inactive</span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <div class="actions">
                          <a
                            class="icon-button"
                            href="view-customer.php?id=<?php echo (int) $customer['id']; ?>"
                            title="View customer"
                            aria-label="View customer">
                            <i class="fa-regular fa-eye"></i>
                          </a>

                          <a
                            class="icon-button"
                            href="edit-customer.php?id=<?php echo (int) $customer['id']; ?>"
                            title="Edit customer"
                            aria-label="Edit customer">
                            <i class="fa-solid fa-pen"></i>
                          </a>

                          <form
                            method="POST"
                            action="update-customer-status.php"
                            onsubmit="return confirm('<?php echo $customer['status'] === 'active' ? 'Are you sure you want to deactivate this customer?' : 'Are you sure you want to reactivate this customer?'; ?>');">
                            <input
                              type="hidden"
                              name="customer_id"
                              value="<?php echo (int) $customer['id']; ?>">

                            <input
                              type="hidden"
                              name="csrf_token"
                              value="<?php echo htmlspecialchars($_SESSION['customer_status_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                            <input
                              type="hidden"
                              name="status"
                              value="<?php echo $customer['status'] === 'active' ? 'inactive' : 'active'; ?>">

                            <?php if ($customer['status'] === 'active'): ?>

                              <button
                                class="icon-button danger-action"
                                type="submit"
                                title="Deactivate customer"
                                aria-label="Deactivate customer">
                                <i class="fa-solid fa-user-slash"></i>
                              </button>

                            <?php else: ?>

                              <button
                                class="icon-button"
                                type="submit"
                                title="Reactivate customer"
                                aria-label="Reactivate customer">
                                <i class="fa-solid fa-user-check"></i>
                              </button>

                            <?php endif; ?>
                          </form>
                        </div>
                      </td>
                    </tr>

                  <?php endwhile; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="7">No registered customers found.</td>
                  </tr>

                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="table-footer">
            <span>
              Showing <?php echo $customersResult->num_rows; ?> customers
            </span>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>