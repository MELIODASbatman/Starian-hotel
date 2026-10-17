<?php
session_start();
require_once "db.php";

header("Content-Type: application/json");
if ($_SERVER["REQUEST_METHOD"] !=="POST"){
  echo json_encode([
    "success" => false,
    "message" => "Invalid request method"
  ]);
  exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if (empty($email) || empty($password)){
  echo json_encode([
    "success" => false,
    "message" => "Please enter your email and password"
  ]);
  exit;
}

$sql = $conn->prepare(
  "SELECT id, fullname, email, password FROM users WHERE email = ?"
);
$sql->bind_param("s", $email);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows === 0){
  echo json_encode([
    "success" => false,
    "message" => "Invalid email or password"
  ]);
  exit;
}

$user = $result->fetch_assoc();
if (!password_verify($password, $user["password"])){
  echo json_encode([
    "success" => false,
    "message" => "Invalid email or password"
  ]);
  exit;
}

session_regenerate_id(true);
$_SESSION["user_id"] = $user["id"];
$_SESSION["fullname"] = $user["fullname"];
$_SESSION["email"] = $user["email"];

echo json_encode([
  "success" => true,
  "message" => "Login successful"
]);

$sql->close();
$conn->close();