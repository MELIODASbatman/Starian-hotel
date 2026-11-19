
<?php

// Protect this page - administrators only
require_once __DIR__ . "/includes/auth.php";

// Create a CSRF token for contact message actions
if (empty($_SESSION["contact_messages_csrf_token"])) {
  $_SESSION["contact_messages_csrf_token"] = bin2hex(random_bytes(32));
}

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit("Method not allowed.");
}

// Verify the CSRF token
if (
  !isset($_POST["csrf_token"]) ||
  !isset($_SESSION["contact_messages_csrf_token"]) ||
  !hash_equals(
    $_SESSION["contact_messages_csrf_token"],
    $_POST["csrf_token"]
  )
) {
  http_response_code(403);
  exit("Invalid security token. Please try again.");
}

// Validate the message ID
$messageId = filter_input(
  INPUT_POST,
  "message_id",
  FILTER_VALIDATE_INT
);

if (!$messageId || $messageId < 1) {
  header("Location: contact-messages.php?error=invalid");
  exit;
}

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Delete the selected contact message
$stmt = $conn->prepare(
  "DELETE FROM contact_messages WHERE id = ?"
);

if (!$stmt) {
  error_log("Contact message deletion prepare failed: " . $conn->error);
  header("Location: contact-messages.php?error=failed");
  exit;
}

$stmt->bind_param("i", $messageId);

if ($stmt->execute() && $stmt->affected_rows > 0) {

  $stmt->close();
  $conn->close();

  header("Location: contact-messages.php?deleted=1");
  exit;
} else {

  $stmt->close();
  $conn->close();

  header("Location: contact-messages.php?error=notfound");
  exit;
}

?>
