
<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: orders.php");
  exit;
}

// Verify CSRF token
$sessionToken = $_SESSION["delivery_status_csrf_token"] ?? "";
$submittedToken = $_POST["csrf_token"] ?? "";

if (
  $sessionToken === "" ||
  !hash_equals($sessionToken, $submittedToken)
) {
  header("Location: orders.php?delivery_update=invalid");
  exit;
}

// Get and validate order ID
$orderId = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);

// Get and validate delivery status
$deliveryStatus = $_POST["delivery_status"] ?? "";

$allowedStatuses = [
  "pending",
  "preparing",
  "ready",
  "delivered"
];

if (
  !$orderId ||
  $orderId < 1 ||
  !in_array($deliveryStatus, $allowedStatuses, true)
) {
  header("Location: orders.php?delivery_update=invalid");
  exit;
}

// Update delivery status
$stmt = $conn->prepare("
    UPDATE food_orders
    SET delivery_status = ?
    WHERE id = ?
");

if (!$stmt) {
  error_log("Delivery status prepare error: " . $conn->error);
  header("Location: orders.php?delivery_update=error");
  exit;
}

$stmt->bind_param("si", $deliveryStatus, $orderId);

if ($stmt->execute()) {
  $stmt->close();

  header("Location: view-food-order.php?id=" . $orderId . "&delivery_update=success");
  exit;
}

error_log("Delivery status update error: " . $stmt->error);

$stmt->close();

header("Location: orders.php?delivery_update=error");
exit;
