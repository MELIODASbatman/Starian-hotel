<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Get booking ID from the URL
$bookingId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$bookingId) {
  die("Invalid booking ID.");
}

// Fetch booking details
$stmt = $conn->prepare("
    SELECT
        bookings.id,
        bookings.check_in,
        bookings.check_out,
        bookings.arrival_time,
        bookings.total_nights,
        bookings.total_price,
        bookings.booking_status,
        bookings.payment_status,
        bookings.payment_method,
        bookings.transaction_id,
        bookings.payment_reference,
        bookings.paid_at,
        bookings.created_at,

        users.fullname,
        users.email,
        users.phone,

        rooms.room_name,
        rooms.room_code,
        rooms.price_per_night

    FROM bookings
    INNER JOIN users ON bookings.user_id = users.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    WHERE bookings.id = ?
");

$stmt->bind_param("i", $bookingId);
$stmt->execute();

$booking = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$booking) {
  die("Booking not found.");
}

$bookingStatus = strtolower((string)$booking["booking_status"]);
$paymentStatus = strtolower((string)$booking["payment_status"]);
$bookingBadgeClass = $bookingStatus === "pending"
  ? "warning"
  : ($bookingStatus === "cancelled" ? "danger" : "");
$paymentBadgeClass = in_array($paymentStatus, ["pending", "unpaid"], true)
  ? "warning"
  : (in_array($paymentStatus, ["failed", "cancelled"], true) ? "danger" : "");

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Booking details for Starian Hotel">
  <title>Booking #<?= (int)$booking["id"] ?> | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity">
        <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
        <span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong><small>Administrator</small></span>
      </div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a class="active" href="bookings.php" aria-current="page"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar">
        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label>
        <span class="topbar-spacer"></span>
        <a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin">
          <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong><small>Administrator</small></span>
        </div>
        <form class="admin-logout-form" method="post" action="../logout.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8") ?>">
          <button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button>
        </form>
      </header>
      <main class="page-content">
        <div class="page-heading booking-detail-heading">
          <div>
            <h1>Booking #<?= (int)$booking["id"] ?></h1>
            <p>Review guest, stay, room and payment information.</p>
          </div>
          <a class="button-secondary" href="bookings.php"><i class="fa-solid fa-arrow-left"></i>Back to bookings</a>
        </div>

        <div class="booking-detail-grid">
          <section class="panel booking-detail-panel">
            <div class="booking-detail-title">
              <span class="booking-detail-icon"><i class="fa-solid fa-user"></i></span>
              <h2>Customer Information</h2>
            </div>
            <dl class="booking-info-list">
              <div><dt>Full name</dt><dd><?= htmlspecialchars($booking["fullname"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Email</dt><dd><?= htmlspecialchars($booking["email"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Phone</dt><dd><?= htmlspecialchars($booking["phone"] ?? "Not provided", ENT_QUOTES, "UTF-8") ?></dd></div>
            </dl>
          </section>

          <section class="panel booking-detail-panel">
            <div class="booking-detail-title">
              <span class="booking-detail-icon"><i class="fa-solid fa-bed"></i></span>
              <h2>Room Information</h2>
            </div>
            <dl class="booking-info-list">
              <div><dt>Room</dt><dd><?= htmlspecialchars($booking["room_name"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Room code</dt><dd><?= htmlspecialchars($booking["room_code"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Price per night</dt><dd>KSh <?= number_format((float)$booking["price_per_night"], 2) ?></dd></div>
            </dl>
          </section>

          <section class="panel booking-detail-panel">
            <div class="booking-detail-title">
              <span class="booking-detail-icon"><i class="fa-regular fa-calendar-check"></i></span>
              <h2>Stay Information</h2>
            </div>
            <dl class="booking-info-list">
              <div><dt>Check in</dt><dd><?= htmlspecialchars($booking["check_in"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Check out</dt><dd><?= htmlspecialchars($booking["check_out"], ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Arrival time</dt><dd><?= htmlspecialchars($booking["arrival_time"] ?? "Not specified", ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Total nights</dt><dd><?= (int)$booking["total_nights"] ?></dd></div>
            </dl>
          </section>

          <section class="panel booking-detail-panel">
            <div class="booking-detail-title">
              <span class="booking-detail-icon payment-icon"><i class="fa-solid fa-credit-card"></i></span>
              <h2>Payment Information</h2>
            </div>
            <dl class="booking-info-list">
              <div><dt>Total price</dt><dd class="booking-total">KSh <?= number_format((float)$booking["total_price"], 2) ?></dd></div>
              <div>
                <dt>Booking status</dt>
                <dd><span class="badge <?= $bookingBadgeClass ?>"><?= htmlspecialchars(ucfirst($bookingStatus), ENT_QUOTES, "UTF-8") ?></span></dd>
              </div>
              <div>
                <dt>Payment status</dt>
                <dd><span class="badge <?= $paymentBadgeClass ?>"><?= htmlspecialchars(ucfirst($paymentStatus), ENT_QUOTES, "UTF-8") ?></span></dd>
              </div>
              <div><dt>Payment method</dt><dd><?= htmlspecialchars($booking["payment_method"] ?? "Not provided", ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Transaction ID</dt><dd><?= htmlspecialchars($booking["transaction_id"] ?? "Not available", ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Payment reference</dt><dd><?= htmlspecialchars($booking["payment_reference"] ?? "Not available", ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Paid at</dt><dd><?= htmlspecialchars($booking["paid_at"] ?? "Not paid", ENT_QUOTES, "UTF-8") ?></dd></div>
              <div><dt>Booking created</dt><dd><?= htmlspecialchars($booking["created_at"], ENT_QUOTES, "UTF-8") ?></dd></div>
            </dl>
          </section>
        </div>
      </main>
    </div>
  </div>
</body>

</html>