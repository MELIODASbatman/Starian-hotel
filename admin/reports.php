<?php
require_once __DIR__ . "/includes/auth.php";
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Starian Hotel reports and analytics">
  <title>Reports | Starian Hotel Admin</title>
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
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a><a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a><a class="active" href="reports.php" aria-current="page"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div><form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Reports &amp; Analytics</h1>
            <p>View hotel performance and key metrics at a glance.</p>
          </div>
          <div class="heading-actions"><span class="date-chip"><i class="fa-regular fa-calendar"></i>01 Oct 2025 – 31 Oct 2025</span><a class="button-primary" href="#"><i class="fa-solid fa-download"></i>Export Report</a></div>
        </div>
        <section class="report-stats" aria-label="Monthly summary">
          <article class="stat-card"><span class="stat-icon"><i class="fa-regular fa-calendar-check"></i></span>
            <div><span class="stat-label">Total Bookings</span><strong class="stat-value">125</strong><span class="stat-foot">↗ 12% vs last month</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon orange"><i class="fa-solid fa-utensils"></i></span>
            <div><span class="stat-label">Total Orders</span><strong class="stat-value">96</strong><span class="stat-foot">↗ 8% vs last month</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></span>
            <div><span class="stat-label">Revenue (KSh)</span><strong class="stat-value">245,600</strong><span class="stat-foot">↗ 15% vs last month</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon purple"><i class="fa-solid fa-bed"></i></span>
            <div><span class="stat-label">Room Occupancy</span><strong class="stat-value">83%</strong><span class="stat-foot">↗ 6% vs last month</span></div>
          </article>
        </section>
        <div class="analytics-grid">
          <section class="panel">
            <div class="panel-heading">
              <h2>Top Selling Menu Items</h2><small>This month</small>
            </div>
            <div class="selling-list">
              <div class="selling-item"><strong>1</strong><img src="../img/grilled%20chicken.jpg" alt=""><span>Grilled Chicken</span><span>24 orders</span>
                <div class="selling-bar"><span style="width:92%"></span></div>
              </div>
              <div class="selling-item"><strong>2</strong><img src="../img/burger.jpg" alt=""><span>Beef Burger</span><span>20 orders</span>
                <div class="selling-bar"><span style="width:78%"></span></div>
              </div>
              <div class="selling-item"><strong>3</strong><img src="../img/pasta.jpg" alt=""><span>Vegetable Pasta</span><span>16 orders</span>
                <div class="selling-bar"><span style="width:63%"></span></div>
              </div>
              <div class="selling-item"><strong>4</strong><img src="../img/fish.jpg" alt=""><span>Fish and Chips</span><span>12 orders</span>
                <div class="selling-bar"><span style="width:48%"></span></div>
              </div>
              <div class="selling-item"><strong>5</strong><img src="../img/chicken%20Caesar%20salad.jpg" alt=""><span>Chicken Caesar Salad</span><span>10 orders</span>
                <div class="selling-bar"><span style="width:38%"></span></div>
              </div>
            </div>
          </section>
          <section class="panel">
            <div class="panel-heading">
              <h2>Room Occupancy Rate</h2>
            </div>
            <div class="donut-chart" role="img" aria-label="Room occupancy 68 percent"></div>
            <div class="legend"><span><i></i>Occupied</span><span><i class="yellow-dot"></i>Available</span><span><i class="gray-dot"></i>Out of Service</span></div>
          </section>
          <section class="panel">
            <div class="panel-heading">
              <h2>Booking Trends</h2><small>Monthly overview</small>
            </div>
            <div class="bar-chart" role="img" aria-label="Booking and check-in trends over six months">
              <div class="bar-group"><i class="bar" style="height:42%"></i><i class="bar secondary" style="height:25%"></i><span class="bar-label">Sep</span></div>
              <div class="bar-group"><i class="bar" style="height:60%"></i><i class="bar secondary" style="height:37%"></i><span class="bar-label">Oct</span></div>
              <div class="bar-group"><i class="bar" style="height:48%"></i><i class="bar secondary" style="height:32%"></i><span class="bar-label">Nov</span></div>
              <div class="bar-group"><i class="bar" style="height:75%"></i><i class="bar secondary" style="height:48%"></i><span class="bar-label">Dec</span></div>
              <div class="bar-group"><i class="bar" style="height:57%"></i><i class="bar secondary" style="height:39%"></i><span class="bar-label">Jan</span></div>
              <div class="bar-group"><i class="bar" style="height:88%"></i><i class="bar secondary" style="height:64%"></i><span class="bar-label">Feb</span></div>
              <div class="bar-group"><i class="bar" style="height:68%"></i><i class="bar secondary" style="height:50%"></i><span class="bar-label">Mar</span></div>
            </div>
            <div class="legend"><span><i class="yellow-dot"></i>Bookings</span><span><i style="background:#1687e8"></i>Check-ins</span></div>
          </section>
        </div>
      </main>
    </div>
  </div>
</body>

</html>