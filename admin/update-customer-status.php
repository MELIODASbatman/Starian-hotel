<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  die("Invalid request method.");
}

// Validate CSRF token
$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["customer_status_csrf_token"] ?? "";

if (
  !is_string($submittedToken) ||
  $sessionToken === "" ||
  !hash_equals($sessionToken, $submittedToken)
) {
  die("Invalid request. Please refresh the page and try again.");
}

// Validate customer ID
$customerId = filter_var(
  $_POST["customer_id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$customerId || $customerId < 1) {
  die("Invalid customer ID.");
}

// Validate requested status
$newStatus = $_POST["status"] ?? "";

if (!in_array($newStatus, ["active", "inactive"], true)) {
  die("Invalid customer status.");
}

// Update customer account status
$updateStmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ? AND role = 'customer'
");

if (!$updateStmt) {
  die("Unable to prepare customer status update.");
}

$updateStmt->bind_param("si", $newStatus, $customerId);

if (!$updateStmt->execute()) {
  $updateStmt->close();
  die("Unable to update customer status.");
}

$updateStmt->close();

// Redirect back to customers page
$result = $newStatus === "inactive" ? "deactivated" : "reactivated";

header("Location: customers.php?status=" . $result);
exit;
