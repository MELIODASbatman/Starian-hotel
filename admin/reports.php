<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Get the selected report dates
$startDate = $_GET["start_date"] ?? date("Y-m-d", strtotime("-6 months"));
$endDate = $_GET["end_date"] ?? date("Y-m-d");

// Validate the date format and actual calendar dates
$startDateObj = DateTime::createFromFormat("!Y-m-d", $startDate);
$endDateObj = DateTime::createFromFormat("!Y-m-d", $endDate);

if (
  !$startDateObj ||
  $startDateObj->format("Y-m-d") !== $startDate ||
  !$endDateObj ||
  $endDateObj->format("Y-m-d") !== $endDate ||
  $startDate > $endDate
) {
  exit("Invalid report date range. Please select valid dates.");
}

// Fetch total bookings
$bookingQuery = $conn->prepare("
    SELECT COUNT(*) AS total_bookings
    FROM bookings
    WHERE created_at >= ?
    AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
");

$bookingQuery->bind_param("ss", $startDate, $endDate);
$bookingQuery->execute();
$bookingResult = $bookingQuery->get_result();

if (!$bookingQuery) {
  error_log("Booking statistics error: " . $conn->error);
  exit("Unable to load booking statistics.");
}

$bookingStats = $bookingResult->fetch_assoc();

$totalBookings = (int) $bookingStats["total_bookings"];

// Fetch total food orders
$orderQuery = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM food_orders
    WHERE created_at >= ?
    AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
");

$orderQuery->bind_param("ss", $startDate, $endDate);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();

if (!$orderQuery) {
  error_log("Food order statistics error: " . $conn->error);
  exit("Unable to load food order statistics.");
}

$orderStats = $orderResult->fetch_assoc();

$totalOrders = (int) $orderStats["total_orders"];

// Calculate revenue from paid room bookings and food orders
$revenueQuery = $conn->prepare("
    SELECT
        (
            SELECT COALESCE(SUM(total_price), 0)
            FROM bookings
            WHERE payment_status = 'paid'
            AND created_at >= ?
            AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
        )
        +
        (
            SELECT COALESCE(SUM(total_amount), 0)
            FROM food_orders
            WHERE payment_status = 'paid'
            AND created_at >= ?
            AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
        ) AS total_revenue
");

$revenueQuery->bind_param(
  "ssss",
  $startDate,
  $endDate,
  $startDate,
  $endDate
);

$revenueQuery->execute();
$revenueResult = $revenueQuery->get_result();

if (!$revenueQuery) {
  error_log("Revenue statistics error: " . $conn->error);
  exit("Unable to load revenue statistics.");
}

$revenueStats = $revenueResult->fetch_assoc();

$totalRevenue = (float) $revenueStats["total_revenue"];

// Fetch total number of hotel rooms
$roomCountQuery = $conn->query("
    SELECT COUNT(*) AS total_rooms
    FROM rooms
");

if (!$roomCountQuery) {
  error_log("Room count error: " . $conn->error);
  exit("Unable to load room statistics.");
}

$roomCountStats = $roomCountQuery->fetch_assoc();
$totalRooms = (int) $roomCountStats["total_rooms"];

// Count rooms with paid bookings currently in progress
$occupiedQuery = $conn->query("
    SELECT COUNT(DISTINCT room_id) AS occupied_rooms
    FROM bookings
    WHERE check_in <= CURDATE()
    AND check_out > CURDATE()
    AND payment_status = 'paid'
    AND booking_status != 'cancelled'
");

if (!$occupiedQuery) {
  error_log("Room occupancy error: " . $conn->error);
  exit("Unable to load room occupancy.");
}

$occupiedStats = $occupiedQuery->fetch_assoc();
$occupiedRooms = (int) $occupiedStats["occupied_rooms"];

// Calculate occupancy percentage
$occupancyRate = $totalRooms > 0
  ? round(($occupiedRooms / $totalRooms) * 100)
  : 0;

// Fetch the top five best-selling food items
$topItemsQuery = $conn->prepare("
    SELECT
        fm.food_name,
        fm.image,
        SUM(foi.quantity) AS total_sold
    FROM food_order_items foi
    INNER JOIN food_menu fm ON foi.food_id = fm.id
    INNER JOIN food_orders fo ON foi.order_id = fo.id
    WHERE fo.payment_status = 'paid'
    AND fo.created_at >= ?
    AND fo.created_at < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY fm.id, fm.food_name, fm.image
    ORDER BY total_sold DESC
    LIMIT 5
");

$topItemsQuery->bind_param("ss", $startDate, $endDate);
$topItemsQuery->execute();
$topItemsResult = $topItemsQuery->get_result();

if (!$topItemsQuery) {
  error_log("Top-selling items error: " . $conn->error);
  exit("Unable to load top-selling food items.");
}

$topItems = [];

while ($item = $topItemsResult->fetch_assoc()) {
  $topItems[] = $item;
}

// Fetch monthly booking totals within the selected date range
$bookingTrendsQuery = $conn->prepare("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        COUNT(*) AS total_bookings
    FROM bookings
    WHERE created_at >= ?
    AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY month_key
");

$bookingTrendsQuery->bind_param("ss", $startDate, $endDate);
$bookingTrendsQuery->execute();

$bookingTrendsResult = $bookingTrendsQuery->get_result();

$monthlyBookings = [];

while ($row = $bookingTrendsResult->fetch_assoc()) {
  $monthlyBookings[$row["month_key"]] = (int) $row["total_bookings"];
}

// Fetch monthly scheduled check-ins within the selected date range
$checkinQuery = $conn->prepare("
    SELECT
        DATE_FORMAT(check_in, '%Y-%m') AS month_key,
        COUNT(*) AS total_checkins
    FROM bookings
    WHERE check_in >= ?
    AND check_in < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY month_key
");

$checkinQuery->bind_param("ss", $startDate, $endDate);
$checkinQuery->execute();

$checkinResult = $checkinQuery->get_result();

$monthlyCheckins = [];

while ($row = $checkinResult->fetch_assoc()) {
  $monthlyCheckins[$row["month_key"]] = (int) $row["total_checkins"];
}

// Prepare chart data for the selected date range
$bookingChart = [];

$startMonth = new DateTime($startDate);
$startMonth->modify("first day of this month");

$endMonth = new DateTime($endDate);
$endMonth->modify("first day of this month");

while ($startMonth <= $endMonth) {

  $monthKey = $startMonth->format("Y-m");
  $monthLabel = $startMonth->format("M Y");

  $bookingChart[] = [
    "label" => $monthLabel,
    "total" => $monthlyBookings[$monthKey] ?? 0,
    "checkins" => $monthlyCheckins[$monthKey] ?? 0
  ];

  $startMonth->modify("+1 month");
}

// Find the highest value for chart scaling
$maxChartValue = 0;

foreach ($bookingChart as $month) {
  $maxChartValue = max(
    $maxChartValue,
    $month["total"],
    $month["checkins"]
  );
}

// Find the highest value for chart scaling
$maxChartValue = 0;

foreach ($bookingChart as $month) {
  $maxChartValue = max(
    $maxChartValue,
    $month["total"],
    $month["checkins"]
  );
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Starian Hotel reports and analytics">
  <title>Reports | Starian Hotel Admin</title>
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
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a><a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a><a class="active" href="reports.php" aria-current="page"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><?php require __DIR__ . "/includes/notification-widget.php"; ?>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="print-report-header">
          <h2>Starian Hotel</h2>
          <h3>Reports &amp; Analytics</h3>

          <p>
            Reporting Period:
            <?php echo date("d M Y", strtotime($startDate)); ?>
            -
            <?php echo date("d M Y", strtotime($endDate)); ?>
          </p>

          <p>
            Generated on: <?php echo date("d M Y, h:i A"); ?>
          </p>
        </div>
        <div class="page-heading">
          <div>
            <h1>Reports &amp; Analytics</h1>
            <p>View hotel performance and key metrics at a glance.</p>
          </div>
          <div class="heading-actions">

            <form method="GET" action="reports.php" class="date-filter-form">

              <label class="date-field">
                <span>From</span>
                <input type="date" name="start_date"
                  value="<?php echo htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months')), ENT_QUOTES, 'UTF-8'); ?>"
                  max="<?php echo htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>">
              </label>

              <label class="date-field">
                <span>To</span>
                <input type="date" name="end_date"
                  value="<?php echo htmlspecialchars($_GET['end_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                  min="<?php echo htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
              </label>

              <button type="submit" class="button-primary">
                Apply Filter
              </button>
              <a href="reports.php" class="button-primary">Clear</a>

            </form>

            <button type="button" class="button-primary" onclick="window.print()">
              <i class="fa-solid fa-download"></i>
              Export / Print Report
            </button>

          </div>
        </div>
        <section class="report-stats" aria-label="Monthly summary">
          <article class="stat-card"><span class="stat-icon"><i class="fa-regular fa-calendar-check"></i></span>
            <div><span class="stat-label">Total Bookings</span>
              <strong class="stat-value">
                <?php echo number_format($totalBookings); ?>
              </strong><span class="stat-foot">In selected date range</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon orange"><i class="fa-solid fa-utensils"></i></span>
            <div><span class="stat-label">Total Orders</span>
              <strong class="stat-value">
                <?php echo number_format($totalOrders); ?>
              </strong><span class="stat-foot">In selected date range</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></span>
            <div><span class="stat-label">Revenue (KSh)</span>
              <strong class="stat-value">
                <?php echo number_format($totalRevenue, 2); ?>
              </strong><span class="stat-foot">In selected date range</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon purple"><i class="fa-solid fa-bed"></i></span>
            <div><span class="stat-label">Room Occupancy</span>
              <strong class="stat-value">
                <?php echo $occupancyRate; ?>%
              </strong>
              <span class="stat-foot">
                <?php echo $occupiedRooms; ?> of <?php echo $totalRooms; ?> rooms occupied today
              </span>
            </div>
          </article>
        </section>
        <div class="analytics-grid">
          <section class="panel">
            <div class="panel-heading">
              <h2>Top Selling Menu Items</h2><small>Paid orders in selected date range</small>
            </div>
            <div class="selling-list">

              <?php if (!empty($topItems)): ?>

                <?php
                $maxSold = (int) $topItems[0]["total_sold"];
                ?>

                <?php foreach ($topItems as $index => $item): ?>

                  <?php
                  $image = !empty($item["image"])
                    ? "../" . ltrim($item["image"], "/")
                    : "../img/food-placeholder.jpg";

                  $percentage = $maxSold > 0
                    ? round(((int) $item["total_sold"] / $maxSold) * 100)
                    : 0;
                  ?>

                  <div class="selling-item">

                    <strong><?php echo $index + 1; ?></strong>

                    <img
                      src="<?php echo htmlspecialchars($image, ENT_QUOTES, "UTF-8"); ?>"
                      alt="<?php echo htmlspecialchars($item["food_name"], ENT_QUOTES, "UTF-8"); ?>">

                    <span>
                      <?php echo htmlspecialchars($item["food_name"], ENT_QUOTES, "UTF-8"); ?>
                    </span>

                    <span>
                      <?php echo number_format((int) $item["total_sold"]); ?> sold
                    </span>

                    <div class="selling-bar">
                      <span style="width: <?php echo $percentage; ?>%"></span>
                    </div>

                  </div>

                <?php endforeach; ?>

              <?php else: ?>

                <p>No paid food orders available yet.</p>

              <?php endif; ?>

            </div>
          </section>
          <section class="panel">
            <div class="panel-heading">
              <h2>Room Occupancy Rate</h2>
            </div>
            <div class="donut-chart"
              data-rate="<?php echo $occupancyRate; ?>%"
              style="--occupancy-rate: <?php echo $occupancyRate; ?>%;"
              role="img"
              aria-label="Room occupancy <?php echo $occupancyRate; ?> percent">
            </div>
            <div class="legend">
              <span><i></i>Occupied</span>
              <span><i class="available-dot"></i>Available</span>
            </div>
          </section>
          <section class="panel">
            <div class="panel-heading">
              <h2>Booking Trends</h2><small>Monthly overview</small>
            </div>
            <div class="bar-chart" role="img"
              aria-label="Bookings and scheduled check-ins over the last seven months">

              <?php foreach ($bookingChart as $month): ?>

                <div class="bar-group">

                  <i class="bar"
                    style="height: <?php echo $maxChartValue > 0
                                      ? round(($month["total"] / $maxChartValue) * 100)
                                      : 0; ?>%">
                  </i>

                  <i class="bar secondary"
                    style="height: <?php echo $maxChartValue > 0
                                      ? round(($month["checkins"] / $maxChartValue) * 100)
                                      : 0; ?>%">
                  </i>

                  <span class="bar-label">
                    <?php echo htmlspecialchars($month["label"]); ?>
                  </span>

                </div>

              <?php endforeach; ?>

            </div>
            <div class="legend"><span><i class="yellow-dot"></i>Bookings</span><span><i style="background:#1687e8"></i>Check-ins</span></div>
          </section>
        </div>
      </main>
    </div>
  </div>
</body>

</html>