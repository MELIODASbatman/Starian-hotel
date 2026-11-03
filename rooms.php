<?php
session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
require_once "php/db.php";

$isLoggedIn = isset($_SESSION["user_id"]);

// Fetch all available rooms
$sql = $conn->query("
    SELECT *
    FROM rooms
    WHERE status = 'available'
    ORDER BY id ASC
");

$rooms = [];

if ($sql) {
  while ($row = $sql->fetch_assoc()) {
    $rooms[] = $row;
  }
}

// Group rooms by category
$roomCategories = [
  "standard" => [],
  "deluxe" => [],
  "executive" => [],
  "family" => []
];

$categoryNames = [
  "standard" => "Standard Rooms",
  "deluxe" => "Deluxe Rooms",
  "executive" => "Executive Rooms",
  "family" => "Family Rooms"
];

foreach ($rooms as $room) {
  $category = strtolower($room["category"] ?? "");

  if (isset($roomCategories[$category])) {
    $roomCategories[$category][] = $room;
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Room | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/rooms.css?v=5" />
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
              <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" class="nav-avatar" />
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

  <!-- hero section -->
  <section class="rooms-hero">
    <div class="rooms-hero-content">
      <h1>Our Rooms</h1>
      <p>Comfortable rooms for a relaxing stay</p>
      <div class="rooms-breadcrumb">
        <a href="index.php">Home</a>
        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
        <span>Rooms</span>
      </div>
    </div>
  </section>

  <!-- booking -->
  <section class="booking-search" id="booking">
    <div class="booking-heading">
      <span class="booking-icon">
        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
      </span>
      <div>
        <h2>Plan Your Stay</h2>
        <p>
          Choose a room here or from the list below, then confirm your dates
          and arrival time.
        </p>
      </div>
    </div>

    <form class="booking-form" id="availability-form">
      <div class="booking-field">
        <label for="room-category">Room Category</label>
        <select id="room-category" name="room_category" required>
          <option value="">Choose a category</option>
          <option value="standard">Standard Room</option>
          <option value="deluxe">Deluxe Room</option>
          <option value="executive">Executive Room</option>
          <option value="family">Family Room</option>
        </select>
      </div>

      <div class="booking-field">
        <label for="room-option">Room Option</label>
        <select id="room-option" name="room_option" required>

          <option value="">Choose a room</option>

          <?php foreach ($roomCategories as $category => $categoryRooms): ?>

            <?php if (!empty($categoryRooms)): ?>

              <optgroup label="<?= htmlspecialchars($categoryNames[$category] ?? ucfirst($category) . ' Rooms') ?>">

                <?php foreach ($categoryRooms as $room): ?>

                  <option value="<?= htmlspecialchars($room["room_code"]) ?>">
                    <?= htmlspecialchars($room["room_name"]) ?>
                    - KSh <?= number_format((float)$room["price_per_night"]) ?>/night
                  </option>

                <?php endforeach; ?>

              </optgroup>

            <?php endif; ?>

          <?php endforeach; ?>

        </select>
      </div>

      <div class="booking-field booking-room-selection">
        <span class="booking-field-label">Rooms in this booking</span>
        <button class="add-room-btn" id="add-room-btn" type="button">
          <i class="fa-solid fa-plus" aria-hidden="true"></i>
          Add selected room
        </button>
        <ul
          class="selected-rooms"
          id="selected-rooms"
          aria-label="Rooms added to this booking"
          hidden></ul>
      </div>

      <div class="booking-field">
        <label for="check-in">Check-in Date</label>
        <input type="date" id="check-in" name="check_in" required />
      </div>

      <div class="booking-field">
        <label for="check-out">Check-out Date</label>
        <input type="date" id="check-out" name="check_out" required />
      </div>

      <div class="booking-field">
        <label for="arrival-time">Arrival Time</label>
        <input type="time" id="arrival-time" name="arrival_time" required />
      </div>

      <div class="booking-actions">
        <button
          type="button"
          class="availability-btn"
          id="check-availability-btn">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          Check Availability
        </button>
        <button type="button" class="availability-btn" id="book-now-btn">
          <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
          Book Now
        </button>
      </div>
    </form>

    <div
      class="availability-message"
      id="availability-result"
      aria-live="polite">
      Pick a room here, or click “Choose Room” on any room below and it will
      be filled in for you. Then confirm your dates and time.
    </div>
  </section>

  <div
    class="booking-notification"
    id="booking-notification"
    role="status"
    aria-live="polite"
    aria-atomic="true"
    hidden>
    <span class="booking-notification-icon" aria-hidden="true"></span>
    <div class="booking-notification-content">
      <strong class="booking-notification-title" id="booking-notification-title"></strong>
      <p id="booking-notification-message"></p>
    </div>
    <button
      class="booking-notification-close"
      id="booking-notification-close"
      type="button"
      aria-label="Dismiss notification">
      &times;
    </button>
  </div>

  <main class="room-catalogue">

    <?php
    // $categoryNames = [
    //   "standard" => "Standard Rooms",
    //   "deluxe" => "Deluxe Rooms",
    //   "executive" => "Executive Rooms",
    //   "family" => "Family Rooms"
    // ];

    $categoryDescriptions = [
      "standard" => "Comfortable and well-furnished rooms for everyday stays.",
      "deluxe" => "Elegant interiors, extra comfort and stylish furnishings.",
      "executive" => "Premium rooms with spacious layouts and sophisticated interiors.",
      "family" => "Spacious and family-friendly rooms with room for everyone."
    ];
    ?>

    <?php foreach ($roomCategories as $category => $categoryRooms): ?>

      <?php if (!empty($categoryRooms)): ?>

        <section class="room-category"
          id="<?= htmlspecialchars($category) ?>-rooms">

          <div class="category-heading">

            <div class="category-title">
              <h2>
                <?= htmlspecialchars($categoryNames[$category]) ?>
              </h2>

              <p>
                <?= htmlspecialchars($categoryDescriptions[$category]) ?>
              </p>
            </div>

            <span class="category-count">
              <?= count($categoryRooms) ?> Room Options
            </span>

          </div>

          <div class="room-grid">

            <?php foreach ($categoryRooms as $index => $room): ?>

              <?php
              $roomId = (int)$room["id"];
              $imageFilename = basename(rawurldecode((string)($room["image"] ?? "")));
              $image = "img/bed.jpg";

              if ($imageFilename !== "") {
                if (is_file(__DIR__ . "/img/rooms/" . $imageFilename)) {
                  $image = "img/rooms/" . rawurlencode($imageFilename);
                } elseif (is_file(__DIR__ . "/img/" . $imageFilename)) {
                  $image = "img/" . rawurlencode($imageFilename);
                }
              }
              ?>
              <article class="room-card"
                id="<?= htmlspecialchars($room["room_code"]) ?>">
                <div class="room-photo">

                  <img
                    src="<?= htmlspecialchars($image) ?>"
                    alt="<?= htmlspecialchars($room["room_name"]) ?>"
                    loading="lazy">

                  <span class="room-number">
                    Option <?= str_pad($index + 1, 2, "0", STR_PAD_LEFT) ?>
                  </span>

                </div>

                <div class="room-info">

                  <h3>
                    <?= htmlspecialchars($room["room_name"]) ?>
                  </h3>

                  <p class="room-description">
                    <?= htmlspecialchars($room["description"] ?? "") ?>
                  </p>

                  <div class="room-features">

                    <span>
                      <i class="fa-solid fa-bed"></i>
                      <?= htmlspecialchars($room["bed_type"] ?? "Bed information unavailable") ?>
                    </span>

                    <?php if (!empty($room["wifi"])): ?>
                      <span>
                        <i class="fa-solid fa-wifi"></i>
                        Free Wi-Fi
                      </span>
                    <?php endif; ?>

                    <?php if (!empty($room["room_service"])): ?>
                      <span>
                        <i class="fa-solid fa-bell-concierge"></i>
                        Room Service
                      </span>
                    <?php endif; ?>

                  </div>

                  <details class="room-more">

                    <summary>View more room details</summary>

                    <div class="room-more-content">

                      <p>
                        <?= htmlspecialchars($room["room_details"] ?? "") ?>
                      </p>

                      <h4>Room amenities</h4>

                      <ul>
                        <?php
                        $amenities = explode(",", $room["amenities"] ?? "");

                        foreach ($amenities as $amenity):
                          $amenity = trim($amenity);

                          if ($amenity !== ""):
                        ?>
                            <li><?= htmlspecialchars($amenity) ?></li>
                        <?php
                          endif;
                        endforeach;
                        ?>
                      </ul>

                      <p class="room-note">
                        Confirm specific amenities with reception before booking.
                      </p>

                    </div>

                  </details>

                  <div class="room-bottom">

                    <p class="room-price">
                      <strong>
                        KSh <?= number_format((float)$room["price_per_night"]) ?>
                      </strong>
                      <span>/ night</span>
                    </p>

                    <a
                      href="#booking"
                      class="view-room-btn"
                      data-room="<?= htmlspecialchars($room["room_code"]) ?>">
                      Choose Room
                      <i class="fa-solid fa-arrow-right"></i>
                    </a>

                  </div>

                </div>

              </article>

            <?php endforeach; ?>

          </div>

        </section>

      <?php endif; ?>

    <?php endforeach; ?>

  </main>

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
        &nbsp; brianmagana048@gmail.com
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
  <script src="javascript/room.js?v=3"></script>
</body>

</html>