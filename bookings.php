<?php

session_start();

require_once "php/db.php";

// Ensure the guest is logged in
if (!isset($_SESSION["user_id"])) {
  header("Location: login.html");
  exit;
}

// Get the logged-in guest's details
$user_id = (int) $_SESSION["user_id"];

$fullname = $_SESSION["fullname"] ?? "Guest";
$email = $_SESSION["email"] ?? "";


// Retrieve bookings belonging to the logged-in guest
$sql = $conn->prepare(
  "SELECT
        bookings.id,
        bookings.check_in,
        bookings.check_out,
        bookings.total_nights,
        bookings.total_price,
        bookings.booking_status,
        rooms.room_name,
        rooms.category,
        rooms.room_code
     FROM bookings
     INNER JOIN rooms
        ON bookings.room_id = rooms.id
     WHERE bookings.user_id = ?
     ORDER BY bookings.created_at DESC"
);

$sql->bind_param("i", $user_id);
$sql->execute();

$result = $sql->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}

$sql->close();

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

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Bookings | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css" />
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/bookings.css" />
</head>

<body>
  <header class="navi">
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
    <nav class="nav-links" id="main-navigation" aria-label="Main navigation">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="rooms.php">Rooms</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li>
          <a href="dashboard.php" class="account-link">
            <img src="img/user.png" alt="" class="nav-avatar" />
            <span><?php echo htmlspecialchars($fullname); ?></span>
          </a>
        </li>
      </ul>
    </nav>
  </header>

  <div class="dashboard-layout">
    <aside class="sidebar" aria-label="Customer navigation">
      <div class="customer-profile">
        <div class="profile-avatar">
          <i class="fa-solid fa-user" aria-hidden="true"></i>
        </div>
        <h3><?php echo htmlspecialchars($fullname); ?></h3>
        <p>Customer</p>
      </div>
      <nav class="sidebar-nav" aria-label="Account navigation">
        <a href="dashboard.php" class="sidebar-link">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          <span>Dashboard</span>
        </a>
        <a
          href="bookings.php"
          class="sidebar-link active"
          aria-current="page">
          <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
          <span>My Bookings</span>
        </a>
        <a href="orders.php" class="sidebar-link">
          <i class="fa-solid fa-utensils" aria-hidden="true"></i>
          <span>My Orders</span>
        </a>
        <a href="profile.php" class="sidebar-link">
          <i class="fa-solid fa-user" aria-hidden="true"></i>
          <span>My Profile</span>
        </a>
      </nav>
      <div class="sidebar-promo" aria-label="Good Food, Great Stays, Better Moments">
        <p>Good Food</p>
        <p>Great Stays</p>
        <p class="promo-highlight">Better Moments</p>
      </div>
    </aside>

    <main class="main-info bookings-main">
      <div class="bookings-heading">
        <div>
          <h2>My Bookings</h2>
          <p>View and manage your room bookings.</p>
        </div>
        <?php if (count($bookings) > 0): ?>
          <label class="booking-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="visually-hidden">Search bookings</span>
            <input
              id="booking-search-input"
              type="search"
              placeholder="Search bookings..."
              autocomplete="off" />
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
          </label>
        <?php endif; ?>
      </div>

      <?php if (count($bookings) > 0): ?>
        <div class="booking-filters" role="group" aria-label="Filter bookings">
          <button class="booking-filter active" type="button" data-filter="all" aria-pressed="true">
            All
          </button>
          <button class="booking-filter" type="button" data-filter="upcoming" aria-pressed="false">
            Upcoming
          </button>
          <button class="booking-filter" type="button" data-filter="completed" aria-pressed="false">
            Completed
          </button>
          <button class="booking-filter" type="button" data-filter="cancelled" aria-pressed="false">
            Cancelled
          </button>
        </div>
      <?php endif; ?>

      <section class="booking-list" id="booking-list" aria-label="Your bookings">
        <?php if (count($bookings) > 0): ?>
          <?php foreach ($bookings as $booking): ?>
            <?php
            $status = strtolower($booking["booking_status"]);

            if ($status === "cancelled") {
              $filterCategory = "cancelled";
            } elseif ($booking["check_in"] > date("Y-m-d")) {
              $filterCategory = "upcoming";
            } else {
              $filterCategory = "completed";
            }

            $checkInDisplay = date("d M Y", strtotime($booking["check_in"]));
            $checkOutDisplay = date("d M Y", strtotime($booking["check_out"]));
            $roomImage = $roomImages[$booking["room_code"]] ?? "img/hotelroom.jpg";
            ?>
            <article
              class="booking-item"
              data-category="<?= htmlspecialchars($filterCategory, ENT_QUOTES, 'UTF-8') ?>"
              data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
              <img
                src="<?= htmlspecialchars($roomImage, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($booking["room_name"], ENT_QUOTES, 'UTF-8') ?>"
                class="booking-image">
              <div class="booking-details">
                <h3><?= htmlspecialchars($booking["room_name"], ENT_QUOTES, 'UTF-8') ?></h3>
                <p>
                  <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                  Check-in: <?= $checkInDisplay ?>
                </p>
                <p>
                  <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                  Check-out: <?= $checkOutDisplay ?>
                </p>
                <p>
                  <i class="fa-solid fa-moon" aria-hidden="true"></i>
                  <?= (int) $booking["total_nights"] ?>
                  <?= (int) $booking["total_nights"] === 1 ? "Night" : "Nights" ?>
                </p>
                <p>Booking ID: #<?= (int) $booking["id"] ?></p>
              </div>
              <div class="booking-status">
                <span class="status <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <strong>KSh <?= number_format((float) $booking["total_price"], 2) ?></strong>
                <a href="rooms.php#booking" class="details-btn">Book Another Room</a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="booking-empty">
            You have not made any room bookings yet.
            <a href="rooms.php#booking">Book a room now</a>.
          </p>
        <?php endif; ?>

        <p class="booking-empty" id="booking-empty" hidden>
          No bookings match your search.
        </p>
      </section>
    </main>
  </div>

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
  <script src="javascript/bookings.js"></script>
</body>

</html>