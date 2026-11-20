
<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: staff.php?error=invalid_request");
  exit;
}

// Verify CSRF token
if (
  !isset($_POST["csrf_token"]) ||
  !isset($_SESSION["staff_status_csrf_token"]) ||
  !hash_equals(
    $_SESSION["staff_status_csrf_token"],
    $_POST["csrf_token"]
  )
) {
  header("Location: staff.php?error=invalid_request");
  exit;
}

// Validate staff ID
$staffId = filter_input(INPUT_POST, "staff_id", FILTER_VALIDATE_INT);

if (!$staffId || $staffId <= 0) {
  header("Location: staff.php?error=invalid_request");
  exit;
}

// Validate requested status
$newStatus = $_POST["status"] ?? "";

if (!in_array($newStatus, ["active", "inactive"], true)) {
  header("Location: staff.php?error=invalid_request");
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

// Retrieve the employee's current employment status
$check = $conn->prepare("
    SELECT employment_status
    FROM users
    WHERE id = ? AND role != 'customer'
");

if (!$check) {
  error_log("Staff lookup failed: " . $conn->error);
  header("Location: staff.php?error=update_failed");
  exit;
}

$check->bind_param("i", $staffId);
$check->execute();

$result = $check->get_result();
$staff = $result->fetch_assoc();

$check->close();

if (!$staff) {
  header("Location: staff.php?error=not_found");
  exit;
}

// Prevent suspended or former employees from being reactivated
if (
  $newStatus === "active" &&
  $staff["employment_status"] !== "active"
) {
  header("Location: staff.php?error=employment_inactive");
  exit;
}

// Update employee account status
$stmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ? AND role != 'customer'
");

if (!$stmt) {
  error_log("Staff status update preparation failed: " . $conn->error);
  header("Location: staff.php?error=update_failed");
  exit;
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
