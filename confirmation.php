<?php

session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
$isLoggedIn = isset($_SESSION["user_id"]);

require_once __DIR__ . "/php/db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$user_id = (int) $_SESSION["user_id"];

if (isset($_GET["booking_ids"]) && is_string($_GET["booking_ids"])) {
  $requestedIds = explode(",", $_GET["booking_ids"]);
} elseif (isset($_GET["booking_id"]) && is_string($_GET["booking_id"])) {
  $requestedIds = [$_GET["booking_id"]];
} else {
  $requestedIds = [];
}

$booking_ids = [];
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

$placeholders = implode(",", array_fill(0, count($booking_ids), "?"));
$stmt = $conn->prepare("
  SELECT
    b.id AS booking_id,
    b.check_in,
    b.check_out,
    b.total_nights,
    b.total_price,
    b.booking_status,
    b.payment_status,
    b.payment_method,
    b.transaction_id,
    b.payment_reference,
    b.paid_at,
    r.room_name,
    r.room_code,
    r.image AS room_image,
    r.wifi,
    r.room_service
  FROM bookings AS b
  INNER JOIN rooms AS r ON b.room_id = r.id
  WHERE b.id IN ($placeholders)
    AND b.user_id = ?
");

if (!$stmt) {
  error_log("Unable to prepare confirmation booking query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your booking confirmation. Please try again later.");
}

$bindParameters = [str_repeat("i", count($booking_ids) + 1)];
foreach ($booking_ids as $index => $bookingId) {
  $bindParameters[] = &$booking_ids[$index];
}
$bindParameters[] = &$user_id;
call_user_func_array([$stmt, "bind_param"], $bindParameters);

if (!$stmt->execute()) {
  error_log("Unable to load confirmation bookings: " . $stmt->error);
  $stmt->close();
  http_response_code(500);
  exit("Unable to load your booking confirmation. Please try again later.");
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
    header("Location: bookings.php");
    exit;
  }

  if ($booking["payment_status"] !== "paid") {
    header("Location: checkout.php?booking_ids=" . implode(",", $booking_ids));
    exit;
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
    exit("The bookings do not share the same stay dates.");
  }

  $bookings[] = $booking;
  $room_amount_cents += (int) round((float) $booking["total_price"] * 100);
}

$booking = $bookings[0];
$all_bookings_confirmed = true;
$has_pending_bookings = false;

foreach ($bookings as $bookingRecord) {

  if ($bookingRecord["booking_status"] !== "confirmed") {
    $all_bookings_confirmed = false;
  }

  if ($bookingRecord["booking_status"] === "pending") {
    $has_pending_bookings = true;
  }
}

$room_amount = $room_amount_cents / 100;
$taxes_and_fees = round($room_amount_cents * 0.10) / 100;
$grand_total = $room_amount + $taxes_and_fees;
$transaction_reference = $booking["transaction_id"] ?: $booking["payment_reference"];
$paid_at = $booking["paid_at"]
  ? date("d M Y, g:i a", strtotime($booking["paid_at"]))
  : "Recorded";

function escapeHTML($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="Your Starian Hotel booking and demo payment confirmation." />
  <title>Booking Confirmation | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/style.css?v=4" />
  <link rel="stylesheet" href="css/checkout.css?v=3" />
</head>

<body class="checkout-body confirmation-body">
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

  <main class="checkout-main confirmation-main">
    <section class="checkout-heading" aria-labelledby="confirmation-title">
      <div class="checkout-title-row">
        <span aria-hidden="true"></span>
        <h2 id="confirmation-title"><?= $all_bookings_confirmed ? "Booking Confirmed" : "Payment Received" ?></h2>
        <span aria-hidden="true"></span>
      </div>
      <p>
        <?= $all_bookings_confirmed
          ? "Your booking is confirmed. We look forward to welcoming you."
          : "Your payment is recorded. Your booking is awaiting confirmation from hotel staff." ?>
      </p>
    </section>

    <div class="confirmation-success" role="status">
      <span class="confirmation-success-icon" aria-hidden="true">
        <i class="fa-solid fa-check"></i>
      </span>
      <div>
        <h3>Payment complete</h3>
        <p>
          Your M-Pesa demo payment was recorded. No real money was transferred.
          <?= $all_bookings_confirmed
            ? "Your stay is confirmed."
            : "Your booking is awaiting confirmation from hotel staff." ?>
        </p>
      </div>
    </div>

    <div class="checkout-grid confirmation-grid">
      <section
        class="checkout-card booking-summary"
        aria-labelledby="summary-title">
        <div class="checkout-card-heading">
          <h3 id="summary-title">
            <i class="fa-solid fa-bed" aria-hidden="true"></i>
            Room Summary
          </h3>
          <span class="confirmation-status<?= $all_bookings_confirmed ? "" : " confirmation-status-pending" ?>">
            <i class="fa-solid <?= $all_bookings_confirmed ? "fa-circle-check" : "fa-clock" ?>" aria-hidden="true"></i>
            <?= $all_bookings_confirmed ? "Confirmed" : "Pending confirmation" ?>
          </span>
        </div>

        <?php foreach ($bookings as $booking): ?>
          <?php
          $bookingImageFilename = basename(rawurldecode((string) ($booking["room_image"] ?? "")));
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
              src="<?= escapeHTML($bookingImage) ?>"
              alt="<?= escapeHTML($booking["room_name"]) ?>" />
            <div class="room-summary-details">
              <h4><?= escapeHTML($booking["room_name"]) ?></h4>
              <p>
                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                Room stay
              </p>
              <?php if ((int)$booking["wifi"] === 1): ?>
                <p>
                  <i class="fa-solid fa-wifi" aria-hidden="true"></i>
                  Free Wi-Fi
                </p>
              <?php endif; ?>

              <?php if ((int)$booking["room_service"] === 1): ?>
                <p>
                  <i class="fa-solid fa-bell-concierge" aria-hidden="true"></i>
                  Room Service
                </p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="stay-details" aria-label="Stay details">
          <div class="stay-detail">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            <p>
              <span>Check-in</span>
              <strong><time datetime="<?= escapeHTML($booking["check_in"]) ?>"><?= date("d M Y", strtotime($booking["check_in"])) ?></time></strong>
            </p>
          </div>
          <div class="stay-detail">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
            <p>
              <span>Check-out</span>
              <strong><time datetime="<?= escapeHTML($booking["check_out"]) ?>"><?= date("d M Y", strtotime($booking["check_out"])) ?></time></strong>
            </p>
          </div>
          <div class="stay-detail">
            <i class="fa-regular fa-moon" aria-hidden="true"></i>
            <p>
              <span>Duration</span>
              <strong><?= escapeHTML($booking["total_nights"]) ?> <?= (int) $booking["total_nights"] === 1 ? "Night" : "Nights" ?></strong>
            </p>
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
          <p><span>Rooms subtotal</span><strong>KSh <?= number_format($room_amount, 2) ?></strong></p>
          <p><span>Taxes &amp; fees (10%)</span><strong>KSh <?= number_format($taxes_and_fees, 2) ?></strong></p>
          <p class="price-total">
            <span>Total paid</span><strong>KSh <?= number_format($grand_total, 2) ?></strong>
          </p>
        </div>
      </section>

      <section
        class="checkout-card payment-card confirmation-payment"
        aria-labelledby="payment-title">
        <div class="checkout-card-heading">
          <h3 id="payment-title">
            <i class="fa-regular fa-credit-card" aria-hidden="true"></i>
            Payment Details
          </h3>
        </div>

        <div class="confirmation-payment-status">
          <span class="confirmation-payment-icon" aria-hidden="true">
            <i class="fa-solid fa-check"></i>
          </span>
          <div>
            <strong>Paid</strong>
            <span><?= escapeHTML($booking["payment_method"] ?: "M-Pesa (Demo)") ?></span>
          </div>
        </div>

        <div class="confirmation-details">
          <p>
            <span>Booking reference</span>
            <strong>#<?= escapeHTML(implode(", #", $booking_ids)) ?></strong>
          </p>
          <p>
            <span>Transaction reference</span>
            <strong><?= escapeHTML($transaction_reference ?: "Not available") ?></strong>
          </p>
          <p>
            <span>Payment date</span>
            <strong><?= escapeHTML($paid_at) ?></strong>
          </p>
          <p>
            <span>Payment status</span>
            <strong class="confirmation-paid-label">Paid</strong>
          </p>
        </div>

        <p class="confirmation-demo-note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          This is a demo payment confirmation. No real money was transferred.
        </p>

        <div class="confirmation-actions">
          <a class="confirmation-primary-action" href="bookings.php">
            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            View My Bookings
          </a>
          <a class="confirmation-secondary-action" href="index.php">
            Return to Home
          </a>
        </div>
      </section>
    </div>

    <nav class="checkout-progress confirmation-progress" aria-label="Booking progress">
      <ol>
        <li class="progress-complete">
          <span class="progress-marker"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <strong>1. Booking</strong>
          <small>Completed</small>
        </li>
        <li class="progress-complete">
          <span class="progress-marker"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <strong>2. Payment</strong>
          <small>Completed</small>
        </li>
        <li class="progress-current" aria-current="step">
          <span class="progress-marker">3</span>
          <strong>3. Confirmation</strong>
          <small>Current</small>
        </li>
      </ol>
    </nav>

    <p class="checkout-assurance confirmation-assurance">
      <i class="fa-solid fa-envelope" aria-hidden="true"></i>
      Keep your booking and transaction references for your records.
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
</body>

</html>