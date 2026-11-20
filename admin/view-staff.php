<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";
require_once __DIR__ . "/../php/avatar_helpers.php";

// Validate staff ID
$staffId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$staffId || $staffId <= 0) {
  header("Location: staff.php");
  exit;
}

// Fetch staff details
$stmt = $conn->prepare("
    SELECT
        id,
        fullname,
        email,
        phone,
        avatar,
        role,
        status,
        created_at
    FROM users
    WHERE id = ? AND role != 'customer'
    LIMIT 1
");

if (!$stmt) {
  error_log("Staff details query failed: " . $conn->error);
  exit("Unable to load staff details.");
}

$stmt->bind_param("i", $staffId);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

// Check whether staff member exists
if (!$staff) {
  header("Location: staff.php?error=not_found");
  exit;
}

$staffAvatar = getAvatarFilename($staff["avatar"] ?? null);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="View Starian Hotel staff details">
  <title>Staff Details | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__ . "/admin.css"); ?>">
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
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>Log out
          </button>
        </form>
      </header>

      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Staff Details</h1>
            <p>View this employee's profile and account information.</p>
          </div>
          <div class="heading-actions">
            <a class="button-primary" href="staff.php">
              <i class="fa-solid fa-arrow-left"></i>Back to Staff
            </a>
          </div>
        </div>

        <section class="panel staff-details-panel" aria-label="Staff member details">
          <div class="staff-profile">
            <span class="person-avatar">
              <img
                src="../img/avatars/<?php echo htmlspecialchars($staffAvatar, ENT_QUOTES, "UTF-8"); ?>"
                alt="<?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?> avatar">
            </span>
            <div>
              <h2><?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?></h2>
              <p>Hotel team member</p>
              <div class="staff-profile-badges">
                <span class="badge blue"><?php echo htmlspecialchars(ucfirst($staff["role"]), ENT_QUOTES, "UTF-8"); ?></span>
                <span class="<?php echo strtolower($staff["status"]) === "active" ? "badge" : "badge warning"; ?>">
                  <?php echo htmlspecialchars(ucfirst($staff["status"]), ENT_QUOTES, "UTF-8"); ?>
                </span>
              </div>
            </div>
          </div>

          <hr class="staff-divider">

          <h3 class="staff-section-heading">Personal and account information</h3>
          <div class="staff-detail-grid">
            <div class="staff-detail">
              <strong>Staff ID</strong>
              <span><?php echo (int) $staff["id"]; ?></span>
            </div>
            <div class="staff-detail">
              <strong>Full Name</strong>
              <span><?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="staff-detail">
              <strong>Email Address</strong>
              <span><?php echo htmlspecialchars($staff["email"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="staff-detail">
              <strong>Phone Number</strong>
              <span><?php echo htmlspecialchars($staff["phone"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="staff-detail">
              <strong>Employee Role</strong>
              <span><?php echo htmlspecialchars(ucfirst($staff["role"]), ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="staff-detail">
              <strong>Account Status</strong>
              <span><?php echo htmlspecialchars(ucfirst($staff["status"]), ENT_QUOTES, "UTF-8"); ?></span>
            </div>
            <div class="staff-detail">
              <strong>Registration Date</strong>
              <span>
                <?php
                echo !empty($staff["created_at"])
                  ? date("d M Y", strtotime($staff["created_at"]))
                  : "Not available";
                ?>
              </span>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>