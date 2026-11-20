<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Search and filter values
$search = trim($_GET["search"] ?? "");
$orderStatus = $_GET["order_status"] ?? "";
$paymentStatus = $_GET["payment_status"] ?? "";

// Allowed filter values
$allowedOrderStatuses = [
  "pending",
  "preparing",
  "ready",
  "delivered"
];

$allowedPaymentStatuses = [
  "pending",
  "unpaid",
  "paid",
  "failed"
];

// Validate filter values
if (!in_array($orderStatus, $allowedOrderStatuses, true)) {
  $orderStatus = "";
}

if (!in_array($paymentStatus, $allowedPaymentStatuses, true)) {
  $paymentStatus = "";
}

// Build the orders query
$sql = "
    SELECT
        fo.id,
        fo.user_id,
        fo.total_amount,
        fo.order_status,
        fo.payment_status,
        fo.payment_method,
        fo.created_at,
        u.fullname,
        COALESCE(SUM(foi.quantity), 0) AS total_items
    FROM food_orders fo
    LEFT JOIN users u ON fo.user_id = u.id
    LEFT JOIN food_order_items foi ON fo.id = foi.order_id
    WHERE 1 = 1
";

$params = [];
$types = "";

// Search by Order ID or customer name
if ($search !== "") {
  $sql .= " AND (CAST(fo.id AS CHAR) LIKE ? OR u.fullname LIKE ?)";
  $searchTerm = "%" . $search . "%";

  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= "ss";
}

// Filter by order status
if ($orderStatus !== "") {
  $sql .= " AND fo.order_status = ?";
  $params[] = $orderStatus;
  $types .= "s";
}

// Filter by payment status
if ($paymentStatus !== "") {
  $sql .= " AND fo.payment_status = ?";
  $params[] = $paymentStatus;
  $types .= "s";
}

$sql .= "
    GROUP BY
        fo.id, fo.user_id, fo.total_amount, fo.order_status,
        fo.payment_status, fo.payment_method, fo.created_at, u.fullname
    ORDER BY fo.created_at DESC, fo.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
  die("Error preparing orders query: " . $conn->error);
}

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$ordersResult = $stmt->get_result();
$stmt->close();
?>


<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage customer food orders">
  <title>Food Orders | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
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

        <a class="active" href="orders.php" aria-current="page">
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
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a>

        <a href="reports.php">
          <i class="fa-solid fa-chart-column"></i>Reports
        </a>
        <a href="contact-messages.php">
          <i class="fa-regular fa-envelope"></i>Contact Messages
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

        <?php require __DIR__ . "/includes/notification-widget.php"; ?>

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
            <h1>Food Orders</h1>
            <p>View and manage customer food orders.</p>
          </div>
        </div>

        <section class="panel">

          <div class="panel-heading">
            <h2>All Customer Orders</h2>
            <span>
              Total Orders: <?php echo $ordersResult->num_rows; ?>
            </span>
          </div>

          <form method="GET" action="orders.php" class="order-filter-form">

            <div class="room-form-field">
              <label for="search">Search Orders</label>
              <input
                type="text"
                id="search"
                name="search"
                placeholder="Search by Order ID or customer name"
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="room-form-field">
              <label for="order_status">Order Status</label>
              <select name="order_status" id="order_status">
                <option value="">All Order Statuses</option>
                <option value="pending" <?php echo $orderStatus === "pending" ? "selected" : ""; ?>>Pending</option>
                <option value="preparing" <?php echo $orderStatus === "preparing" ? "selected" : ""; ?>>Preparing</option>
                <option value="ready" <?php echo $orderStatus === "ready" ? "selected" : ""; ?>>Ready</option>
                <option value="delivered" <?php echo $orderStatus === "delivered" ? "selected" : ""; ?>>Delivered</option>
              </select>
            </div>

            <div class="room-form-field">
              <label for="payment_status">Payment Status</label>
              <select name="payment_status" id="payment_status">
                <option value="">All Payment Statuses</option>
                <option value="pending" <?php echo $paymentStatus === "pending" ? "selected" : ""; ?>>Pending</option>
                <option value="unpaid" <?php echo $paymentStatus === "unpaid" ? "selected" : ""; ?>>Unpaid</option>
                <option value="paid" <?php echo $paymentStatus === "paid" ? "selected" : ""; ?>>Paid</option>
                <option value="failed" <?php echo $paymentStatus === "failed" ? "selected" : ""; ?>>Failed</option>
              </select>
            </div>

            <button type="submit" class="button-primary">
              <i class="fa-solid fa-filter"></i> Apply Filters
            </button>

            <a href="orders.php" class="button-secondary">Clear Filters</a>

          </form>

          <div class="table-wrap">


            <table class="data-table">

              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total (KSh)</th>
                  <th>Payment Method</th>
                  <th>Payment Status</th>
                  <th>Order Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>

                <?php if ($ordersResult->num_rows > 0): ?>

                  <?php while ($order = $ordersResult->fetch_assoc()): ?>

                    <tr>

                      <td>
                        #<?php echo (int) $order["id"]; ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars(
                          $order["fullname"] ?? "Unknown Customer",
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        <?php
                        $itemCount = (int) $order["total_items"];
                        echo $itemCount . ($itemCount === 1 ? " item" : " items");
                        ?>
                      </td>

                      <td>
                        KSh <?php echo number_format((float) $order["total_amount"], 2); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars(
                          ucfirst(str_replace("_", " ", $order["payment_method"] ?? "Not specified")),
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars(
                          ucfirst(str_replace("_", " ", $order["payment_status"] ?? "Unknown")),
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars(
                          ucfirst(str_replace("_", " ", $order["order_status"] ?? "Pending")),
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars(
                          $order["created_at"] ?? "",
                          ENT_QUOTES,
                          "UTF-8"
                        ); ?>
                      </td>

                      <td>
                        <span class="badge">
                          <a class="icon-button"
                            title="View details"
                            href="view-food-order.php?id=<?php echo (int) $order['id']; ?>">
                            <i class="fa-solid fa-eye"></i>
                          </a>
                        </span>
                      </td>

                    </tr>

                  <?php endwhile; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="9">
                      No food orders have been placed yet.
                    </td>
                  </tr>

                <?php endif; ?>

              </tbody>

            </table>

          </div>

        </section>

      </main>

    </div>

  </div>

</body>

</html>