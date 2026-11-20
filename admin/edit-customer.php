<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Generate CSRF token
if (empty($_SESSION["edit_customer_csrf_token"])) {
  $_SESSION["edit_customer_csrf_token"] = bin2hex(random_bytes(32));
}

// Get customer ID
$customerId = filter_var(
  $_GET["id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$customerId || $customerId < 1) {
  die("Invalid customer ID.");
}

// Fetch customer details
$customerStmt = $conn->prepare("
    SELECT id, fullname, email, phone, avatar
    FROM users
    WHERE id = ? AND role = 'customer'
");

$customerStmt->bind_param("i", $customerId);
$customerStmt->execute();

$customerResult = $customerStmt->get_result();
$customer = $customerResult->fetch_assoc();

$customerStmt->close();

if (!$customer) {
  die("Customer not found.");
}

// Process customer updates
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Validate CSRF token
  $submittedToken = $_POST["csrf_token"] ?? "";
  $sessionToken = $_SESSION["edit_customer_csrf_token"];

  if (
    !is_string($submittedToken) ||
    !hash_equals($sessionToken, $submittedToken)
  ) {
    die("Invalid request. Please refresh the page and try again.");
  }

  // Collect form information
  $fullname = trim($_POST["fullname"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $phone = trim($_POST["phone"] ?? "");

  // Validate full name
  if ($fullname === "") {
    $errors[] = "Full name is required.";
  }

  // Validate email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
  }

  // Check whether email belongs to another account
  if (empty($errors)) {

    $emailStmt = $conn->prepare("
            SELECT id FROM users
            WHERE email = ? AND id != ?
            LIMIT 1
        ");

    $emailStmt->bind_param("si", $email, $customerId);
    $emailStmt->execute();

    $emailResult = $emailStmt->get_result();

    if ($emailResult->num_rows > 0) {
      $errors[] = "This email address is already registered.";
    }

    $emailStmt->close();
  }

  // Update customer information
  if (empty($errors)) {

    $updateStmt = $conn->prepare("
            UPDATE users
            SET fullname = ?, email = ?, phone = ?
            WHERE id = ? AND role = 'customer'
        ");

    $updateStmt->bind_param(
      "sssi",
      $fullname,
      $email,
      $phone,
      $customerId
    );

    if ($updateStmt->execute()) {

      $updateStmt->close();

      header("Location: customers.php?updated=1");
      exit;
    } else {

      $errors[] = "Unable to update customer information.";
      $updateStmt->close();
    }
  }

  // Preserve entered values when validation fails
  $customer["fullname"] = $fullname;
  $customer["email"] = $email;
  $customer["phone"] = $phone;
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Edit Starian Hotel customer">
  <title>Edit Customer | Starian Hotel Admin</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
</head>

<body>

  <div class="admin-shell">

    <input class="menu-checkbox" type="checkbox" id="admin-menu">

    <aside class="sidebar">

      <a class="brand" href="index.php">
        <img src="../img/starianlogo.png" alt="">
        <span class="brand-name">Starian <span>Hotel</span></span>
      </a>

      <div class="admin-identity">
        <span class="admin-avatar">
          <i class="fa-solid fa-user-shield"></i>
        </span>
        <span>
          <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
          <small>Administrator</small>
        </span>
      </div>

      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a class="active" href="customers.php" aria-current="page">
          <i class="fa-solid fa-users"></i>Customers
        </a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
       <a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
      </nav>

      <p class="sidebar-motto">
        Better Service
        <span>Better Stays</span>
      </p>

    </aside>

    <div class="workspace">

      <header class="topbar">

        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu">
          <i class="fa-solid fa-bars"></i>
        </label>

        <span class="topbar-spacer"></span>

        <?php require __DIR__ . "/includes/notification-widget.php"; ?>

        <div class="top-admin">
          <span class="admin-avatar">
            <i class="fa-solid fa-user-shield"></i>
          </span>
          <span>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
            <small>Administrator</small>
          </span>
        </div>

        <form class="admin-logout-form" method="post" action="../logout.php">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>

      </header>

      <main class="page-content">

        <div class="page-heading">
          <div>
            <h1>Edit Customer</h1>
            <p>Update registered customer information.</p>
          </div>

          <div class="heading-actions">
            <a class="button-primary" href="customers.php">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Customers
            </a>
          </div>
        </div>

        <section class="panel">

          <div class="panel-body">

            <?php if (!empty($errors)): ?>
              <div class="form-message error">
                <?php foreach ($errors as $error): ?>
                  <p><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="edit-customer.php?id=<?php echo (int) $customer["id"]; ?>">

              <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["edit_customer_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

              <div class="room-form-field">
                <label for="fullname">Full Name</label>
                <input
                  type="text"
                  id="fullname"
                  name="fullname"
                  value="<?php echo htmlspecialchars($customer["fullname"], ENT_QUOTES, "UTF-8"); ?>"
                  required>
              </div>

              <div class="room-form-field">
                <label for="email">Email Address</label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value="<?php echo htmlspecialchars($customer["email"], ENT_QUOTES, "UTF-8"); ?>"
                  required>
              </div>

              <div class="room-form-field">
                <label for="phone">Phone Number</label>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  value="<?php echo htmlspecialchars($customer["phone"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
              </div>

              <button type="submit" class="button-primary">
                <i class="fa-solid fa-save"></i>
                Save Changes
              </button>

            </form>

          </div>

        </section>

      </main>

    </div>

  </div>

</body>

</html>