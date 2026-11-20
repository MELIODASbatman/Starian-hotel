<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: staff.php");
  exit;
}

// Verify CSRF token
if (
  !isset($_POST["csrf_token"]) ||
  !hash_equals(
    $_SESSION["staff_status_csrf_token"],
    $_POST["csrf_token"]
  )
) {
  exit("Invalid request. Please refresh and try again.");
}

// Validate staff ID
$staffId = filter_input(INPUT_POST, "staff_id", FILTER_VALIDATE_INT);

if (!$staffId || $staffId <= 0) {
  header("Location: staff.php");
  exit;
}

// Validate requested status
$newStatus = $_POST["status"] ?? "";

if (!in_array($newStatus, ["active", "inactive"], true)) {
  header("Location: staff.php");
  exit;
}

// Prevent an administrator from deactivating their own account
if (
  $staffId === (int) $_SESSION["user_id"] &&
  $newStatus === "inactive"
) {
  header("Location: staff.php?error=self_deactivation");
  exit;
}

// Update employee status
$stmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ? AND role != 'customer'
");

if (!$stmt) {
  error_log("Staff status update preparation failed: " . $conn->error);
  exit("Unable to process this request.");
}

$stmt->bind_param("si", $newStatus, $staffId);

if ($stmt->execute()) {
  $stmt->close();

  header(
    "Location: staff.php?success=" .
      ($newStatus === "inactive" ? "deactivated" : "reactivated")
  );
  exit;
}

error_log("Staff status update failed: " . $stmt->error);
$stmt->close();

header("Location: staff.php?error=update_failed");
exit;
