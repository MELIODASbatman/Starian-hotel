<?php

session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
require_once __DIR__ . "/php/db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$user_id = (int) $_SESSION["user_id"];
$order_id = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$order_id || $order_id < 1) {
  header("Location: orders.php");
  exit;
}

$statement = $conn->prepare(
  "SELECT
      food_orders.id AS order_id,
      food_orders.created_at,
      food_orders.total_amount,
      food_orders.order_status,
      food_order_items.id AS item_id,
      food_order_items.quantity,
      food_order_items.price AS unit_price,
      food_order_items.subtotal,
      food_menu.food_name,
      food_menu.image
   FROM food_orders
   LEFT JOIN food_order_items
      ON food_order_items.order_id = food_orders.id
   LEFT JOIN food_menu
      ON food_menu.id = food_order_items.food_id
   WHERE food_orders.id = ? AND food_orders.user_id = ?
   ORDER BY food_order_items.id ASC"
);

if (!$statement) {
  error_log("Unable to prepare food checkout query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your food order right now.");
}

$statement->bind_param("ii", $order_id, $user_id);

if (!$statement->execute()) {
  error_log("Unable to load food checkout order: " . $statement->error);
  $statement->close();
  http_response_code(500);
  exit("Unable to load your food order right now.");
}

$result = $statement->get_result();
$order = null;
$items = [];
$items_subtotal_cents = 0;

while ($row = $result->fetch_assoc()) {
  if ($order === null) {
    $order = [
      "id" => (int) $row["order_id"],
      "created_at" => $row["created_at"],
      "total_amount" => (float) $row["total_amount"],
      "status" => strtolower((string) $row["order_status"])
    ];
  }

  if ($row["item_id"] !== null) {
    $itemSubtotalCents = (int) round((float) $row["subtotal"] * 100);
    $items[] = [
      "name" => $row["food_name"] ?? "Menu item",
      "quantity" => (int) $row["quantity"],
      "unit_price" => (float) $row["unit_price"],
      "subtotal" => $itemSubtotalCents / 100,
      "image" => $row["image"] ?? ""
    ];
    $items_subtotal_cents += $itemSubtotalCents;
  }
}

$statement->close();
$conn->close();

if ($order === null) {
  http_response_code(404);
  exit("Food order not found or you do not have permission to view it.");
}

if ($order["status"] === "cancelled") {
  http_response_code(409);
  exit("This food order has been cancelled and cannot be paid for.");
}

if (count($items) === 0) {
  http_response_code(409);
  exit("This food order has no items to pay for.");
}

$stored_total_cents = (int) round($order["total_amount"] * 100);
if ($items_subtotal_cents !== $stored_total_cents) {
  error_log("Food order total does not match its saved item subtotals.");
  http_response_code(409);
  exit("The food order total does not match its items. Please contact the hotel.");
}

$order_total_display = number_format($items_subtotal_cents / 100, 2);
$fullname = $_SESSION["fullname"] ?? "My Account";

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
    content="Review your Starian Hotel food order and payment options." />
  <title>Food Order Payment | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/style.css?v=4" />
  <link rel="stylesheet" href="css/checkout.css?v=2" />
  <link rel="stylesheet" href="css/foodcheckout.css?v=1" />
</head>

<body class="checkout-body">
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
            <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" class="nav-avatar" />
            <span><?= escapeHTML($fullname) ?></span>
          </a>
        </li>
      </ul>
    </nav>
  </header>

  <main class="checkout-main">
    <section class="checkout-heading" aria-labelledby="checkout-title">
      <div class="checkout-title-row">
        <span aria-hidden="true"></span>
        <h2 id="checkout-title">Complete Your Food Order</h2>
        <span aria-hidden="true"></span>
      </div>
      <p>Review your order and choose a payment method.</p>
    </section>

    <div class="checkout-grid">
      <section
        class="checkout-card booking-summary"
        aria-labelledby="summary-title">
        <div class="checkout-card-heading">
          <h3 id="summary-title">
            <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            Order Summary
          </h3>
          <a class="edit-booking" href="menu.php">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Add Items
          </a>
        </div>

        <div class="food-order-meta">
          <span>Order #<?= (int) $order["id"] ?></span>
          <time datetime="<?= escapeHTML($order["created_at"]) ?>">
            <?= escapeHTML(date("d M Y, g:i a", strtotime($order["created_at"]))) ?>
          </time>
        </div>

        <ul class="food-order-items" aria-label="Items in your order">
          <?php foreach ($items as $item): ?>
            <?php
            $imageFilename = $item["image"] !== ""
              ? rawurldecode(basename($item["image"]))
              : "grilled chicken.jpg";
            ?>
            <li class="food-order-item">
              <img
                src="img/<?= escapeHTML($imageFilename) ?>"
                alt=""
                class="food-order-image"
                loading="lazy" />
              <div class="food-order-item-details">
                <h4><?= escapeHTML($item["name"]) ?></h4>
                <p>
                  <?= (int) $item["quantity"] ?> × KSh
                  <?= number_format($item["unit_price"], 2) ?>
                </p>
              </div>
              <strong class="food-order-subtotal">
                KSh <?= number_format($item["subtotal"], 2) ?>
              </strong>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="price-breakdown">
          <p>
            <span>Food subtotal</span>
            <strong>KSh <?= $order_total_display ?></strong>
          </p>
          <p class="price-total">
            <span>Total</span>
            <strong>KSh <?= $order_total_display ?></strong>
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
              <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
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
              <p>Enter your M-Pesa phone number to continue.</p>
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
              <p>Enter the details printed on your payment card.</p>
              <label class="payment-field" for="card-number">Card number</label>
              <input
                class="payment-input"
                type="text"
                id="card-number"
                inputmode="numeric"
                autocomplete="cc-number"
                placeholder="1234 5678 9012 3456"
                maxlength="23" />
              <div class="card-expiry-row">
                <div>
                  <label class="payment-field" for="card-expiry">Expiry date</label>
                  <input
                    class="payment-input"
                    type="text"
                    id="card-expiry"
                    autocomplete="cc-exp"
                    placeholder="MM / YY"
                    maxlength="7" />
                </div>
                <div>
                  <label class="payment-field" for="card-cvc">Security code</label>
                  <input
                    class="payment-input"
                    type="password"
                    id="card-cvc"
                    inputmode="numeric"
                    autocomplete="cc-csc"
                    placeholder="CVC"
                    maxlength="4" />
                </div>
              </div>
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

        <button
          class="pay-button"
          id="pay-button"
          type="button"
          data-demo-message="Demo mode: no payment was made, no payment details were submitted, and your food order remains pending.">
          Pay KSh <?= $order_total_display ?>
        </button>
        <p
          class="checkout-feedback"
          id="checkout-feedback"
          role="status"
          aria-live="polite"
          hidden></p>
        <p class="payment-security">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          This demo checkout does not process payments.
        </p>

        <div class="terms-agreement">
          <input
            type="checkbox"
            id="agree-terms"
            data-required-message="Please agree to the food order and cancellation terms to continue." />
          <label for="agree-terms">
            I agree to the food order and
            <a href="contact.php">cancellation terms</a>.
          </label>
        </div>
      </section>
    </div>

    <nav class="checkout-progress" aria-label="Food order progress">
      <ol>
        <li class="progress-complete">
          <span class="progress-marker"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <strong>1. Order</strong>
          <small>Submitted</small>
        </li>
        <li class="progress-current" aria-current="step">
          <span class="progress-marker">2</span>
          <strong>2. Payment</strong>
          <small>Current</small>
        </li>
        <li>
          <span class="progress-marker">3</span>
          <strong>3. Preparation</strong>
          <small>Upcoming</small>
        </li>
      </ol>
    </nav>

    <p class="checkout-assurance">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      This demo does not process payment or update your order status.
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
  <script src="javascript/checkout.js?v=2"></script>
</body>

</html>