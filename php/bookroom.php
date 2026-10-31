<?php

session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

// STEP 1: Check whether the guest is logged in
if (!isset($_SESSION["user_id"])) {

  echo json_encode([
    "success" => false,
    "message" => "Please log in before making a booking."
  ]);

  exit;
}

// STEP 2: Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

  echo json_encode([
    "success" => false,
    "message" => "Invalid request method."
  ]);

  exit;
}

// STEP 3: Get the submitted form data
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
    echo json_encode([
      "success" => false,
      "message" => "The selected room list is invalid."
    ]);
    exit;
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

// STEP 4: Check that all required fields were submitted
if (
  count($room_codes) === 0 ||
  count($room_codes) > 16 ||
  empty($check_in) ||
  empty($check_out) ||
  empty($arrival_time)
) {

  echo json_encode([
    "success" => false,
    "message" => "Please complete all booking fields."
  ]);

  exit;
}

// STEP 5: Validate the dates
$checkInDate = DateTime::createFromFormat("!Y-m-d", $check_in);
$checkOutDate = DateTime::createFromFormat("!Y-m-d", $check_out);

if (
  !$checkInDate ||
  !$checkOutDate ||
  $checkInDate->format("Y-m-d") !== $check_in ||
  $checkOutDate->format("Y-m-d") !== $check_out
) {

  echo json_encode([
    "success" => false,
    "message" => "Please enter valid check-in and check-out dates."
  ]);

  exit;
}

$today = new DateTime("today");

if ($checkInDate < $today) {

  echo json_encode([
    "success" => false,
    "message" => "Check-in cannot be in the past."
  ]);

  exit;
}

if ($checkOutDate <= $checkInDate) {

  echo json_encode([
    "success" => false,
    "message" => "Check-out must be after check-in."
  ]);

  exit;
}

// STEP 6: Validate the arrival time
$time = DateTime::createFromFormat("!H:i", $arrival_time);

if (
  !$time ||
  $time->format("H:i") !== $arrival_time
) {

  echo json_encode([
    "success" => false,
    "message" => "Please enter a valid arrival time."
  ]);

  exit;
}

// STEP 7: Load and lock the selected rooms before checking availability.
try {
  if (!$conn->begin_transaction()) {
    throw new RuntimeException("Unable to start booking transaction.");
  }

  $placeholders = implode(",", array_fill(0, count($room_codes), "?"));
  $roomStatement = $conn->prepare(
    "SELECT id, room_code, room_name, price_per_night, status
       FROM rooms
       WHERE room_code IN ($placeholders)
       ORDER BY id
       FOR UPDATE"
  );

  if (!$roomStatement) {
    throw new RuntimeException("Unable to prepare room lookup.");
  }

  $roomParameters = [str_repeat("s", count($room_codes))];
  foreach ($room_codes as $index => $roomCode) {
    $roomParameters[] = &$room_codes[$index];
  }
  call_user_func_array([$roomStatement, "bind_param"], $roomParameters);

  if (!$roomStatement->execute()) {
    throw new RuntimeException("Unable to load selected rooms.");
  }

  $roomResult = $roomStatement->get_result();
  $roomsByCode = [];
  while ($room = $roomResult->fetch_assoc()) {
    $roomsByCode[$room["room_code"]] = $room;
  }
  $roomStatement->close();

  if (count($roomsByCode) !== count($room_codes)) {
    throw new InvalidArgumentException("One or more selected rooms do not exist.");
  }

  $rooms = [];
  foreach ($room_codes as $roomCode) {
    $room = $roomsByCode[$roomCode];
    if ($room["status"] !== "available") {
      throw new InvalidArgumentException(
        $room["room_name"] . " is currently unavailable."
      );
    }

    $rooms[] = $room;
  }

  // STEP 8: Check every selected room for overlapping reservations.
  $availabilityStatement = $conn->prepare(
    "SELECT id
       FROM bookings
       WHERE room_id = ?
         AND booking_status IN ('pending', 'confirmed')
         AND check_in < ?
         AND check_out > ?
       LIMIT 1"
  );

  if (!$availabilityStatement) {
    throw new RuntimeException("Unable to prepare room availability check.");
  }

  foreach ($rooms as $room) {
    $roomId = (int) $room["id"];
    $availabilityStatement->bind_param(
      "iss",
      $roomId,
      $check_out,
      $check_in
    );

    if (!$availabilityStatement->execute()) {
      throw new RuntimeException("Unable to check room availability.");
    }

    if ($availabilityStatement->get_result()->num_rows > 0) {
      throw new InvalidArgumentException(
        $room["room_name"] .
          " is already booked for those dates. Choose another room or dates."
      );
    }
  }
  $availabilityStatement->close();

  // STEP 9: Calculate the stay and insert one booking row per room.
  $total_nights = $checkInDate->diff($checkOutDate)->days;
  $user_id = (int) $_SESSION["user_id"];
  $insertStatement = $conn->prepare(
    "INSERT INTO bookings
      (
          user_id,
          room_id,
          check_in,
          check_out,
          arrival_time,
          total_nights,
          total_price,
          booking_status
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
  );

  if (!$insertStatement) {
    throw new RuntimeException("Unable to prepare booking insert.");
  }

  $booking_ids = [];
  $total_price_cents = 0;
  foreach ($rooms as $room) {
    $room_id = (int) $room["id"];
    $price_per_night_cents = (int) round(
      (float) $room["price_per_night"] * 100
    );
    $room_total_cents = $total_nights * $price_per_night_cents;
    $room_total = $room_total_cents / 100;
    $total_price_cents += $room_total_cents;

    $insertStatement->bind_param(
      "iisssid",
      $user_id,
      $room_id,
      $check_in,
      $check_out,
      $arrival_time,
      $total_nights,
      $room_total
    );

    if (!$insertStatement->execute()) {
      throw new RuntimeException("Unable to save all selected rooms.");
    }

    $booking_ids[] = (int) $conn->insert_id;
  }
  $insertStatement->close();

  if (!$conn->commit()) {
    throw new RuntimeException("Unable to complete booking transaction.");
  }

  $room_names = array_map(
    static fn($room) => $room["room_name"],
    $rooms
  );

  echo json_encode([
    "success" => true,
    "message" => "Your booking request was submitted successfully!",
    "booking_id" => $booking_ids[0],
    "booking_ids" => $booking_ids,
    "room_count" => count($rooms),
    "room_names" => $room_names,
    "check_in" => $check_in,
    "check_out" => $check_out,
    "total_nights" => $total_nights,
    "total_price" => number_format($total_price_cents / 100, 2),
    "booking_status" => "pending"
  ]);
} catch (InvalidArgumentException $error) {
  $conn->rollback();
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => $error->getMessage()
  ]);
} catch (Throwable $error) {
  $conn->rollback();
  error_log("Unable to create room booking: " . $error->getMessage());
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "We could not save your booking. Please try again."
  ]);
}

$conn->close();
