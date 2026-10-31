<?php
// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
  header("Location: ../login.php");
  exit;
}

if ($_SESSION["role"] !== "admin") {
  header("Location: ../dashboard.php");
  exit;
}

if (!isset($_SESSION["profile_csrf_token"])) {
  $_SESSION["profile_csrf_token"] = bin2hex(random_bytes(32));
}

$adminName = $_SESSION["fullname"] ?? "Administrator";
