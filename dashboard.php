<?php

session_start();

$isLoggedIn = isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);

// Check whether the user is logged in
if (!$isLoggedIn) {
  header("Location: login.php");
  exit;
}

// Get the logged-in user's information
$fullname = $_SESSION["fullname"] ?? "";
$email = $_SESSION["email"] ?? "";

require_once "php/db.php";

$userId = (int) $_SESSION["user_id"];

$bookingStatement = $conn->prepare(
  "SELECT
      bookings.id,
      bookings.check_in,
      bookings.check_out,
      bookings.total_nights,
      bookings.total_price,
      bookings.booking_status,
      rooms.room_name,
      rooms.room_code
   FROM bookings
   INNER JOIN rooms ON rooms.id = bookings.room_id
   WHERE bookings.user_id = ?
   ORDER BY bookings.created_at DESC, bookings.id DESC
   LIMIT 1"
);

if (!$bookingStatement) {
  error_log("Unable to prepare dashboard booking query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$bookingStatement->bind_param("i", $userId);

if (!$bookingStatement->execute()) {
  error_log("Unable to load dashboard booking: " . $bookingStatement->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$recentBooking = $bookingStatement->get_result()->fetch_assoc();
$bookingStatement->close();

$orderStatement = $conn->prepare(
  "SELECT
      recent_orders.id AS order_id,
      recent_orders.created_at,
      recent_orders.total_amount,
      recent_orders.order_status,
      food_order_items.id AS item_id,
      food_order_items.quantity,
      food_menu.food_name,
      food_menu.image
   FROM (
      SELECT id, created_at, total_amount, order_status
      FROM food_orders
      WHERE user_id = ?
      ORDER BY created_at DESC, id DESC
      LIMIT 2
   ) AS recent_orders
   LEFT JOIN food_order_items
      ON food_order_items.order_id = recent_orders.id
   LEFT JOIN food_menu
      ON food_menu.id = food_order_items.food_id
   ORDER BY recent_orders.created_at DESC, recent_orders.id DESC, food_order_items.id ASC"
);

if (!$orderStatement) {
  error_log("Unable to prepare dashboard orders query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$orderStatement->bind_param("i", $userId);

if (!$orderStatement->execute()) {
  error_log("Unable to load dashboard orders: " . $orderStatement->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$orderResult = $orderStatement->get_result();
$recentOrders = [];

while ($row = $orderResult->fetch_assoc()) {
  $orderId = (int) $row["order_id"];

  if (!isset($recentOrders[$orderId])) {
    $recentOrders[$orderId] = [
      "id" => $orderId,
      "created_at" => $row["created_at"],
      "total_amount" => $row["total_amount"],
      "status" => strtolower((string) $row["order_status"]),
      "items" => []
    ];
  }

  if ($row["item_id"] !== null) {
    $recentOrders[$orderId]["items"][] = [
      "name" => $row["food_name"] ?? "Menu item",
      "quantity" => (int) $row["quantity"],
      "image" => $row["image"] ?? ""
    ];
  }
}

$orderStatement->close();

$summaryStatement = $conn->prepare(
  "SELECT
      (SELECT COUNT(*)
       FROM bookings
       WHERE user_id = ? AND check_in >= CURDATE() AND booking_status != 'cancelled') AS upcoming_bookings,
      (SELECT COUNT(*)
       FROM food_orders
       WHERE user_id = ?) AS total_orders"
);

if (!$summaryStatement) {
  error_log("Unable to prepare dashboard summary query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$summaryStatement->bind_param("ii", $userId, $userId);

if (!$summaryStatement->execute()) {
  error_log("Unable to load dashboard summary: " . $summaryStatement->error);
  http_response_code(500);
  exit("Unable to load your dashboard right now.");
}

$summary = $summaryStatement->get_result()->fetch_assoc();
$summaryStatement->close();
$conn->close();

$roomImages = [
  "standard-1" => "img/bed.jpg",
  "standard-2" => "img/sbed2.jpg",
  "standard-3" => "img/sbed3.jpg",
  "standard-4" => "img/sbed4.jpg",
  "deluxe-1" => "img/dbed.jpg",
  "deluxe-2" => "img/dbed2.jpg",
  "deluxe-3" => "img/dbed3.jpg",
  "deluxe-4" => "img/dbed4.jpg",
  "executive-1" => "img/ebed.jpg",
  "executive-2" => "img/ebed2.jpg",
  "executive-3" => "img/ebed3.jpg",
  "executive-4" => "img/ebed4.jpg",
  "family-1" => "img/fbed.jpg",
  "family-2" => "img/fbed2.jpg",
  "family-3" => "img/fbed3.png",
  "family-4" => "img/fbed4.jpg"
];

$orderStatusLabels = [
  "pending" => "Pending",
  "confirmed" => "Confirmed",
  "preparing" => "Preparing",
  "completed" => "Delivered",
  "cancelled" => "Cancelled"
];

$orderStatusClasses = [
  "pending" => "preparing",
  "confirmed" => "confirmed",
  "preparing" => "preparing",
  "completed" => "delivered",
  "cancelled" => "cancelled"
];

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=2" />
  <link rel="stylesheet" href="css/dashboard.css?v=2" />
</head>

<body>
  <section class="navi">
    <img src="img/starianlogo.png" alt="Starian Hotel logo" />
    <h1>Starian <span>Hotel</span></h1>
    <button
      class="nav-toggle"
      type="button"
      aria-label="Open navigation menu"
      aria-expanded="false"
      aria-controls="main-navigation">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav class="nav-links" id="main-navigation">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="rooms.php">Rooms</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>

        <?php if ($isLoggedIn): ?>
          <li>
            <a href="dashboard.php" class="account-link">
              <img src="img/user.png" alt="" class="nav-avatar" />
              <span><?php echo htmlspecialchars($_SESSION["fullname"] ?? "My Account"); ?></span>
            </a>
          </li>

        <?php else: ?>
          <li><a class="loginup" href="login.php">Login</a></li>
          <li><a class="signup" href="signup.php">Sign Up</a></li>
        <?php endif; ?>

      </ul>
    </nav>
  </section>

  <!-- dashboard body -->
  <section class="dashboard-layout">
    <!-- left sidebody -->
    <aside class="sidebar">
      <div class="customer-profile">
        <div class="profile-avatar">
          <i class="fa-solid fa-user"></i>
        </div>
        <h3><?php echo htmlspecialchars($fullname); ?></h3>
        <p>customer</p>
      </div>
      <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-link active" aria-current="page">
          <i class="fa-solid fa-house"></i>
          <span>Dashboard</span>
        </a>

        <a href="bookings.php" class="sidebar-link">
          <i class="fa-regular fa-calendar-check"></i>
          <span>My Bookings</span>
        </a>

        <a href="orders.php" class="sidebar-link">
          <i class="fa-solid fa-utensils"></i>
          <span>My Orders</span>
        </a>

        <a href="profile.php" class="sidebar-link">
          <i class="fa-solid fa-user"></i>
          <span>My Profile</span>
        </a>
      </nav>

      <div class="sidebar-promo">
        <p>Good Food</p>
        <p>Great Stays</p>
        <p class="promo-highlight">Better Moments</p>
      </div>
    </aside>

    <!-- main info -->
    <main class="main-info">
      <!-- welcome banner -->
      <section class="welcome-banner">
        <div class="welcome-text">
          <h1>Welcome Back,<br /><span><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?>!</span></h1>
          <p>Your comfort, our priority. Enjoy your stay with us.</p>
        </div>
      </section>

      <!-- summary card -->
      <section class="summary-cards">
        <article class="summary-card">
          <div class="summary-icon">
            <i class="fa-solid fa-bed"></i>
          </div>

          <h4>Upcoming Bookings</h4>
          <p class="summary-value"><?php echo (int) $summary["upcoming_bookings"]; ?></p>

          <a href="bookings.php" class="card-btn">View Bookings</a>
        </article>

        <article class="summary-card">
          <div class="summary-icon">
            <i class="fa-solid fa-utensils"></i>
          </div>

          <h4>Recent Orders</h4>
          <p class="summary-value"><?php echo (int) $summary["total_orders"]; ?></p>

          <a href="orders.php" class="card-btn">View Orders</a>
        </article>

        <article class="summary-card">
          <div class="summary-icon">
            <i class="fa-solid fa-user"></i>
          </div>

          <h4>Profile Status</h4>
          <p class="summary-value">Complete</p>

          <a href="profile.php" class="card-btn">View Profile</a>
        </article>

        <article class="summary-card">
          <div class="summary-icon">
            <i class="fa-solid fa-star"></i>
          </div>

          <h4>Member Since</h4>
          <p class="summary-value">Oct 2025</p>

          <a href="#" class="card-btn">View Details</a>
        </article>
      </section>

      <!-- recent activities -->
      <section class="activity-section">
        <article class="activity-card">
          <div class="activity-heading">
            <h2>Recent Booking</h2>
            <a href="bookings.php">View All <i class="fa-solid fa-arrow-right"></i></a>
          </div>
          <?php if ($recentBooking): ?>
            <?php
            $bookingStatus = strtolower((string) $recentBooking["booking_status"]);
            $bookingStatusClass = in_array($bookingStatus, ["pending", "confirmed", "cancelled"], true)
              ? $bookingStatus
              : "pending";
            $bookingImage = $roomImages[$recentBooking["room_code"]] ?? "img/hotelroom.jpg";
            ?>
            <div class="booking-item">
              <img
                src="<?php echo htmlspecialchars($bookingImage, ENT_QUOTES, "UTF-8"); ?>"
                alt="<?php echo htmlspecialchars($recentBooking["room_name"], ENT_QUOTES, "UTF-8"); ?>"
                class="booking-image" />
              <div class="booking-details">
                <h3><?php echo htmlspecialchars($recentBooking["room_name"], ENT_QUOTES, "UTF-8"); ?></h3>
                <p>
                  <i class="fa-regular fa-calendar"></i>
                  Check-in: <?php echo htmlspecialchars(date("d M Y", strtotime($recentBooking["check_in"])), ENT_QUOTES, "UTF-8"); ?>
                </p>
                <p>
                  <i class="fa-regular fa-calendar"></i>
                  Check-out: <?php echo htmlspecialchars(date("d M Y", strtotime($recentBooking["check_out"])), ENT_QUOTES, "UTF-8"); ?>
                </p>
                <p>
                  <i class="fa-solid fa-moon"></i>
                  <?php echo (int) $recentBooking["total_nights"]; ?>
                  <?php echo (int) $recentBooking["total_nights"] === 1 ? "Night" : "Nights"; ?>
                </p>
              </div>
              <div class="booking-status">
                <span class="status <?php echo htmlspecialchars($bookingStatusClass, ENT_QUOTES, "UTF-8"); ?>">
                  <?php echo htmlspecialchars(ucfirst($bookingStatus), ENT_QUOTES, "UTF-8"); ?>
                </span>
                <strong>KSh <?php echo number_format((float) $recentBooking["total_price"], 2); ?></strong>
                <a href="bookings.php" class="details-btn">View Details</a>
              </div>
            </div>
          <?php else: ?>
            <p class="activity-empty">
              You haven't made a booking yet. <a href="rooms.php">Browse rooms</a>.
            </p>
          <?php endif; ?>
        </article>

        <!--Recent orders-->
        <article class="activity-card">
          <div class="activity-heading">
            <h2>Recent Orders</h2>
            <a href="orders.php">View All <i class="fa-solid fa-arrow-right"></i></a>
          </div>

          <?php if (count($recentOrders) > 0): ?>
            <?php foreach ($recentOrders as $order): ?>
              <?php
              $firstItem = $order["items"][0] ?? null;
              $orderStatus = $order["status"];
              $orderStatusLabel = $orderStatusLabels[$orderStatus] ?? ucfirst($orderStatus);
              $orderStatusClass = $orderStatusClasses[$orderStatus] ?? "preparing";
              $itemCount = count($order["items"]);
              ?>
              <div class="order-item">
                <img
                  src="<?php echo htmlspecialchars(($firstItem["image"] ?? "") !== "" ? $firstItem["image"] : "img/grilled chicken.jpg", ENT_QUOTES, "UTF-8"); ?>"
                  alt="<?php echo htmlspecialchars($firstItem["name"] ?? "Food order", ENT_QUOTES, "UTF-8"); ?>"
                  class="order-image" />
                <div class="order-details">
                  <h3>
                    <?php echo htmlspecialchars($firstItem["name"] ?? "Food order", ENT_QUOTES, "UTF-8"); ?>
                    <?php if ($itemCount > 1): ?>
                      <span class="additional-order-items">+<?php echo $itemCount - 1; ?> more</span>
                    <?php endif; ?>
                  </h3>
                  <p>
                    <?php echo htmlspecialchars(date("d M Y", strtotime($order["created_at"])), ENT_QUOTES, "UTF-8"); ?>
                    &nbsp;/&nbsp; KSh <?php echo number_format((float) $order["total_amount"], 2); ?>
                    &nbsp;/&nbsp; Order #<?php echo (int) $order["id"]; ?>
                  </p>
                </div>
                <span class="status <?php echo htmlspecialchars($orderStatusClass, ENT_QUOTES, "UTF-8"); ?>">
                  <?php echo htmlspecialchars($orderStatusLabel, ENT_QUOTES, "UTF-8"); ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="activity-empty">
              You haven't placed an order yet. <a href="menu.php">Explore the menu</a>.
            </p>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </section>

  <!--footer-->
  <footer class="footer">
    <div class="footer-brand">
      <img src="img/starianlogo.png" alt="Starian Hotel logo" />

      <div>
        <h2>Starian <span>Hotel</span></h2>
        <p>Relax Dine Enjoy</p>
      </div>
    </div>

    <div class="footer-contact">
      <p>
        <i class="fa-solid fa-location-dot"></i>
        &nbsp; section 58 Road, Nakuru, Kenya
      </p>

      <p>
        <i class="fa-solid fa-phone"></i>
        &nbsp; +254 754 171 383
      </p>

      <p>
        <i class="fa-solid fa-envelope"></i>
        &nbsp; starianhotel@gmail.com
      </p>
    </div>

    <div class="footer-social">
      <p>Follow Us</p>

      <a href="#" aria-label="Facebook">
        <i class="fa-brands fa-facebook-f"></i>
      </a>

      <a href="#" aria-label="Instagram">
        <i class="fa-brands fa-instagram"></i>
      </a>

      <a href="#" aria-label="Twitter">
        <i class="fa-brands fa-twitter"></i>
      </a>
    </div>
  </footer>
  <script src="javascript/navigation.js"></script>
</body>

</html>