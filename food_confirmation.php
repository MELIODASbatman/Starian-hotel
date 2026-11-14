<?php

session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
$isLoggedIn = isset($_SESSION["user_id"]);

if (!$isLoggedIn) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . "/php/db.php";

$userId = (int) $_SESSION["user_id"];
$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
  header("Location: orders.php");
  exit;
}

$statement = $conn->prepare(
  "SELECT
      food_orders.id AS order_id,
      food_orders.created_at,
      food_orders.total_amount,
      food_orders.order_status,
      food_orders.payment_method,
      food_orders.payment_status,
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
  error_log("Unable to prepare food confirmation query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your food order confirmation right now.");
}

$statement->bind_param("ii", $orderId, $userId);

if (!$statement->execute()) {
  error_log("Unable to load food order confirmation: " . $statement->error);
  $statement->close();
  http_response_code(500);
  exit("Unable to load your food order confirmation right now.");
}

$result = $statement->get_result();
$order = null;
$items = [];
$itemsSubtotalCents = 0;

while ($row = $result->fetch_assoc()) {
  if ($order === null) {
    $order = [
      "id" => (int) $row["order_id"],
      "created_at" => $row["created_at"],
      "total_amount" => (float) $row["total_amount"],
      "status" => strtolower((string) $row["order_status"]),
      "payment_method" => (string) $row["payment_method"],
      "payment_status" => strtolower((string) $row["payment_status"])
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
    $itemsSubtotalCents += $itemSubtotalCents;
  }
}

$statement->close();
$conn->close();

if ($order === null) {
  http_response_code(404);
  exit("Food order not found or you do not have permission to view it.");
}

if (count($items) === 0) {
  http_response_code(409);
  exit("This food order has no items.");
}

$storedTotalCents = (int) round($order["total_amount"] * 100);
if ($itemsSubtotalCents !== $storedTotalCents) {
  error_log("Food order total does not match its saved item subtotals.");
  http_response_code(409);
  exit("The food order total does not match its items. Please contact the hotel.");
}

$statusLabels = [
  "pending" => "Pending confirmation",
  "confirmed" => "Confirmed",
  "preparing" => "Preparing",
  "completed" => "Completed",
  "cancelled" => "Cancelled"
];
$statusLabel = $statusLabels[$order["status"]] ?? ucfirst($order["status"]);
$isCancelled = $order["status"] === "cancelled";
$isPending = $order["status"] === "pending";
$pageTitle = $isCancelled
  ? "Order Cancelled"
  : ($isPending ? "Order Received" : "Order Update");
$pageMessage = $isCancelled
  ? "This food order has been cancelled."
  : ($isPending
    ? "Your menu order has been received and is awaiting confirmation from hotel staff."
    : "Here is the latest status of your menu order.");
$orderDate = date("d M Y, g:i a", strtotime($order["created_at"]));
$orderTotal = number_format($itemsSubtotalCents / 100, 2);
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
    content="Review your Starian Hotel menu order confirmation and status." />
  <title><?= escapeHTML($pageTitle) ?> | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/style.css?v=4" />
  <link rel="stylesheet" href="css/checkout.css?v=3" />
  <link rel="stylesheet" href="css/foodcheckout.css?v=1" />
</head>

<body class="checkout-body confirmation-body">
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
        <?php if ($isLoggedIn): ?>
          <li>
            <a href="dashboard.php" class="account-link">
              <img src="img/avatars/<?= escapeHTML($avatarFilename) ?>" alt="" class="nav-avatar" />
              <span><?= escapeHTML($fullname) ?></span>
            </a>
          </li>
        <?php else: ?>
          <li><a class="loginup" href="login.php">Login</a></li>
          <li><a class="signup" href="signup.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main class="checkout-main confirmation-main">
    <section class="checkout-heading" aria-labelledby="confirmation-title">
      <div class="checkout-title-row">
        <span aria-hidden="true"></span>
        <h2 id="confirmation-title"><?= escapeHTML($pageTitle) ?></h2>
        <span aria-hidden="true"></span>
      </div>
      <p><?= escapeHTML($pageMessage) ?></p>
    </section>

    <div class="confirmation-success" role="status">
      <span class="confirmation-success-icon" aria-hidden="true">
        <i class="fa-solid <?= $isCancelled ? "fa-xmark" : "fa-check" ?>"></i>
      </span>
      <div>
        <h3><?= $isCancelled ? "Order cancelled" : "Order received" ?></h3>
        <p>
          <?= $isCancelled
            ? "Please contact the hotel if you need help with this order."
            : "Your order has been saved. No payment has been processed." ?>
        </p>
      </div>
    </div>

    <div class="checkout-grid confirmation-grid">
      <section
        class="checkout-card booking-summary"
        aria-labelledby="summary-title">
        <div class="checkout-card-heading">
          <h3 id="summary-title">
            <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            Menu Order
          </h3>
          <span class="confirmation-status<?= $isPending || $isCancelled ? " confirmation-status-pending" : "" ?>">
            <i class="fa-solid <?= $isPending ? "fa-clock" : ($isCancelled ? "fa-circle-xmark" : "fa-circle-check") ?>" aria-hidden="true"></i>
            <?= escapeHTML($statusLabel) ?>
          </span>
        </div>

        <div class="food-order-meta">
          <span>Order #<?= (int) $order["id"] ?></span>
          <time datetime="<?= escapeHTML($order["created_at"]) ?>">
            <?= escapeHTML($orderDate) ?>
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
                src="img/<?= escapeHTML(rawurlencode($imageFilename)) ?>"
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
            <strong>KSh <?= $orderTotal ?></strong>
          </p>
          <p class="price-total">
            <span>Order total</span>
            <strong>KSh <?= $orderTotal ?></strong>
          </p>
        </div>
      </section>

      <section
        class="checkout-card payment-card confirmation-payment"
        aria-labelledby="order-details-title">
        <div class="checkout-card-heading">
          <h3 id="order-details-title">
            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            Order Details
          </h3>
        </div>

        <div class="confirmation-details">
          <p>
            <span>Order reference</span>
            <strong>#<?= (int) $order["id"] ?></strong>
          </p>
          <p>
            <span>Order date</span>
            <strong><?= escapeHTML($orderDate) ?></strong>
          </p>
          <p>
            <span>Order status</span>
            <strong><?= escapeHTML($statusLabel) ?></strong>
          </p>
          <p>
            <span>Payment status</span>
            <strong><?= $order["payment_method"] === "pay_at_hotel"
              ? "Due at hotel"
              : escapeHTML(ucfirst($order["payment_status"])) ?></strong>
          </p>
          <p>
            <span>Payment method</span>
            <strong><?= $order["payment_method"] === "pay_at_hotel"
              ? "Pay at Hotel"
              : escapeHTML(ucfirst($order["payment_method"])) ?></strong>
          </p>
        </div>

        <p class="confirmation-demo-note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          This confirmation records your food order only. No payment was taken.
        </p>

        <div class="confirmation-actions">
          <a class="confirmation-primary-action" href="orders.php">
            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
            View My Orders
          </a>
          <a class="confirmation-secondary-action" href="menu.php">
            Order More Food
          </a>
        </div>
      </section>
    </div>

    <nav class="checkout-progress confirmation-progress" aria-label="Food order progress">
      <ol>
        <li class="progress-complete">
          <span class="progress-marker"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <strong>1. Order</strong>
          <small>Submitted</small>
        </li>
        <li class="progress-current" aria-current="step">
          <span class="progress-marker">2</span>
          <strong>2. Confirmation</strong>
          <small>Current</small>
        </li>
        <li>
          <span class="progress-marker">3</span>
          <strong>3. Preparation</strong>
          <small><?= $order["status"] === "preparing" || $order["status"] === "completed" ? "Started" : "Upcoming" ?></small>
        </li>
      </ol>
    </nav>

    <p class="checkout-assurance confirmation-assurance">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      Your order status may change as hotel staff review and prepare it.
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