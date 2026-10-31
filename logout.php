<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  header("Allow: POST");
  exit("Method not allowed.");
}

$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["profile_csrf_token"] ?? "";

if (
  !isset($_SESSION["user_id"])
  || !is_string($submittedToken)
  || !is_string($sessionToken)
  || $sessionToken === ""
  || !hash_equals($sessionToken, $submittedToken)
) {
  http_response_code(403);
  exit("Your session has expired. Refresh the page and try again.");
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
  $cookie = session_get_cookie_params();
  setcookie(
    session_name(),
    "",
    time() - 42000,
    $cookie["path"],
    $cookie["domain"],
    $cookie["secure"],
    $cookie["httponly"]
  );
}

session_destroy();

header("Location: login.php?logged_out=1", true, 303);
exit;
