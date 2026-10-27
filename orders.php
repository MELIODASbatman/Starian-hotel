<?php

session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

require_once "php/db.php";

$userId = (int) $_SESSION["user_id"];
$fullname = $_SESSION["fullname"] ?? "Guest";

$orderStatement = $conn->prepare(
  "SELECT
      food_orders.id AS order_id,
      food_orders.created_at,
      food_orders.total_amount,
      food_orders.order_status,
      food_order_items.id AS item_id,
      food_order_items.quantity,
      food_menu.food_name,
      food_menu.image
   FROM food_orders
   LEFT JOIN food_order_items
      ON food_order_items.order_id = food_orders.id
   LEFT JOIN food_menu
      ON food_menu.id = food_order_items.food_id
   WHERE food_orders.user_id = ?
   ORDER BY food_orders.created_at DESC, food_orders.id DESC, food_order_items.id ASC"
);

if (!$orderStatement) {
  error_log("Unable to prepare customer orders query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your orders right now.");
}

$orderStatement->bind_param("i", $userId);

if (!$orderStatement->execute()) {
  error_log("Unable to load customer orders: " . $orderStatement->error);
  http_response_code(500);
  exit("Unable to load your orders right now.");
}

$result = $orderStatement->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
  $orderId = (int) $row["order_id"];

  if (!isset($orders[$orderId])) {
    $orders[$orderId] = [
      "id" => $orderId,
      "created_at" => $row["created_at"],
      "total_amount" => $row["total_amount"],
      "status" => strtolower((string) $row["order_status"]),
      "items" => []
    ];
  }

  if ($row["item_id"] !== null) {
    $orders[$orderId]["items"][] = [
      "name" => $row["food_name"] ?? "Menu item",
      "quantity" => (int) $row["quantity"],
      "image" => $row["image"] ?? ""
    ];
  }
}

$orderStatement->close();
$conn->close();

$statusLabels = [
  "pending" => "Pending",
  "confirmed" => "Confirmed",
  "preparing" => "Preparing",
  "completed" => "Delivered",
  "cancelled" => "Cancelled"
];

$statusCategories = [
  "pending" => "preparing",
  "confirmed" => "preparing",
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
  <meta name="description" content="View and manage your Starian Hotel food orders." />
  <title>My Orders | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=6" />
  <link rel="stylesheet" href="css/dashboard.css?v=2" />
  <link rel="stylesheet" href="css/orders.css?v=1" />
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
            <span><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?></span>
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
        <h3><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?></h3>
        <p>Customer</p>
      </div>
      <nav class="sidebar-nav" aria-label="Account navigation">
        <a href="dashboard.php" class="sidebar-link">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          <span>Dashboard</span>
        </a>
        <a href="bookings.php" class="sidebar-link">
          <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
          <span>My Bookings</span>
        </a>
        <a href="orders.php" class="sidebar-link active" aria-current="page">
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

    <main class="main-info orders-main">
      <div class="orders-heading">
        <div>
          <h2>My Orders</h2>
          <p>View and manage your food orders.</p>
        </div>
        <a class="order-food-link" href="menu.php">
          <i class="fa-solid fa-plus" aria-hidden="true"></i>
          Order Food
        </a>
      </div>

      <div class="order-filters" role="group" aria-label="Filter orders">
        <button class="order-filter active" type="button" data-filter="all" aria-pressed="true">
          All
        </button>
        <button class="order-filter" type="button" data-filter="preparing" aria-pressed="false">
          Preparing
        </button>
        <button class="order-filter" type="button" data-filter="delivered" aria-pressed="false">
          Delivered
        </button>
        <button class="order-filter" type="button" data-filter="cancelled" aria-pressed="false">
          Cancelled
        </button>
      </div>

      <section class="orders-list" aria-label="Your food orders">
        <?php if (count($orders) > 0): ?>
          <?php foreach ($orders as $order): ?>
            <?php
            $status = $order["status"];
            $statusLabel = $statusLabels[$status] ?? ucfirst($status);
            $statusClass = isset($statusLabels[$status]) ? $status : "pending";
            $filterCategory = $statusCategories[$status] ?? "preparing";
            $firstItem = $order["items"][0] ?? null;
            $itemCount = count($order["items"]);
            ?>
            <article class="order-row" data-category="<?= htmlspecialchars($filterCategory, ENT_QUOTES, "UTF-8") ?>">
              <img
                src="<?= htmlspecialchars(($firstItem["image"] ?? "") !== "" ? $firstItem["image"] : "img/grilled chicken.jpg", ENT_QUOTES, "UTF-8") ?>"
                alt="<?= htmlspecialchars($firstItem["name"] ?? "Food order", ENT_QUOTES, "UTF-8") ?>"
                class="order-image"
                loading="lazy">
              <div class="order-details">
                <h3>
                  <?= htmlspecialchars($firstItem["name"] ?? "Food order", ENT_QUOTES, "UTF-8") ?>
                  <?php if ($itemCount > 1): ?>
                    <span class="additional-items">+<?= $itemCount - 1 ?> more</span>
                  <?php endif; ?>
                </h3>
                <p>
                  <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                  <?= htmlspecialchars(date("d M Y", strtotime($order["created_at"])), ENT_QUOTES, "UTF-8") ?>
                  &nbsp; · &nbsp;
                  <?= $itemCount ?> <?= $itemCount === 1 ? "item" : "items" ?>
                </p>
                <strong class="order-total">KSh <?= number_format((float) $order["total_amount"], 2) ?></strong>
              </div>
              <div class="order-status">
                <span class="status <?= htmlspecialchars($statusClass, ENT_QUOTES, "UTF-8") ?>">
                  <?= htmlspecialchars($statusLabel, ENT_QUOTES, "UTF-8") ?>
                </span>
                <details class="order-details-toggle">
                  <summary class="details-btn">View Details</summary>
                  <div class="order-details-popover">
                    <h4>Order #<?= (int) $order["id"] ?></h4>
                    <ul>
                      <?php foreach ($order["items"] as $item): ?>
                        <li>
                          <?= htmlspecialchars($item["name"], ENT_QUOTES, "UTF-8") ?>
                          <span>× <?= (int) $item["quantity"] ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </details>
              </div>
            </article>
          <?php endforeach; ?>
          <p class="orders-empty" id="orders-filter-empty" hidden>
            No orders match this filter.
          </p>
        <?php else: ?>
          <div class="orders-empty">
            <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            <p>You have not placed any food orders yet.</p>
            <a href="menu.php">Explore the menu</a>
          </div>
        <?php endif; ?>
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
  <script src="javascript/orders.js"></script>
</body>

</html>