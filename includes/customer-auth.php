
<?php

// Start the session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check whether the user is logged in
if (
  !isset($_SESSION["user_id"]) ||
  !isset($_SESSION["role"])
) {
  header("Location: login.php");
  exit;
}

// Only customers can access customer pages
if ($_SESSION["role"] !== "customer") {
  header("Location: login.php");
  exit;
}

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Check the customer's current account status
$userId = (int) $_SESSION["user_id"];

$statusStmt = $conn->prepare("
    SELECT status
    FROM users
    WHERE id = ? AND role = 'customer'
    LIMIT 1
");

if (!$statusStmt) {
  error_log("Unable to prepare customer authentication query: " . $conn->error);
  http_response_code(500);
  exit("Unable to verify your account right now.");
}

$statusStmt->bind_param("i", $userId);

if (!$statusStmt->execute()) {
  error_log("Unable to check customer account status: " . $statusStmt->error);
  $statusStmt->close();
  http_response_code(500);
  exit("Unable to verify your account right now.");
}

$result = $statusStmt->get_result();
$customer = $result->fetch_assoc();

$statusStmt->close();

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

  header("Location: login.php?account=inactive");
  exit;
}

?>
