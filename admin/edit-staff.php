<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Create CSRF token
if (empty($_SESSION["edit_staff_csrf_token"])) {
  $_SESSION["edit_staff_csrf_token"] = bin2hex(random_bytes(32));
}

// Validate staff ID
$staffId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$staffId || $staffId <= 0) {
  header("Location: staff.php");
  exit;
}

// Allowed employee roles
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

$errorMessage = "";

// Fetch existing staff details
$stmt = $conn->prepare("
   SELECT
    id,
    fullname,
    email,
    phone,
    role,
    status,
    employment_status,
    departure_date,
    departure_reason
FROM users
WHERE id = ? AND role != 'customer'
LIMIT 1
");

$stmt->bind_param("i", $staffId);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) {
  header("Location: staff.php?error=not_found");
  exit;
}

// Process update form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if (
    !isset($_POST["csrf_token"]) ||
    !hash_equals(
      $_SESSION["edit_staff_csrf_token"],
      $_POST["csrf_token"]
    )
  ) {
    $errorMessage = "Invalid form submission. Please refresh and try again.";
  } else {

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $role = $_POST["role"] ?? "";
    $status = $_POST["status"] ?? "";
    $employmentStatus = $_POST["employment_status"] ?? "active";
    $departureDate = $_POST["departure_date"] ?? "";
    $departureReason = trim($_POST["departure_reason"] ?? "");

    $allowedEmploymentStatuses = [
      "active",
      "suspended",
      "resigned",
      "terminated",
      "retired"
    ];

    if (
      $fullname === "" ||
      $email === "" ||
      $phone === "" ||
      $role === "" ||
      $status === ""
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
    } elseif (!in_array($role, $allowedRoles, true)) {
      $errorMessage = "Please select a valid employee role.";
    } elseif (!in_array($status, ["active", "inactive"], true)) {
      $errorMessage = "Please select a valid account status.";
    } elseif (!in_array($employmentStatus, $allowedEmploymentStatuses, true)) {
      $errorMessage = "Please select a valid employment status.";
    } elseif (
      in_array($employmentStatus, ["resigned", "terminated", "retired"], true) &&
      ($departureDate === "" || $departureReason === "")
    ) {
      $errorMessage = "Please provide the departure date and reason.";
    } elseif (
      $departureDate !== "" &&
      !DateTime::createFromFormat("!Y-m-d", $departureDate)
    ) {
      $errorMessage = "Please enter a valid departure date.";
    } else {

      // Check whether another account uses this email
      $checkEmail = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ? AND id != ?
                LIMIT 1
            ");

      $checkEmail->bind_param("si", $email, $staffId);
      $checkEmail->execute();

      $emailResult = $checkEmail->get_result();

      if ($emailResult->num_rows > 0) {
        $errorMessage = "This email address is already registered.";
      }

      $checkEmail->close();

      if ($errorMessage === "") {

        // Update employee details
        $update = $conn->prepare("
                    UPDATE users
                    SET fullname = ?, email = ?, phone = ?, role = ?, status = ?, employment_status = ?, departure_date = ?, departure_reason = ?
                    WHERE id = ? AND role != 'customer'
                ");

        if ($update) {

          // Automatically disable accounts for suspended or former employees
          $finalStatus = in_array(
            $employmentStatus,
            ["suspended", "resigned", "terminated", "retired"],
            true
          ) ? "inactive" : $status;

          // Only retain departure details for former employees
          $isFormerEmployee = in_array(
            $employmentStatus,
            ["resigned", "terminated", "retired"],
            true
          );

          $finalDepartureDate = $isFormerEmployee ? $departureDate : null;
          $finalDepartureReason = $isFormerEmployee ? $departureReason : null;

          $update->bind_param(
            "ssssssssi",
            $fullname,
            $email,
            $phone,
            $role,
            $finalStatus,
            $employmentStatus,
            $finalDepartureDate,
            $finalDepartureReason,
            $staffId
          );

          if ($update->execute()) {
            $update->close();

            header("Location: staff.php?success=updated");
            exit;
          } else {
            error_log("Staff update failed: " . $update->error);
            $errorMessage = "Unable to update staff details. Please try again.";
          }

          $update->close();
        } else {
          error_log("Staff update preparation failed: " . $conn->error);
          $errorMessage = "Something went wrong. Please try again.";
        }
      }
    }

    // Preserve entered values after validation errors
    $staff["fullname"] = $fullname;
    $staff["email"] = $email;
    $staff["phone"] = $phone;
    $staff["role"] = $role;
    $staff["status"] = $status;
    $staff["employment_status"] = $employmentStatus;
    $staff["departure_date"] = $departureDate;
    $staff["departure_reason"] = $departureReason;
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Edit Starian Hotel staff information">
  <title>Edit Staff | Starian Hotel Admin</title>
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
        <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
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
        <a class="active" href="staff.php" aria-current="page"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a>
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
          <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span>
            <strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong>
            <small>Administrator</small>
          </span>
        </div>

        <form class="admin-logout-form" method="post" action="../logout.php">
          <input type="hidden" name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>Log out
          </button>
        </form>
      </header>

      <main class="page-content">
        <div class="page-heading room-form-heading">
          <div>
            <h1>Edit Staff Details</h1>
            <p>Update this employee's contact information, role, and account status.</p>
          </div>
          <a class="button-secondary" href="staff.php">
            <i class="fa-solid fa-arrow-left"></i>Back to Staff
          </a>
        </div>

        <?php if ($errorMessage !== ""): ?>
          <div class="room-form-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8"); ?>
          </div>
        <?php endif; ?>

        <section class="panel room-form-panel" aria-labelledby="edit-staff-form-title">
          <div class="room-form-intro">
            <span class="room-form-icon"><i class="fa-solid fa-user-pen"></i></span>
            <div>
              <h2 id="edit-staff-form-title">Employee information</h2>
              <p>Fields marked <span>*</span> are required.</p>
            </div>
          </div>

          <form method="POST" action="edit-staff.php?id=<?php echo (int) $staffId; ?>">
            <input type="hidden" name="csrf_token"
              value="<?php echo htmlspecialchars($_SESSION["edit_staff_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

            <div class="room-form-grid">
              <div class="form-group">
                <label for="fullname">Full Name <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="fullname"
                  name="fullname"
                  value="<?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?>"
                  maxlength="100"
                  autocomplete="name"
                  required>
              </div>

              <div class="form-group">
                <label for="email">Email Address <span aria-hidden="true">*</span></label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value="<?php echo htmlspecialchars($staff["email"], ENT_QUOTES, "UTF-8"); ?>"
                  maxlength="150"
                  autocomplete="email"
                  required>
              </div>

              <div class="form-group">
                <label for="phone">Phone Number <span aria-hidden="true">*</span></label>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  value="<?php echo htmlspecialchars($staff["phone"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                  maxlength="30"
                  autocomplete="tel"
                  required>
              </div>

              <div class="form-group">
                <label for="role">Employee Role <span aria-hidden="true">*</span></label>
                <select id="role" name="role" required>
                  <?php foreach ($allowedRoles as $allowedRole): ?>
                    <option
                      value="<?php echo htmlspecialchars($allowedRole, ENT_QUOTES, "UTF-8"); ?>"
                      <?php echo $staff["role"] === $allowedRole ? "selected" : ""; ?>>
                      <?php echo htmlspecialchars(ucfirst($allowedRole), ENT_QUOTES, "UTF-8"); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="status">Account Status <span aria-hidden="true">*</span></label>
                <select id="status" name="status" required>
                  <option value="active" <?php echo $staff["status"] === "active" ? "selected" : ""; ?>>
                    Active
                  </option>
                  <option value="inactive" <?php echo $staff["status"] === "inactive" ? "selected" : ""; ?>>
                    Inactive
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label for="employment_status">Employment Status</label>

                <select id="employment_status" name="employment_status" required>
                  <?php
                  $employmentOptions = [
                    "active" => "Active",
                    "suspended" => "Suspended",
                    "resigned" => "Resigned",
                    "terminated" => "Terminated",
                    "retired" => "Retired"
                  ];
                  ?>

                  <?php foreach ($employmentOptions as $value => $label): ?>
                    <option value="<?php echo $value; ?>"
                      <?php echo ($staff["employment_status"] ?? "active") === $value ? "selected" : ""; ?>>
                      <?php echo $label; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" id="departureDateGroup">
                <label for="departure_date">Departure Date</label>
                <input type="date"
                  id="departure_date"
                  name="departure_date"
                  value="<?php echo htmlspecialchars($staff["departure_date"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
              </div>

              <div class="form-group" id="departureReasonGroup">
                <label for="departure_reason">Reason for Leaving</label>
                <textarea id="departure_reason"
                  name="departure_reason"
                  rows="4"><?php echo htmlspecialchars($staff["departure_reason"] ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>
              </div>
            </div>

            <div class="room-form-actions">
              <a class="button-secondary" href="staff.php">Cancel</a>
              <button type="submit" class="button-primary">
                <i class="fa-solid fa-floppy-disk"></i>Save Changes
              </button>
            </div>
          </form>
        </section>
      </main>
    </div>
  </div>
  <script src="javascript/editstaff.js?v=<?php echo filemtime(__DIR__ . '/javascript/editstaff.js'); ?>"></script>
</body>

</html>