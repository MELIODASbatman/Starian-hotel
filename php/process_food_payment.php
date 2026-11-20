<?php

session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/db.php";

function respondFoodPayment(int $statusCode, array $response): void
{
  http_response_code($statusCode);
  echo json_encode($response);
  exit;
}

if (!isset($_SESSION["user_id"])) {
  respondFoodPayment(401, [
    "success" => false,
    "message" => "Please log in to continue."
  ]);
}

if (($_SESSION["role"] ?? "") !== "customer") {
  respondFoodPayment(403, [
    "success" => false,
    "message" => "You are not authorized to process food payments."
  ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  respondFoodPayment(405, [
    "success" => false,
    "message" => "Invalid request method."
  ]);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
  respondFoodPayment(400, [
    "success" => false,
    "message" => "Invalid payment request."
  ]);
}

$submittedToken = $input["csrf_token"] ?? "";
$sessionToken = $_SESSION["food_payment_csrf_token"] ?? "";

if (
  !is_string($submittedToken) ||
  !is_string($sessionToken) ||
  $sessionToken === "" ||
  !hash_equals($sessionToken, $submittedToken)
) {
  respondFoodPayment(403, [
    "success" => false,
    "message" => "Your payment session has expired. Refresh the page and try again."
  ]);
}

$orderId = filter_var($input["order_id"] ?? null, FILTER_VALIDATE_INT);
$phone = $input["phone"] ?? "";

if (!$orderId || $orderId < 1 || !is_string($phone)) {
  respondFoodPayment(400, [
    "success" => false,
    "message" => "Invalid food order or phone number."
  ]);
}

$phone = preg_replace("/\D+/", "", $phone);

if (substr($phone, 0, 3) === "254") {
  $phone = substr($phone, 3);
} elseif (substr($phone, 0, 1) === "0") {
  $phone = substr($phone, 1);
}

if (!preg_match("/^[17][0-9]{8}$/", $phone)) {
  respondFoodPayment(400, [
    "success" => false,
    "message" => "Enter a valid Kenyan M-Pesa phone number."
  ]);
}

$userId = (int) $_SESSION["user_id"];
$statusStatement = $conn->prepare("
    SELECT status
    FROM users
    WHERE id = ? AND role = 'customer'
    LIMIT 1
");

if (!$statusStatement) {
  error_log("Unable to prepare food payment account check: " . $conn->error);
  respondFoodPayment(500, [
    "success" => false,
    "message" => "Unable to verify your account right now."
  ]);
}

$statusStatement->bind_param("i", $userId);

if (!$statusStatement->execute()) {
  error_log("Unable to check food payment account: " . $statusStatement->error);
  $statusStatement->close();
  respondFoodPayment(500, [
    "success" => false,
    "message" => "Unable to verify your account right now."
  ]);
}

$customer = $statusStatement->get_result()->fetch_assoc();
$statusStatement->close();

if (!$customer || $customer["status"] !== "active") {
  respondFoodPayment(403, [
    "success" => false,
    "message" => "Your account is inactive. Please contact the hotel administrator."
  ]);
}

$transactionOpen = false;

try {
  if (!$conn->begin_transaction()) {
    throw new RuntimeException("Unable to start food payment transaction.");
  }
  $transactionOpen = true;

  $orderStatement = $conn->prepare("
      SELECT id, order_status, payment_status, payment_method, transaction_id
      FROM food_orders
      WHERE id = ? AND user_id = ?
      LIMIT 1
      FOR UPDATE
  ");

  if (!$orderStatement) {
    throw new RuntimeException("Unable to prepare food order payment lookup.");
  }

  $orderStatement->bind_param("ii", $orderId, $userId);

  if (!$orderStatement->execute()) {
    $error = $orderStatement->error;
    $orderStatement->close();
    throw new RuntimeException("Unable to load food order for payment: " . $error);
  }

  $order = $orderStatement->get_result()->fetch_assoc();
  $orderStatement->close();

  if (!$order) {
    $conn->rollback();
    $transactionOpen = false;
    respondFoodPayment(404, [
      "success" => false,
      "message" => "Food order not found."
    ]);
  }

  if ($order["order_status"] === "cancelled") {
    $conn->rollback();
    $transactionOpen = false;
    respondFoodPayment(409, [
      "success" => false,
      "message" => "This food order has been cancelled and cannot be paid for."
    ]);
  }

  if (!in_array($order["payment_method"], ["mpesa", "M-Pesa (Demo)"], true)) {
    $conn->rollback();
    $transactionOpen = false;
    respondFoodPayment(409, [
      "success" => false,
      "message" => "This order is not using M-Pesa demo checkout."
    ]);
  }

  if ($order["payment_status"] === "paid") {
    $conn->commit();
    $transactionOpen = false;
    respondFoodPayment(200, [
      "success" => true,
      "message" => "This demo payment has already been completed.",
      "order_id" => (int) $order["id"],
      "transaction_id" => $order["transaction_id"]
    ]);
  }

  if ($order["payment_status"] !== "pending") {
    $conn->rollback();
    $transactionOpen = false;
    respondFoodPayment(409, [
      "success" => false,
      "message" => "This food order is not awaiting M-Pesa payment."
    ]);
  }

  $transactionId = "DEMO-" . strtoupper(bin2hex(random_bytes(5)));
  $updateStatement = $conn->prepare("
      UPDATE food_orders
      SET
          payment_status = 'paid',
          payment_method = 'M-Pesa (Demo)',
          transaction_id = ?,
          payment_reference = ?,
          paid_at = NOW()
      WHERE id = ? AND user_id = ? AND payment_status = 'pending'
  ");

  if (!$updateStatement) {
    throw new RuntimeException("Unable to prepare food payment update.");
  }

  $updateStatement->bind_param(
    "ssii",
    $transactionId,
    $transactionId,
    $orderId,
    $userId
  );

  if (!$updateStatement->execute() || $updateStatement->affected_rows !== 1) {
    $error = $updateStatement->error;
    $updateStatement->close();
    throw new RuntimeException("Unable to record food payment: " . $error);
  }

  $updateStatement->close();

  if (!$conn->commit()) {
    throw new RuntimeException("Unable to complete food payment transaction.");
  }
  $transactionOpen = false;

  respondFoodPayment(200, [
    "success" => true,
    "message" => "Demo M-Pesa payment completed. No real money was transferred.",
    "order_id" => (int) $orderId,
    "transaction_id" => $transactionId
  ]);
} catch (Throwable $error) {
  if ($transactionOpen) {
    $conn->rollback();
  }

  error_log("Food payment processing failed: " . $error->getMessage());
  respondFoodPayment(500, [
    "success" => false,
    "message" => "Unable to complete demo payment right now. Please try again."
  ]);
}
