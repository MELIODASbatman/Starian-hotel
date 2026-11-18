<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Get customer ID
$customerId = filter_var(
  $_GET["id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$customerId || $customerId < 1) {
  die("Invalid customer ID.");
}

// Fetch customer details
$customerStmt = $conn->prepare("
    SELECT id, fullname, email, phone, avatar, role, created_at
    FROM users
    WHERE id = ? AND role = 'customer'
");

$customerStmt->bind_param("i", $customerId);
$customerStmt->execute();

$customerResult = $customerStmt->get_result();
$customer = $customerResult->fetch_assoc();

$customerStmt->close();

if (!$customer) {
  die("Customer not found.");
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="View Starian Hotel customer details">
  <title>Customer Details | Starian Hotel Admin</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
</head>

<body>

  <div class="admin-shell">

    <input class="menu-checkbox" type="checkbox" id="admin-menu">

    <aside class="sidebar">

      <a class="brand" href="index.php">
        <img src="../img/starianlogo.png" alt="">
        <span class="brand-name">Starian <span>Hotel</span></span>
      </a>

      <div class="admin-identity">
        <span class="admin-avatar">
          <i class="fa-solid fa-user-shield"></i>
        </span>
        <span>
          <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
          <small>Administrator</small>
        </span>
      </div>

      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a class="active" href="customers.php" aria-current="page">
          <i class="fa-solid fa-users"></i>Customers
        </a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>

      <p class="sidebar-motto">
        Better Service
        <span>Better Stays</span>
      </p>

    </aside>

    <div class="workspace">

      <header class="topbar">

        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu">
          <i class="fa-solid fa-bars"></i>
        </label>

        <span class="topbar-spacer"></span>

        <a class="topbar-icon" href="#" aria-label="Notifications">
          <i class="fa-regular fa-bell"></i>
          <span class="notification-dot">3</span>
        </a>

        <div class="top-admin">
          <span class="admin-avatar">
            <i class="fa-solid fa-user-shield"></i>
          </span>
          <span>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
            <small>Administrator</small>
          </span>
        </div>

        <form class="admin-logout-form" method="post" action="../logout.php">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>

      </header>

      <main class="page-content">

        <div class="page-heading">
          <div>
            <h1>Customer Details</h1>
            <p>View registered customer information.</p>
          </div>

          <div class="heading-actions">
            <a class="button-primary" href="customers.php">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Customers
            </a>
          </div>
        </div>

        <section class="panel">

          <div class="panel-body">

            <div class="customer-profile">

              <div class="person-avatar">
                <?php if (!empty($customer["avatar"])): ?>
                  <img
                    src="../img/avatars/<?php echo htmlspecialchars(basename($customer["avatar"]), ENT_QUOTES, "UTF-8"); ?>"
                    alt="Customer photo">
                <?php else: ?>
                  <i class="fa-solid fa-user"></i>
                <?php endif; ?>
              </div>

              <div>
                <h2>
                  <?php echo htmlspecialchars($customer["fullname"], ENT_QUOTES, "UTF-8"); ?>
                </h2>
                <p>Registered Customer</p>
              </div>

            </div>

            <hr>

            <h3>Personal Information</h3>

            <div class="customer-details-grid">

              <div>
                <strong>Customer ID</strong>
                <p><?php echo (int) $customer["id"]; ?></p>
              </div>

              <div>
                <strong>Full Name</strong>
                <p><?php echo htmlspecialchars($customer["fullname"], ENT_QUOTES, "UTF-8"); ?></p>
              </div>

              <div>
                <strong>Email Address</strong>
                <p><?php echo htmlspecialchars($customer["email"], ENT_QUOTES, "UTF-8"); ?></p>
              </div>

              <div>
                <strong>Phone Number</strong>
                <p><?php echo htmlspecialchars($customer["phone"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?></p>
              </div>

              <div>
                <strong>Member Since</strong>
                <p>
                  <?php echo date("d M Y", strtotime($customer["created_at"])); ?>
                </p>
              </div>

              <div>
                <strong>Account Role</strong>
                <p>Customer</p>
              </div>

            </div>

          </div>

        </section>

      </main>

    </div>

  </div>

</body>

</html>