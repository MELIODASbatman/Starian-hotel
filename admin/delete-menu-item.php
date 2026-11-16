
<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: menu-orders.php");
  exit;
}

// Verify CSRF token
$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["menu_delete_csrf_token"] ?? "";

if (
  $sessionToken === "" ||
  !is_string($submittedToken) ||
  !hash_equals($sessionToken, $submittedToken)
) {
  die("Invalid request. Please refresh the page and try again.");
}

// Validate menu item ID
$id = filter_var(
  $_POST["id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
  header("Location: menu-orders.php?delete_status=invalid");
  exit;
}

try {
  $conn->begin_transaction();

  // Check that the menu item exists
  $itemStmt = $conn->prepare("
        SELECT id
        FROM food_menu
        WHERE id = ?
        FOR UPDATE
    ");

  $itemStmt->bind_param("i", $id);
  $itemStmt->execute();

  $itemResult = $itemStmt->get_result();
  $item = $itemResult->fetch_assoc();
  $itemStmt->close();

  if (!$item) {
    $conn->rollback();
    header("Location: menu-orders.php?delete_status=not_found");
    exit;
  }

  // Check whether this item appears in previous food orders
  $orderStmt = $conn->prepare("
        SELECT id
        FROM food_order_items
        WHERE food_id = ?
        LIMIT 1
    ");

  $orderStmt->bind_param("i", $id);
  $orderStmt->execute();

  $orderResult = $orderStmt->get_result();
  $hasOrders = $orderResult->num_rows > 0;
  $orderStmt->close();

  if ($hasOrders) {
    // Preserve order history by making the item unavailable
    $updateStmt = $conn->prepare("
            UPDATE food_menu
            SET is_available = 0
            WHERE id = ?
        ");

    $updateStmt->bind_param("i", $id);
    $updateStmt->execute();
    $updateStmt->close();

    $status = "unavailable";
  } else {
    // Permanently delete items with no order history
    $deleteStmt = $conn->prepare("
            DELETE FROM food_menu
            WHERE id = ?
        ");

    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    $deleteStmt->close();

    $status = "deleted";
  }

  $conn->commit();

  header("Location: menu-orders.php?delete_status=" . $status);
  exit;
} catch (Throwable $error) {
  $conn->rollback();
  error_log("Menu item deletion error: " . $error->getMessage());

  header("Location: menu-orders.php?delete_status=error");
  exit;
}
?>
