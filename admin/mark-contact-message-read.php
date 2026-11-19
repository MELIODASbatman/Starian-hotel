
<?php

// Protect this page - administrators only
require_once __DIR__ . "/includes/auth.php";

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

// Update the message status
$stmt = $conn->prepare("
    UPDATE contact_messages
    SET message_status = 'read'
    WHERE id = ?
");

if (!$stmt) {
  error_log("Message status update failed: " . $conn->error);
  header("Location: contact-messages.php?error=failed");
  exit;
}

$stmt->bind_param("i", $messageId);

if ($stmt->execute()) {
  $stmt->close();
  $conn->close();

  header("Location: contact-messages.php?updated=1");
  exit;
}

$stmt->close();
$conn->close();

header("Location: contact-messages.php?error=failed");
exit;

?>
