<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Create CSRF token
if (empty($_SESSION["staff_csrf_token"])) {
  $_SESSION["staff_csrf_token"] = bin2hex(random_bytes(32));
}

$errorMessage = "";

$fullname = "";
$email = "";
$phone = "";
$role = "";
$status = "active";

// Employee roles
$allowedRoles = [
  "admin",
  "receptionist",
  "chef",
  "finance",
  "waiter",
  "housekeeping",
  "maintenance",
  "security",
  "laundry",
  "groundskeeper"
];

// Process registration form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Verify CSRF token
  if (
    !isset($_POST["csrf_token"]) ||
    !hash_equals(
      $_SESSION["staff_csrf_token"],
      $_POST["csrf_token"]
    )
  ) {
    $errorMessage = "Invalid form submission. Please refresh and try again.";
  } else {

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";
    $status = $_POST["status"] ?? "active";

    // Validate employee information
    if (
      $fullname === "" ||
      $email === "" ||
      $phone === "" ||
      $password === "" ||
      $role === ""
    ) {
      $errorMessage = "Please fill in all required fields.";
    } elseif (
      !preg_match("/^[\p{L}\p{M}\s.'-]+$/u", $fullname) ||
      strlen($fullname) > 100 ||
      !filter_var($email, FILTER_VALIDATE_EMAIL) ||
      strlen($email) > 150 ||
      !preg_match("/^[0-9+\s().-]+$/", $phone) ||
      strlen($phone) > 30
    ) {
      $errorMessage = "Please enter valid employee information.";
    } elseif (strlen($password) < 8) {
      $errorMessage = "Password must contain at least 8 characters.";
    } elseif (!in_array($role, $allowedRoles, true)) {
      $errorMessage = "Please select a valid employee role.";
    } elseif (!in_array($status, ["active", "inactive"], true)) {
      $errorMessage = "Please select a valid account status.";
    } else {

      // Hash the employee password
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      // Insert employee into database
      $stmt = $conn->prepare("
                INSERT INTO users
                (fullname, email, phone, password, role, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

      if ($stmt) {

        $stmt->bind_param(
          "ssssss",
          $fullname,
          $email,
          $phone,
          $hashedPassword,
          $role,
          $status
        );

        if ($stmt->execute()) {

          $stmt->close();

          header("Location: staff.php?success=added");
          exit;
        } else {

          if ($stmt->errno === 1062) {
            $errorMessage = "This email address is already registered.";
          } else {
            error_log("Staff registration failed: " . $stmt->error);
            $errorMessage = "Unable to register this employee. Please try again.";
          }
        }

        $stmt->close();
      } else {

        error_log("Staff registration prepare failed: " . $conn->error);
        $errorMessage = "Something went wrong. Please try again.";
      }
    }
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Add Staff | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet"
    href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
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
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a class="active" href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
      </nav>

      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>

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
          <input type="hidden" name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>

      </header>

      <main class="page-content">

        <div class="page-heading">

          <div>
            <h1>Add New Staff</h1>
            <p>Register a new Starian Hotel employee.</p>
          </div>

          <a class="button-primary" href="staff.php">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Staff
          </a>

        </div>

        <?php if ($errorMessage !== ""): ?>
          <div class="form-notification error">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8"); ?>
          </div>
        <?php endif; ?>

        <section class="panel">

          <form method="POST" action="add-staff.php">

            <input type="hidden" name="csrf_token"
              value="<?php echo htmlspecialchars($_SESSION["staff_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

            <div class="form-group">
              <label for="fullname">Full Name</label>

              <input
                type="text"
                id="fullname"
                name="fullname"
                value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?>"
                maxlength="100"
                required>
            </div>

            <div class="form-group">
              <label for="email">Email Address</label>

              <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"
                maxlength="150"
                required>
            </div>

            <div class="form-group">
              <label for="phone">Phone Number</label>

              <input
                type="tel"
                id="phone"
                name="phone"
                value="<?php echo htmlspecialchars($phone, ENT_QUOTES, "UTF-8"); ?>"
                maxlength="30"
                required>
            </div>

            <div class="form-group">
              <label for="password">Initial Password</label>

              <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required>
            </div>

            <div class="form-group">
              <label for="role">Employee Role</label>

              <select id="role" name="role" required>

                <option value="">Select Employee Role</option>

                <?php foreach ($allowedRoles as $employeeRole): ?>

                  <option
                    value="<?php echo htmlspecialchars($employeeRole, ENT_QUOTES, "UTF-8"); ?>"
                    <?php echo $role === $employeeRole ? "selected" : ""; ?>>

                    <?php echo htmlspecialchars(ucwords(str_replace("_", " ", $employeeRole)), ENT_QUOTES, "UTF-8"); ?>

                  </option>

                <?php endforeach; ?>

              </select>
            </div>

            <div class="form-group">
              <label for="status">Account Status</label>

              <select id="status" name="status" required>
                <option value="active" <?php echo $status === "active" ? "selected" : ""; ?>>
                  Active
                </option>

                <option value="inactive" <?php echo $status === "inactive" ? "selected" : ""; ?>>
                  Inactive
                </option>
              </select>
            </div>

            <button type="submit" class="button-primary">
              <i class="fa-solid fa-user-plus"></i>
              Register Staff
            </button>

          </form>

        </section>

      </main>

    </div>

  </div>

</body>

</html>