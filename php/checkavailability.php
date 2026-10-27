<?php

session_start();

require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

function availabilityResponse($success, $available, $message)
{
  echo json_encode([
    "success" => $success,
    "available" => $available,
    "message" => $message
  ]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  availabilityResponse(false, false, "Invalid request method.");
}

$room_code = trim($_POST["room_option"] ?? "");
$check_in = $_POST["check_in"] ?? "";
$check_out = $_POST["check_out"] ?? "";
$arrival_time = $_POST["arrival_time"] ?? "";

if (empty($room_code) || empty($check_in) || empty($check_out) || empty($arrival_time)) {
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

$sql = $conn->prepare(
  "SELECT id, status
     FROM rooms
     WHERE room_code = ?"
);
$sql->bind_param("s", $room_code);
$sql->execute();
$roomResult = $sql->get_result();

if ($roomResult->num_rows === 0) {
  $sql->close();
  $conn->close();
  availabilityResponse(false, false, "The selected room does not exist.");
}

$room = $roomResult->fetch_assoc();
$sql->close();

if ($room["status"] !== "available") {
  $conn->close();
  availabilityResponse(true, false, "This room is currently unavailable.");
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
$sql->bind_param("iss", $room["id"], $check_out, $check_in);
$sql->execute();
$bookingResult = $sql->get_result();
$isBooked = $bookingResult->num_rows > 0;

$sql->close();
$conn->close();

if ($isBooked) {
  availabilityResponse(
    true,
    false,
    "Sorry, this room is already booked for those dates. Please choose another room or dates."
  );
}

availabilityResponse(true, true, "This room is available for your selected dates.");
