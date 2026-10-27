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
$room_code = trim($_POST["room_option"] ?? "");
$check_in = $_POST["check_in"] ?? "";
$check_out = $_POST["check_out"] ?? "";
$arrival_time = $_POST["arrival_time"] ?? "";

// STEP 4: Check that all required fields were submitted
if (
  empty($room_code) ||
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

// STEP 7: Find the selected room and retrieve its price
$sql = $conn->prepare(
  "SELECT id, room_name, price_per_night, status
     FROM rooms
     WHERE room_code = ?"
);

$sql->bind_param("s", $room_code);
$sql->execute();

$result = $sql->get_result();

if ($result->num_rows === 0) {

  echo json_encode([
    "success" => false,
    "message" => "The selected room does not exist."
  ]);

  $sql->close();
  $conn->close();

  exit;
}

$room = $result->fetch_assoc();

$sql->close();

// STEP 8: Check whether the room is available for maintenance
if ($room["status"] !== "available") {

  echo json_encode([
    "success" => false,
    "message" => "This room is currently unavailable."
  ]);

  $conn->close();

  exit;
}

// STEP 9: Check for overlapping reservations
$sql = $conn->prepare(
  "SELECT id
     FROM bookings
     WHERE room_id = ?
       AND booking_status IN ('pending', 'confirmed')
       AND check_in < ?
       AND check_out > ?
     LIMIT 1"
);

$sql->bind_param(
  "iss",
  $room["id"],
  $check_out,
  $check_in
);

$sql->execute();

$result = $sql->get_result();

if ($result->num_rows > 0) {

  echo json_encode([
    "success" => false,
    "message" => "Sorry, this room is already booked for those dates. Please choose another room or dates."
  ]);

  $sql->close();
  $conn->close();

  exit;
}

$sql->close();

// STEP 10: Calculate the number of nights
$total_nights = $checkInDate->diff($checkOutDate)->days;

// Retrieve the room price from the database
$price_per_night = (float) $room["price_per_night"];

$total_price = $total_nights * $price_per_night;

// STEP 11: Save the booking
$user_id = (int) $_SESSION["user_id"];
$room_id = (int) $room["id"];

$sql = $conn->prepare(
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

$sql->bind_param(
  "iisssid",
  $user_id,
  $room_id,
  $check_in,
  $check_out,
  $arrival_time,
  $total_nights,
  $total_price
);

if ($sql->execute()) {

  $booking_id = $conn->insert_id;

  echo json_encode([
    "success" => true,
    "message" => "Your booking request was submitted successfully!",
    "booking_id" => $booking_id,
    "room_name" => $room["room_name"],
    "check_in" => $check_in,
    "check_out" => $check_out,
    "total_nights" => $total_nights,
    "total_price" => number_format($total_price, 2),
    "booking_status" => "pending"
  ]);
} else {

  echo json_encode([
    "success" => false,
    "message" => "We could not save your booking. Please try again."
  ]);
}

$sql->close();
$conn->close();
