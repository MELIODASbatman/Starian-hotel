
<?php

session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

// Standard JSON response
function availabilityResponse($success, $available, $message)
{
  echo json_encode([
    "success" => $success,
    "available" => $available,
    "message" => $message
  ]);

  exit;
}

// STEP 1: Check whether the customer is logged in
if (!isset($_SESSION["user_id"])) {

  http_response_code(401);

  availabilityResponse(
    false,
    false,
    "Please log in before checking room availability."
  );
}

// STEP 2: Check that the account belongs to a customer
if (($_SESSION["role"] ?? "") !== "customer") {

  http_response_code(403);

  availabilityResponse(
    false,
    false,
    "You are not authorized to check room availability."
  );
}

// STEP 3: Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

  http_response_code(405);

  availabilityResponse(
    false,
    false,
    "Invalid request method."
  );
}

// STEP 4: Check the customer's current account status
$userId = (int) $_SESSION["user_id"];

$statusStatement = $conn->prepare(
  "SELECT status
     FROM users
     WHERE id = ? AND role = 'customer'
     LIMIT 1"
);

if (!$statusStatement) {

  error_log("Unable to prepare customer status check: " . $conn->error);

  http_response_code(500);

  availabilityResponse(
    false,
    false,
    "Unable to verify your account right now."
  );
}

$statusStatement->bind_param("i", $userId);

if (!$statusStatement->execute()) {

  error_log("Unable to check customer status: " . $statusStatement->error);

  $statusStatement->close();

  http_response_code(500);

  availabilityResponse(
    false,
    false,
    "Unable to verify your account right now."
  );
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

  availabilityResponse(
    false,
    false,
    "Your account has been deactivated. Please contact the administrator."
  );
}


$room_codes = $_POST["room_codes"] ?? [];
if (!is_array($room_codes)) {
  $room_codes = [];
}
if (count($room_codes) === 0 && isset($_POST["room_option"])) {
  $room_codes[] = $_POST["room_option"];
}
foreach ($room_codes as $roomCode) {
  if (!is_string($roomCode)) {
    http_response_code(400);
    availabilityResponse(false, false, "The selected room list is invalid.");
  }
}
$room_codes = array_map(
  static fn($roomCode) => trim($roomCode),
  $room_codes
);
$room_codes = array_values(array_unique(array_filter($room_codes)));
$check_in = $_POST["check_in"] ?? "";
$check_out = $_POST["check_out"] ?? "";
$arrival_time = $_POST["arrival_time"] ?? "";

if (
  count($room_codes) === 0 ||
  count($room_codes) > 16 ||
  empty($check_in) ||
  empty($check_out) ||
  empty($arrival_time)
) {
  availabilityResponse(false, false, "Please complete all booking fields.");
}

$checkInDate = DateTime::createFromFormat("!Y-m-d", $check_in);
$checkOutDate = DateTime::createFromFormat("!Y-m-d", $check_out);

if (
  !$checkInDate ||
  !$checkOutDate ||
  $checkInDate->format("Y-m-d") !== $check_in ||
  $checkOutDate->format("Y-m-d") !== $check_out
) {
  availabilityResponse(false, false, "Please enter valid check-in and check-out dates.");
}

if ($checkInDate < new DateTime("today")) {
  availabilityResponse(false, false, "Check-in cannot be in the past.");
}

if ($checkOutDate <= $checkInDate) {
  availabilityResponse(false, false, "Check-out must be after check-in.");
}

$time = DateTime::createFromFormat("!H:i", $arrival_time);

if (!$time || $time->format("H:i") !== $arrival_time) {
  availabilityResponse(false, false, "Please enter a valid arrival time.");
}

$placeholders = implode(",", array_fill(0, count($room_codes), "?"));
$sql = $conn->prepare(
  "SELECT id, room_code, room_name, status
     FROM rooms
     WHERE room_code IN ($placeholders)"
);

if (!$sql) {
  error_log("Unable to prepare room availability lookup: " . $conn->error);
  http_response_code(500);
  availabilityResponse(false, false, "Unable to check availability right now.");
}

$roomParameters = [str_repeat("s", count($room_codes))];
foreach ($room_codes as $index => $roomCode) {
  $roomParameters[] = &$room_codes[$index];
}
call_user_func_array([$sql, "bind_param"], $roomParameters);

if (!$sql->execute()) {
  error_log("Unable to load rooms for availability: " . $sql->error);
  $sql->close();
  http_response_code(500);
  availabilityResponse(false, false, "Unable to check availability right now.");
}

$roomResult = $sql->get_result();
$roomsByCode = [];
while ($room = $roomResult->fetch_assoc()) {
  $roomsByCode[$room["room_code"]] = $room;
}
$sql->close();

if (count($roomsByCode) !== count($room_codes)) {
  $conn->close();
  availabilityResponse(false, false, "One or more selected rooms do not exist.");
}

foreach ($room_codes as $roomCode) {
  if ($roomsByCode[$roomCode]["status"] !== "available") {
    $roomName = $roomsByCode[$roomCode]["room_name"];
    $conn->close();
    availabilityResponse(
      true,
      false,
      $roomName . " is currently unavailable."
    );
  }
}

$sql = $conn->prepare(
  "SELECT id
     FROM bookings
     WHERE room_id = ?
       AND booking_status IN ('pending', 'confirmed')
       AND check_in < ?
       AND check_out > ?
     LIMIT 1"
);

if (!$sql) {
  error_log("Unable to prepare booking availability check: " . $conn->error);
  $conn->close();
  http_response_code(500);
  availabilityResponse(false, false, "Unable to check availability right now.");
}

foreach ($room_codes as $roomCode) {
  $roomId = (int) $roomsByCode[$roomCode]["id"];
  $sql->bind_param("iss", $roomId, $check_out, $check_in);

  if (!$sql->execute()) {
    error_log("Unable to check booking availability: " . $sql->error);
    $sql->close();
    $conn->close();
    http_response_code(500);
    availabilityResponse(false, false, "Unable to check availability right now.");
  }

  if ($sql->get_result()->num_rows > 0) {
    $roomName = $roomsByCode[$roomCode]["room_name"];
    $sql->close();
    $conn->close();
    availabilityResponse(
      true,
      false,
      $roomName .
        " is already booked for those dates. Choose another room or dates."
    );
  }
}

$sql->close();
$conn->close();

availabilityResponse(
  true,
  true,
  count($room_codes) === 1
    ? "This room is available for your selected dates."
    : "All " . count($room_codes) . " rooms are available for your selected dates."
);
