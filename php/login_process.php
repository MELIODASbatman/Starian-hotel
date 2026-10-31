<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode([
    "success" => false,
    "message" => "Invalid request method"
  ]);
  exit;
}

// Get form data
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Validate inputs
if (empty($email) || empty($password)) {
  echo json_encode([
    "success" => false,
    "message" => "Please enter your email and password"
  ]);
  exit;
}

// Retrieve user including their role
$sql = $conn->prepare(
  "SELECT id, fullname, email, password, avatar, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$sql->bind_param("s", $email);
$sql->execute();

$result = $sql->get_result();

// Check if user exists
if ($result->num_rows === 0) {
  echo json_encode([
    "success" => false,
    "message" => "Invalid email or password"
  ]);
  exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user["password"])) {
  echo json_encode([
    "success" => false,
    "message" => "Invalid email or password"
  ]);
  exit;
}

// Define dashboard destinations
$dashboards = [
  "admin" => "admin/index.php",
  "receptionist" => "reception/index.php",
  "chef" => "chef/index.php",
  "customer" => "dashboard.php"
];

// Get user's role
$role = $user["role"];

// Reject unsupported roles
if (!array_key_exists($role, $dashboards)) {
  echo json_encode([
    "success" => false,
    "message" => "Your account role is not recognized."
  ]);
  exit;
}

// Regenerate session ID
session_regenerate_id(true);

// Store user information in session
$_SESSION["user_id"] = $user["id"];
$_SESSION["fullname"] = $user["fullname"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $role;

// Load avatar helper
require_once __DIR__ . "/avatar_helpers.php";

$_SESSION["avatar"] = getAvatarFilename($user["avatar"] ?? null);

// Close database resources
$sql->close();
$conn->close();

// Return successful login and dashboard destination
echo json_encode([
  "success" => true,
  "message" => "Login successful",
  "redirect" => $dashboards[$role]
]);

exit;
