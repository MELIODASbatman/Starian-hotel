<?php

session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
$isLoggedIn = isset($_SESSION["user_id"]);

require_once __DIR__ . "/php/db.php";

// Ensure the customer is logged in.
if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

// Get the logged-in customer's ID.
$user_id = (int) $_SESSION["user_id"];

// Accept either the original single-booking URL or a comma-separated batch.
$booking_ids = [];
if (isset($_GET["booking_ids"]) && is_string($_GET["booking_ids"])) {
  $requestedIds = explode(",", $_GET["booking_ids"]);
} elseif (isset($_GET["booking_id"])) {
  $requestedIds = [(string) $_GET["booking_id"]];
} else {
  $requestedIds = [];
}

foreach ($requestedIds as $requestedId) {
  if (!ctype_digit($requestedId) || (int) $requestedId < 1) {
    $booking_ids = [];
    break;
  }
  $booking_ids[] = (int) $requestedId;
}

$booking_ids = array_values(array_unique($booking_ids));
if (
  count($booking_ids) === 0 ||
  count($booking_ids) > 16 ||
  count($booking_ids) !== count($requestedIds)
) {
  header("Location: bookings.php");
  exit;
}

// Retrieve every requested booking belonging to this customer.
$placeholders = implode(",", array_fill(0, count($booking_ids), "?"));
$sql = "
    SELECT
        b.id AS booking_id,
        b.user_id,
        b.check_in,
        b.check_out,
        b.total_nights,
        b.total_price,
        b.booking_status,
        b.payment_status,
        b.payment_method,
        r.room_name,
        r.room_code,
        r.price_per_night,
        r.image AS room_image
    FROM bookings AS b
    INNER JOIN rooms AS r
        ON b.room_id = r.id
    WHERE b.id IN ($placeholders)
      AND b.user_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
  error_log("Unable to prepare checkout booking query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load the booking. Please try again later.");
}

$bindParameters = [str_repeat("i", count($booking_ids) + 1)];
foreach ($booking_ids as $index => $bookingId) {
  $bindParameters[] = &$booking_ids[$index];
}
$bindParameters[] = &$user_id;
call_user_func_array([$stmt, "bind_param"], $bindParameters);

if (!$stmt->execute()) {
  error_log("Unable to load checkout bookings: " . $stmt->error);
  $stmt->close();
  http_response_code(500);
  exit("Unable to load the booking. Please try again later.");
}

$result = $stmt->get_result();
$bookingsById = [];
while ($row = $result->fetch_assoc()) {
  $bookingsById[(int) $row["booking_id"]] = $row;
}

$stmt->close();

if (count($bookingsById) !== count($booking_ids)) {
  http_response_code(404);
  exit("Booking not found or you do not have permission to view it.");
}

$bookings = [];
$room_amount_cents = 0;
foreach ($booking_ids as $bookingId) {
  $booking = $bookingsById[$bookingId];
  if ($booking["booking_status"] === "cancelled") {
    http_response_code(409);
    exit("A booking in this checkout has been cancelled.");
  }

  if (
    count($bookings) > 0 &&
    (
      $booking["check_in"] !== $bookings[0]["check_in"] ||
      $booking["check_out"] !== $bookings[0]["check_out"] ||
      (int) $booking["total_nights"] !== (int) $bookings[0]["total_nights"]
    )
  ) {
    http_response_code(409);
    exit("Rooms in a combined checkout must have the same stay dates.");
  }

  $bookings[] = $booking;
  $room_amount_cents += (int) round((float) $booking["total_price"] * 100);
}

$booking = $bookings[0];
$room_amount = $room_amount_cents / 100;
$taxes_and_fees = round($room_amount_cents * 0.10) / 100;

// Calculate the final checkout amount.
$grand_total = $room_amount + $taxes_and_fees;

// Format the dates for display.
$check_in_display = date(
  "d M Y",
  strtotime($booking["check_in"])
);

$check_out_display = date(
  "d M Y",
  strtotime($booking["check_out"])
);

// Format the amounts for display.
$room_amount_display = number_format($room_amount, 2);
$taxes_display = number_format($taxes_and_fees, 2);
$grand_total_display = number_format($grand_total, 2);

// Helper function to safely display database values in HTML.
function escapeHTML($value)
{
  return htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    "UTF-8"
  );
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="Review your Starian Hotel booking and choose a payment method." />
  <title>Complete Your Payment | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/style.css?v=4" />
  <link rel="stylesheet" href="css/checkout.css?v=2" />
</head>

<body class="checkout-body">
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

  <main class="checkout-main">
    <section class="checkout-heading" aria-labelledby="checkout-title">
      <div class="checkout-title-row">
        <span aria-hidden="true"></span>
        <h2 id="checkout-title">Complete Your Payment</h2>
        <span aria-hidden="true"></span>
      </div>
      <p>Review your selected rooms and choose a payment method.</p>
    </section>

    <div class="checkout-grid">
      <section
        class="checkout-card booking-summary"
        aria-labelledby="summary-title">
        <div class="checkout-card-heading">
          <h3 id="summary-title">
            <i class="fa-solid fa-bed" aria-hidden="true"></i>
            Room Summary
          </h3>
          <a class="edit-booking" href="rooms.php#booking">
            <i class="fa-solid fa-pen" aria-hidden="true"></i>
            Edit Booking
          </a>
        </div>

        <?php foreach ($bookings as $booking): ?>
          <?php
          $bookingImageFilename = basename(rawurldecode((string)($booking["room_image"] ?? "")));
          $bookingImage = "img/dbed.jpg";

          if ($bookingImageFilename !== "") {
            if (is_file(__DIR__ . "/img/rooms/" . $bookingImageFilename)) {
              $bookingImage = "img/rooms/" . rawurlencode($bookingImageFilename);
            } elseif (is_file(__DIR__ . "/img/" . $bookingImageFilename)) {
              $bookingImage = "img/" . rawurlencode($bookingImageFilename);
            }
          }
          ?>
          <div class="room-summary">
            <img
              class="summary-room-image"
              src="<?= htmlspecialchars($bookingImage, ENT_QUOTES, "UTF-8") ?>"
              alt="<?= escapeHTML($booking["room_name"]) ?>" />
            <div class="room-summary-details">
              <h4><?= escapeHTML($booking["room_name"]) ?></h4>
              <p>
                <i class="fa-solid fa-bed" aria-hidden="true"></i> Room stay
              </p>
              <p>
                <i class="fa-solid fa-wifi" aria-hidden="true"></i> Free Wi-Fi
              </p>
              <p>
                <i class="fa-solid fa-bell-concierge" aria-hidden="true"></i>
                Room Service
              </p>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="stay-details" aria-label="Stay details">
          <div class="stay-detail">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            <p>
              <span>Check-in</span><strong><time datetime="<?= escapeHTML($booking["check_in"]) ?>"><?= date("d M Y", strtotime($booking["check_in"])) ?></time></strong>
            </p>
          </div>
          <div class="stay-detail">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            <p>
              <span>Check-out</span><strong><time datetime="<?= escapeHTML($booking["check_out"]) ?>"><?= date("d M Y", strtotime($booking["check_out"])) ?></time></strong>
            </p>
          </div>
          <div class="stay-detail">
            <i class="fa-regular fa-moon" aria-hidden="true"></i>
            <p><span>Duration</span><strong><?= escapeHTML($booking["total_nights"]) ?> <?= $booking["total_nights"] === 1 ? "Night" : "Nights" ?></strong></p>
          </div>
        </div>

        <div class="price-breakdown">
          <?php foreach ($bookings as $booking): ?>
            <p>
              <span>
                <?= escapeHTML($booking["room_name"]) ?>
                (<?= escapeHTML($booking["total_nights"]) ?>
                <?= (int) $booking["total_nights"] === 1 ? "Night" : "Nights" ?>)
              </span>
              <strong>KSh <?= number_format((float) $booking["total_price"], 2) ?></strong>
            </p>
          <?php endforeach; ?>
          <p><span>Rooms subtotal</span><strong>KSh <?= $room_amount_display ?></strong></p>
          <p><span>Taxes &amp; fees (10%)</span><strong>KSh <?= $taxes_display ?></strong></p>
          <p class="price-total">
            <span>Total</span><strong>KSh <?= $grand_total_display ?></strong>
          </p>
        </div>
      </section>

      <section
        class="checkout-card payment-card"
        aria-labelledby="payment-title">
        <div class="checkout-card-heading">
          <h3 id="payment-title">
            <i class="fa-regular fa-credit-card" aria-hidden="true"></i>
            Payment Details
          </h3>
        </div>

        <div class="payment-selector">
          <input
            class="payment-choice"
            type="radio"
            name="payment-method"
            id="payment-mpesa"
            checked />
          <input
            class="payment-choice"
            type="radio"
            name="payment-method"
            id="payment-card" />
          <input
            class="payment-choice"
            type="radio"
            name="payment-method"
            id="payment-paypal" />

          <div
            class="payment-methods"
            role="radiogroup"
            aria-label="Choose a payment method">
            <label for="payment-mpesa">
              <i
                class="fa-solid fa-mobile-screen-button"
                aria-hidden="true"></i>
              M-Pesa
            </label>
            <label for="payment-card">
              <i class="fa-regular fa-credit-card" aria-hidden="true"></i>
              Card
            </label>
            <label for="payment-paypal">
              <i class="fa-brands fa-paypal" aria-hidden="true"></i>
              PayPal
            </label>
          </div>

          <div class="payment-panels">
            <section
              class="payment-panel payment-panel-mpesa"
              aria-labelledby="mpesa-title">
              <h4 id="mpesa-title">M-Pesa Payment</h4>
              <p>Enter your M-Pesa phone number to complete the payment.</p>
              <div class="phone-input">
                <label class="visually-hidden" for="country-code">Country code</label>
                <select id="country-code" name="country_code">
                  <option value="+254">+254</option>
                </select>
                <label class="visually-hidden" for="mpesa-phone">M-Pesa phone number</label>
                <input
                  type="tel"
                  id="mpesa-phone"
                  name="mpesa_phone"
                  autocomplete="tel-national"
                  placeholder="712 345 678"
                  maxlength="12" />
              </div>
            </section>

            <section
              class="payment-panel payment-panel-card"
              aria-labelledby="card-title">
              <h4 id="card-title">Card Payment</h4>
              <p>Card payments are not connected in this demo. Select M-Pesa for a simulated payment.</p>
            </section>

            <section
              class="payment-panel payment-panel-paypal"
              aria-labelledby="paypal-title">
              <h4 id="paypal-title">PayPal Payment</h4>
              <p>PayPal is not connected in this demo checkout.</p>
              <div class="paypal-message">
                <i class="fa-brands fa-paypal" aria-hidden="true"></i>
                No payment will be submitted until a payment provider is
                configured.
              </div>
            </section>
          </div>
        </div>

        <button class="pay-button" id="pay-button" type="button">
          Pay KSh <?= $grand_total_display ?>
        </button>
        <p
          class="checkout-feedback"
          id="checkout-feedback"
          role="status"
          aria-live="polite"
          hidden></p>
        <p class="payment-security">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          M-Pesa demo only. No real money is transferred; card and PayPal are not connected.
        </p>

        <div class="terms-agreement">
          <input type="checkbox" id="agree-terms" />
          <label for="agree-terms">
            I agree to the booking
            <a href="contact.php">terms</a> and
            <a href="contact.php">cancellation policy</a>.
          </label>
        </div>
      </section>
    </div>

    <nav class="checkout-progress" aria-label="Booking progress">
      <ol>
        <li class="progress-complete">
          <span class="progress-marker"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <strong>1. Booking</strong>
          <small>Completed</small>
        </li>
        <li class="progress-current" aria-current="step">
          <span class="progress-marker">2</span>
          <strong>2. Payment</strong>
          <small>Current</small>
        </li>
        <li>
          <span class="progress-marker">3</span>
          <strong>3. Confirmation</strong>
          <small>Upcoming</small>
        </li>
      </ol>
    </nav>

    <p class="checkout-assurance">
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      Your booking will be confirmed after payment is completed.
    </p>
  </main>

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
        <i class="fa-solid fa-location-dot" aria-hidden="true"></i> &nbsp;
        section 58 Road, Nakuru, Kenya
      </p>
      <p>
        <i class="fa-solid fa-phone" aria-hidden="true"></i> &nbsp; +254 754
        171 383
      </p>
      <p>
        <i class="fa-solid fa-envelope" aria-hidden="true"></i> &nbsp;
        brianmagana048@gmail.com
      </p>
    </div>

    <div class="footer-social">
      <p>Follow Us</p>
      <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
      <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
      <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter" aria-hidden="true"></i></a>
    </div>
  </footer>

  <script src="javascript/navigation.js?v=1"></script>
  <script>
    const checkoutBookingIds = <?= json_encode($booking_ids) ?>;
  </script>
  <script src="javascript/checkout.js?v=2"></script>
</body>

</html>