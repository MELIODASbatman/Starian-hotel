<?php

session_start();

header("Content-Type: application/json");

require_once "db.php";

// 1. Check whether the customer is logged in
if (!isset($_SESSION["user_id"])) {
  http_response_code(401);

  echo json_encode([
    "success" => false,
    "message" => "Please log in before placing your order.",
    "redirect" => "../login.php"
  ]);

  exit;
}

// 2. Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);

  echo json_encode([
    "success" => false,
    "message" => "Invalid request method."
  ]);

  exit;
}

// 3. Read the JSON sent by JavaScript
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input) || !isset($input["items"]) || !is_array($input["items"])) {
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Invalid order data."
  ]);

  exit;
}

$items = $input["items"];

// 4. Check that the cart is not empty
if (empty($items)) {
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Your cart is empty."
  ]);

  exit;
}

// Limit the number of different food items in one order
if (count($items) > 16) {
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Your order contains too many different items."
  ]);

  exit;
}

try {
  $conn->begin_transaction();

  $validatedItems = [];
  $totalAmount = 0;

  // 5. Validate each food item using its database ID
  $foodStatement = $conn->prepare(
    "SELECT id, food_name, price
         FROM food_menu
         WHERE id = ? AND is_available = 1"
  );

  foreach ($items as $item) {
    $foodId = filter_var(
      $item["food_id"] ?? null,
      FILTER_VALIDATE_INT
    );

    $quantity = filter_var(
      $item["quantity"] ?? null,
      FILTER_VALIDATE_INT
    );

    if (
      $foodId === false ||
      $foodId === null ||
      $foodId < 1 ||
      $quantity === false ||
      $quantity === null ||
      $quantity < 1 ||
      $quantity > 100
    ) {
      throw new Exception(
        "An item or quantity in your cart is invalid."
      );
    }

    $foodStatement->bind_param("i", $foodId);
    $foodStatement->execute();

    $result = $foodStatement->get_result();
    $food = $result->fetch_assoc();

    if (!$food) {
      throw new Exception(
        "One of the selected items is unavailable."
      );
    }

    $priceInCents = (int) round((float) $food["price"] * 100);
    $subtotalInCents = $priceInCents * $quantity;

    if (
      $priceInCents <= 0 ||
      $subtotalInCents > 9999999999
    ) {
      throw new Exception(
        "The order total is outside the permitted range."
      );
    }

    $totalAmount += $subtotalInCents;

    $validatedItems[] = [
      "food_id" => (int) $food["id"],
      "quantity" => $quantity,
      "price" => $priceInCents / 100,
      "subtotal" => $subtotalInCents / 100
    ];
  }

  $foodStatement->close();

  if ($totalAmount <= 0 || $totalAmount > 9999999999) {
    throw new Exception("The order total is invalid.");
  }

  // 6. Save the main order
  $userId = (int) $_SESSION["user_id"];
  $totalAmount = $totalAmount / 100;

  $orderStatement = $conn->prepare(
    "INSERT INTO food_orders (user_id, total_amount)
         VALUES (?, ?)"
  );

  $orderStatement->bind_param("id", $userId, $totalAmount);
  $orderStatement->execute();

  $orderId = $conn->insert_id;

  $orderStatement->close();

  // 7. Save each food item in the order
  $itemStatement = $conn->prepare(
    "INSERT INTO food_order_items
         (order_id, food_id, quantity, price, subtotal)
         VALUES (?, ?, ?, ?, ?)"
  );

  foreach ($validatedItems as $item) {
    $foodId = $item["food_id"];
    $quantity = $item["quantity"];
    $price = $item["price"];
    $subtotal = $item["subtotal"];

    $itemStatement->bind_param(
      "iiidd",
      $orderId,
      $foodId,
      $quantity,
      $price,
      $subtotal
    );

    $itemStatement->execute();
  }

  $itemStatement->close();

  // 8. Commit the transaction
  $conn->commit();

  echo json_encode([
    "success" => true,
    "message" => "Your food order has been placed successfully!",
    "order_id" => $orderId,
    "total_amount" => $totalAmount
  ]);
} catch (Throwable $error) {
  $conn->rollback();

  error_log("Food order error: " . $error->getMessage());

  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => $error instanceof Exception
      ? $error->getMessage()
      : "We could not place your order. Please try again."
  ]);
}

$conn->close();
