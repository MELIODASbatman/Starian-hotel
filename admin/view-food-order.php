<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";


// Generate a CSRF token for order status updates
if (empty($_SESSION["food_order_csrf_token"])) {
  $_SESSION["food_order_csrf_token"] = bin2hex(random_bytes(32));
}

// Process order status updates
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $submittedToken = $_POST["csrf_token"] ?? "";
  $sessionToken = $_SESSION["food_order_csrf_token"];

  if (
    !is_string($submittedToken) ||
    !hash_equals($sessionToken, $submittedToken)
  ) {
    die("Invalid request. Please refresh the page and try again.");
  }

  $postedOrderId = filter_var(
    $_POST["order_id"] ?? "",
    FILTER_VALIDATE_INT
  );

  $newStatus = $_POST["order_status"] ?? "";

  $allowedStatuses = [
    "pending",
    "preparing",
    "ready",
    "delivered"
  ];

  if (
    !$postedOrderId ||
    $postedOrderId < 1 ||
    !in_array($newStatus, $allowedStatuses, true)
  ) {
    die("Invalid order information.");
  }

  $updateStmt = $conn->prepare("
        UPDATE food_orders
        SET order_status = ?
        WHERE id = ?
    ");

  if (!$updateStmt) {
    die("Unable to prepare order status update.");
  }

  $updateStmt->bind_param("si", $newStatus, $postedOrderId);

  if ($updateStmt->execute()) {

    if ($updateStmt->affected_rows >= 0) {
      $updateStmt->close();

      header(
        "Location: view-food-order.php?id=" .
          $postedOrderId .
          "&status_updated=1"
      );
      exit;
    }
  }

  $updateStmt->close();
  die("Unable to update the order status.");
}


// Get the order ID
$orderId = filter_var(
  $_GET["id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$orderId || $orderId < 1) {
  die("Invalid food order ID.");
}

// Fetch the order and customer details
$orderStmt = $conn->prepare("
    SELECT
        fo.id,
        fo.user_id,
        fo.total_amount,
        fo.order_status,
        fo.payment_status,
        fo.payment_method,
        fo.transaction_id,
        fo.payment_reference,
        fo.paid_at,
        fo.created_at,
        u.fullname,
        u.email,
        u.phone
    FROM food_orders fo
    LEFT JOIN users u ON fo.user_id = u.id
    WHERE fo.id = ?
");

$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();

$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

$orderStmt->close();

if (!$order) {
  die("Food order not found.");
}

// Fetch individual food items
$itemsStmt = $conn->prepare("
    SELECT
        foi.food_id,
        foi.quantity,
        foi.price,
        foi.subtotal,
        fm.food_name
    FROM food_order_items foi
    LEFT JOIN food_menu fm ON foi.food_id = fm.id
    WHERE foi.order_id = ?
");

$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();

$itemsResult = $itemsStmt->get_result();
?>


<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="View customer food order details">
  <title>View Food Order | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="admin.css">
</head>

<body>

  <div class="admin-shell">

    <input class="menu-checkbox" type="checkbox" id="admin-menu">

    <aside class="sidebar">

      <a class="brand" href="index.php">
        <img src="../img/starianlogo.png" alt="">
        <span class="brand-name">Starian <span>Hotel</span></span>
      </a>

      <div class="admin-identity">
        <span class="admin-avatar">
          <i class="fa-solid fa-user-shield"></i>
        </span>
        <span>
          <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
          <small>Administrator</small>
        </span>
      </div>

      <nav class="side-nav" aria-label="Admin navigation">

        <a href="index.php">
          <i class="fa-solid fa-house"></i>Dashboard
        </a>

        <a href="rooms.php">
          <i class="fa-solid fa-bed"></i>Rooms
        </a>

        <a href="menu-orders.php">
          <i class="fa-solid fa-utensils"></i>Menu / Orders
        </a>

        <a class="active" href="orders.php">
          <i class="fa-solid fa-receipt"></i>Food Orders
        </a>

        <a href="bookings.php">
          <i class="fa-regular fa-calendar-check"></i>Bookings
        </a>

        <a href="customers.php">
          <i class="fa-solid fa-users"></i>Customers
        </a>

        <a href="staff.php">
          <i class="fa-solid fa-user-group"></i>Staff
        </a>

        <a href="reports.php">
          <i class="fa-solid fa-chart-column"></i>Reports
        </a>

        <a href="#">
          <i class="fa-solid fa-gear"></i>Settings
        </a>

      </nav>

      <p class="sidebar-motto">
        Better Service
        <span>Better Stays</span>
      </p>

    </aside>

    <div class="workspace">

      <header class="topbar">

        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu">
          <i class="fa-solid fa-bars"></i>
        </label>

        <span class="topbar-spacer"></span>

        <a class="topbar-icon" href="#" aria-label="Notifications">
          <i class="fa-regular fa-bell"></i>
          <span class="notification-dot">3</span>
        </a>

        <div class="top-admin">
          <span class="admin-avatar">
            <i class="fa-solid fa-user-shield"></i>
          </span>
          <span>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
            <small>Administrator</small>
          </span>
        </div>

        <form class="admin-logout-form" method="post" action="../logout.php">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>

      </header>

      <main class="page-content">

        <div class="page-heading">

          <div>
            <h1>Food Order #<?php echo (int) $order["id"]; ?></h1>
            <p>View customer order information and purchased items.</p>
          </div>

          <div class="heading-actions">
            <a href="orders.php" class="button-primary">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Orders
            </a>
          </div>

        </div>

        <?php if (($_GET["status_updated"] ?? "") === "1"): ?>
          <p class="form-message success">
            Order status updated successfully!
          </p>
        <?php endif; ?>

        <section class="panel">

          <div class="panel-heading">
            <h2>Customer Information</h2>
          </div>

          <div class="panel-body">

            <p>
              <strong>Customer:</strong>
              <?php echo htmlspecialchars($order["fullname"] ?? "Unknown Customer", ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
              <strong>Email:</strong>
              <?php echo htmlspecialchars($order["email"] ?? "Not available", ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
              <strong>Phone:</strong>
              <?php echo htmlspecialchars($order["phone"] ?? "Not available", ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
              <strong>Order Date:</strong>
              <?php echo htmlspecialchars($order["created_at"] ?? "", ENT_QUOTES, "UTF-8"); ?>
            </p>

          </div>

        </section>

        <section class="panel">

          <div class="panel-heading">
            <h2>Ordered Food Items</h2>
          </div>

          <div class="table-wrap">

            <table class="data-table">

              <thead>
                <tr>
                  <th>Food Item</th>
                  <th>Unit Price (KSh)</th>
                  <th>Quantity</th>
                  <th>Subtotal (KSh)</th>
                </tr>
              </thead>

              <tbody>

                <?php if ($itemsResult->num_rows > 0): ?>

                  <?php while ($food = $itemsResult->fetch_assoc()): ?>

                    <tr>

                      <td>
                        <?php echo htmlspecialchars(
                          $food["food_name"] ?? "Deleted Menu Item",
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        KSh <?php echo number_format((float) $food["price"], 2); ?>
                      </td>

                      <td>
                        <?php echo (int) $food["quantity"]; ?>
                      </td>

                      <td>
                        KSh <?php echo number_format((float) $food["subtotal"], 2); ?>
                      </td>

                    </tr>

                  <?php endwhile; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="4">No food items found for this order.</td>
                  </tr>

                <?php endif; ?>

              </tbody>

            </table>

          </div>

          <div class="panel-body">

            <h3>
              Total Amount:
              KSh <?php echo number_format((float) $order["total_amount"], 2); ?>
            </h3>

          </div>

        </section>

        <section class="panel">

          <div class="panel-heading">
            <h2>Payment Information</h2>
          </div>

          <div class="panel-body">

            <p>
              <strong>Payment Method:</strong>
              <?php echo htmlspecialchars(
                ucfirst(str_replace("_", " ", $order["payment_method"] ?? "Not specified")),
                ENT_QUOTES,
                "UTF-8"
              ); ?>
            </p>

            <p>
              <strong>Payment Status:</strong>
              <?php echo htmlspecialchars(
                ucfirst(str_replace("_", " ", $order["payment_status"] ?? "Unknown")),
                ENT_QUOTES,
                "UTF-8"
              ); ?>
            </p>

            <p>
              <strong>Transaction ID:</strong>
              <?php echo htmlspecialchars($order["transaction_id"] ?? "Not available", ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
              <strong>Payment Reference:</strong>
              <?php echo htmlspecialchars($order["payment_reference"] ?? "Not available", ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
              <strong>Paid At:</strong>
              <?php echo htmlspecialchars($order["paid_at"] ?? "Not yet paid", ENT_QUOTES, "UTF-8"); ?>
            </p>

          </div>

        </section>

        <section class="panel">

          <div class="panel-heading">
            <h2>Order Status</h2>
          </div>


          <div class="panel-body">

            <p>
              <strong>Current Status:</strong>
              <?php echo htmlspecialchars(
                ucfirst(str_replace("_", " ", $order["order_status"] ?? "Pending")),
                ENT_QUOTES,
                "UTF-8"
              ); ?>
            </p>

            <form method="POST" action="view-food-order.php?id=<?php echo (int) $order["id"]; ?>">

              <input
                type="hidden"
                name="order_id"
                value="<?php echo (int) $order["id"]; ?>">

              <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars(
                          $_SESSION["food_order_csrf_token"],
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>">

              <div class="room-form-field">
                <label for="order_status">Update Order Status</label>

                <select id="order_status" name="order_status" required>

                  <?php
                  $statuses = [
                    "pending" => "Pending",
                    "preparing" => "Preparing",
                    "ready" => "Ready",
                    "delivered" => "Delivered"
                  ];

                  foreach ($statuses as $value => $label):
                  ?>

                    <option
                      value="<?php echo $value; ?>"
                      <?php echo ($order["order_status"] === $value) ? "selected" : ""; ?>>
                      <?php echo $label; ?>
                    </option>

                  <?php endforeach; ?>

                </select>
              </div>

              <button type="submit" class="button-primary">
                <i class="fa-solid fa-save"></i>
                Update Status
              </button>

            </form>

          </div>


        </section>

      </main>

    </div>

  </div>

  <?php
  $itemsStmt->close();
  ?>

</body>

</html>