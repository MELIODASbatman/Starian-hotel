<?php

session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json");


// 1. Check customer login
if (!isset($_SESSION["user_id"])) {

  http_response_code(401);

  echo json_encode([
    "success" => false,
    "message" => "Please log in to continue."
  ]);

  exit;
}

// 2. Check that the account belongs to a customer
if (($_SESSION["role"] ?? "") !== "customer") {

  http_response_code(403);

  echo json_encode([
    "success" => false,
    "message" => "You are not authorized to process payments."
  ]);

  exit;
}

$user_id = (int) $_SESSION["user_id"];

// 3. Check the customer's current account status
$statusStatement = $conn->prepare(
  "SELECT status
     FROM users
     WHERE id = ? AND role = 'customer'
     LIMIT 1"
);

if (!$statusStatement) {

  error_log("Unable to prepare customer status check: " . $conn->error);

  http_response_code(500);

  echo json_encode([
    "success" => false,
    "message" => "Unable to verify your account right now."
  ]);

  exit;
}

$statusStatement->bind_param("i", $user_id);

if (!$statusStatement->execute()) {

  error_log("Unable to check customer status: " . $statusStatement->error);

  $statusStatement->close();

  http_response_code(500);

  echo json_encode([
    "success" => false,
    "message" => "Unable to verify your account right now."
  ]);

  exit;
}

$customer = $statusStatement->get_result()->fetch_assoc();

$statusStatement->close();

// Reject missing or inactive accounts
if (!$customer || $customer["status"] !== "active") {

  $_SESSION = [];

  if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
      session_name(),
      "",
      time() - 42000,
      $params["path"],
      $params["domain"],
      $params["secure"],
      $params["httponly"]
    );
  }

  session_destroy();

  http_response_code(403);

  echo json_encode([
    "success" => false,
    "message" => "Your account has been deactivated. Please contact the administrator.",
    "redirect" => "../login.php?account=inactive"
  ]);

  exit;
}

// 2. Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode([
    "success" => false,
    "message" => "Invalid request method."
  ]);
  exit;
}

// 3. Read submitted data
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => "Invalid payment request."
  ]);
  exit;
}

$booking_ids = $data["booking_ids"] ?? [];
$payment_method = $data["payment_method"] ?? "";
$phone = $data["phone"] ?? "";

// 4. Validate booking IDs
if (
  !is_array($booking_ids) ||
  count($booking_ids) < 1 ||
  count($booking_ids) > 16
) {
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => "Invalid booking selection."
  ]);
  exit;
}

$booking_ids = array_values(array_unique($booking_ids));

foreach ($booking_ids as $id) {
  if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
    http_response_code(400);
    echo json_encode([
      "success" => false,
      "message" => "Invalid booking ID."
    ]);
    exit;
  }

  if ((int) $id < 1) {
    http_response_code(400);
    echo json_encode([
      "success" => false,
      "message" => "Invalid booking ID."
    ]);
    exit;
  }
}

$booking_ids = array_map("intval", $booking_ids);

// 5. Only M-Pesa demo is currently supported
if ($payment_method !== "mpesa") {
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => "Only M-Pesa demo payments are currently available."
  ]);
  exit;
}

// 6. Validate Kenyan phone number
$phone = preg_replace('/\D/', '', $phone);

if (str_starts_with($phone, "254")) {
  $phone = substr($phone, 3);
} elseif (str_starts_with($phone, "0")) {
  $phone = substr($phone, 1);
}

if (!preg_match('/^[17][0-9]{8}$/', $phone)) {
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => "Enter a valid Kenyan M-Pesa phone number."
  ]);
  exit;
}

$phone = "254" . $phone;

// 7. Begin database transaction
$conn->begin_transaction();

try {

  $placeholders = implode(",", array_fill(0, count($booking_ids), "?"));

  // Lock and retrieve bookings belonging to this customer
  $sql = "
        SELECT id, payment_status, booking_status
        FROM bookings
        WHERE id IN ($placeholders)
          AND user_id = ?
        FOR UPDATE
    ";

  $stmt = $conn->prepare($sql);

  $types = str_repeat("i", count($booking_ids) + 1);
  $params = array_merge($booking_ids, [$user_id]);

  $stmt->bind_param($types, ...$params);

  $stmt->execute();

  $result = $stmt->get_result();

  $foundBookings = [];

  while ($row = $result->fetch_assoc()) {
    $foundBookings[] = $row;
  }

  $stmt->close();

  if (count($foundBookings) !== count($booking_ids)) {
    throw new Exception("One or more bookings could not be found.");
  }

  // 8. Check booking status
  foreach ($foundBookings as $booking) {

    if ($booking["booking_status"] === "cancelled") {
      throw new Exception("Cancelled bookings cannot be paid for.");
    }

    if ($booking["payment_status"] === "paid") {
      throw new Exception("One or more bookings have already been paid.");
    }
  }

  // 9. Generate demo transaction reference
  $transaction_id = "DEMO-" . strtoupper(bin2hex(random_bytes(5)));

  $payment_reference = $transaction_id;

  // 10. Update payment information
  $updateSql = "
        UPDATE bookings
        SET
            payment_status = 'paid',
            payment_method = 'M-Pesa (Demo)',
            transaction_id = ?,
            payment_reference = ?,
            paid_at = NOW()
        WHERE id IN ($placeholders)
          AND user_id = ?
    ";

  $updateStmt = $conn->prepare($updateSql);

  $updateParams = array_merge(
    [$transaction_id, $payment_reference],
    $booking_ids,
    [$user_id]
  );

  $updateTypes = "ss" . str_repeat("i", count($booking_ids) + 1);

  $updateStmt->bind_param($updateTypes, ...$updateParams);

  $updateStmt->execute();

  $updateStmt->close();

  // 11. Commit transaction
  $conn->commit();

  echo json_encode([
    "success" => true,
    "message" => "Demo payment completed successfully. No real money was transferred.",
    "transaction_id" => $transaction_id,
    "phone" => $phone,
    "booking_ids" => $booking_ids
  ]);
} catch (Throwable $e) {

  $conn->rollback();

  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}

$conn->close();
