<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// 1. Total bookings
$sql = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings
");

$totalBookings = $sql->fetch_assoc()["total"];

// 2. Total food orders
$sql = $conn->query("
    SELECT COUNT(*) AS total
    FROM food_orders
");

$totalOrders = $sql->fetch_assoc()["total"];

// 3. Total customers
$sql = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = $sql->fetch_assoc()["total"];

// 4. Total revenue from paid room bookings
$sql = $conn->query("
    SELECT COALESCE(SUM(total_price), 0) AS revenue
    FROM bookings
    WHERE payment_status = 'paid'
");

$totalRevenue = $sql->fetch_assoc()["revenue"];

// 5. Get the five most recent bookings
$sql = $conn->query("
    SELECT
        bookings.id,
        users.fullname,
        rooms.room_name,
        bookings.check_in,
        bookings.booking_status,
        bookings.created_at
    FROM bookings
    INNER JOIN users ON bookings.user_id = users.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    ORDER BY bookings.created_at DESC
    LIMIT 5
");

$recentBookings = [];

if ($sql) {
  while ($row = $sql->fetch_assoc()) {
    $recentBookings[] = $row;
  }
}

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Starian Hotel administration dashboard" />
  <title>Dashboard | Starian Hotel Admin</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="admin.css" />
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu" />
    <aside class="sidebar">
      <a class="brand" href="index.php">
        <img src="../img/starianlogo.png" alt="" />
        <span class="brand-name">Starian <span>Hotel</span></span>
      </a>
      <div class="admin-identity">
        <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
        <span><strong><?php echo htmlspecialchars($adminName); ?></strong><small>Administrator</small></span>
      </div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a class="active" href="index.php" aria-current="page"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar">
        <label
          class="menu-toggle"
          for="admin-menu"
          aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label>
        <span class="topbar-spacer"></span>
        <a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin">
          <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span>
          <i class="fa-solid fa-chevron-down"></i>
        </div>
        <form class="admin-logout-form" method="post" action="../logout.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" />
          <button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button>
        </form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Dashboard Overview</h1>
            <p>
              Welcome back, <?php echo htmlspecialchars($adminName); ?>! Here's what's happening at Starian Hotel
              today.
            </p>
          </div>
          <span class="date-chip"><i class="fa-regular fa-calendar"></i>Tuesday, 14 October
            2025&nbsp; | &nbsp;10:28 AM</span>
        </div>

        <section class="stat-grid" aria-label="Hotel summary">
          <article class="stat-card">
            <span class="stat-icon"><i class="fa-regular fa-calendar-check"></i></span>
            <div>
              <span class="stat-label">Total Bookings</span><strong class="stat-value"> <?= number_format($totalBookings) ?></strong><span class="stat-foot"><i class="fa-solid fa-arrow-trend-up"></i> 12% vs last
                week</span>
            </div>
          </article>
          <article class="stat-card">
            <span class="stat-icon orange"><i class="fa-solid fa-utensils"></i></span>
            <div>
              <span class="stat-label">Total Orders</span><strong class="stat-value"> <?= number_format($totalOrders) ?></strong><span class="stat-foot"><i class="fa-solid fa-arrow-trend-up"></i> 20% vs last
                week</span>
            </div>
          </article>
          <article class="stat-card">
            <span class="stat-icon green"><i class="fa-solid fa-users"></i></span>
            <div>
              <span class="stat-label">Total Customers</span><strong class="stat-value"> <?= number_format($totalCustomers) ?></strong><span class="stat-foot"><i class="fa-solid fa-arrow-trend-up"></i> 8% vs last
                week</span>
            </div>
          </article>
          <article class="stat-card">
            <span class="stat-icon purple"><i class="fa-solid fa-sack-dollar"></i></span>
            <div>
              <span class="stat-label">Total Revenue</span><strong class="stat-value">KSh <?= number_format((float)$totalRevenue, 2) ?></strong><span class="stat-foot"><i class="fa-solid fa-arrow-trend-up"></i> 15% vs last
                week</span>
            </div>
          </article>
        </section>

        <div class="dashboard-grid">
          <div class="dashboard-main-column">
            <section class="panel">
              <div class="panel-heading">
                <h2>Quick Actions</h2>
              </div>
              <div class="quick-actions">
                <a class="quick-action" href="rooms.php"><i class="fa-solid fa-bed"></i><strong>Add Room</strong><small>Add new room</small></a>
                <a class="quick-action" href="menu-orders.php"><i class="fa-solid fa-utensils"></i><strong>Add Menu Item</strong><small>Add food or drink</small></a>
                <a class="quick-action" href="bookings.php"><i class="fa-regular fa-calendar-check"></i><strong>View Bookings</strong><small>Check all bookings</small></a>
                <a class="quick-action" href="menu-orders.php#orders"><i class="fa-solid fa-bag-shopping"></i><strong>View Orders</strong><small>Check all orders</small></a>
                <a class="quick-action" href="customers.php"><i class="fa-solid fa-user-group"></i><strong>Manage Customers</strong><small>View customer list</small></a>
              </div>
            </section>

            <div class="two-column">
              <section class="panel">
                <div class="panel-heading">
                  <h2>System Performance</h2>
                </div>
                <div class="performance-grid">
                  <div class="performance-item">
                    <div class="progress-ring"><span>65%</span></div>
                    <div class="performance-copy">
                      <strong>Room Occupancy</strong>
                      <div class="progress-track">
                        <span style="width: 65%"></span>
                      </div>
                      <small>12 of 25 rooms</small>
                    </div>
                  </div>
                  <div class="performance-item">
                    <div class="progress-ring green-ring">
                      <span>85%</span>
                    </div>
                    <div class="performance-copy">
                      <strong>Food Orders Completion</strong>
                      <div class="progress-track green-track">
                        <span style="width: 85%"></span>
                      </div>
                      <small>18 of 22 orders</small>
                    </div>
                  </div>
                </div>
              </section>
              <section class="panel">
                <div class="panel-heading">
                  <h2>Popular Rooms</h2>
                  <a href="rooms.php">View all <i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <div class="popular-room">
                  <img
                    src="../img/Executive Suite.jpg"
                    alt="Executive room" />
                  <div>
                    <strong>Executive Room</strong>
                    <p>KSh 5,500 / night</p>
                    <a class="small-button" href="rooms.php">View Room</a>
                  </div>
                </div>
              </section>
            </div>
          </div>

          <section class="panel">
            <div class="panel-heading">
              <h2>Recent Bookings</h2>
              <a href="bookings.php">View all</a>
            </div>

            <div class="activity-list">

              <?php if (!empty($recentBookings)): ?>

                <?php foreach ($recentBookings as $booking): ?>

                  <?php
                  $status = strtolower($booking["booking_status"]);

                  $iconClass = ($status === "cancelled")
                    ? "activity-icon warning"
                    : "activity-icon";
                  ?>

                  <article class="activity-item">

                    <span class="<?= $iconClass ?>">
                      <i class="fa-solid fa-circle-check"></i>
                    </span>

                    <div class="activity-copy">

                      <strong>
                        Booking #<?= htmlspecialchars($booking["id"]) ?>
                        — <?= htmlspecialchars(ucfirst($status)) ?>
                      </strong>

                      <small>
                        <?= htmlspecialchars($booking["fullname"]) ?>
                        ·
                        <?= htmlspecialchars($booking["room_name"]) ?>
                        · Check-in:
                        <?= date("d M Y", strtotime($booking["check_in"])) ?>
                        ·
                        <?= date("d M Y", strtotime($booking["created_at"])) ?>
                      </small>

                    </div>

                  </article>

                <?php endforeach; ?>

              <?php else: ?>

                <p>No bookings available yet.</p>

              <?php endif; ?>

            </div>
            </article>
        </div>
        </section>
    </div>
    </main>
  </div>
  </div>
</body>

</html>