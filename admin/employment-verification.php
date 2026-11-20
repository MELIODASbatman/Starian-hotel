<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Validate staff ID
$staffId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$staffId || $staffId <= 0) {
  header("Location: former-staff.php");
  exit;
}

// Fetch former employee details
$stmt = $conn->prepare("
    SELECT
        id,
        fullname,
        role,
        created_at,
        employment_status,
        departure_date
    FROM users
    WHERE id = ?
    AND role != 'customer'
    AND employment_status IN ('resigned', 'terminated', 'retired')
    LIMIT 1
");

if (!$stmt) {
  error_log("Employment verification query failed: " . $conn->error);
  exit("Unable to generate employment verification.");
}

$stmt->bind_param("i", $staffId);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) {
  header("Location: former-staff.php?error=not_found");
  exit;
}

// Format employment dates
$joinDate = !empty($staff["created_at"])
  ? date("d F Y", strtotime($staff["created_at"]))
  : "Not available";

$departureDate = !empty($staff["departure_date"])
  ? date("d F Y", strtotime($staff["departure_date"]))
  : "Not available";

$verificationDate = date("d F Y");

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Official Starian Hotel employment verification document">
  <title>Employment Verification | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . '/css/admin.css'); ?>">
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
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a class="active" href="former-staff.php" aria-current="page">
          <i class="fa-solid fa-user-clock"></i>Former Staff
        </a>
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

      <main class="page-content verification-page">
        <div class="page-heading verification-page-heading">
          <div>
            <h1>Employment Verification</h1>
            <p>Review and print the former employee's verification document.</p>
          </div>
        </div>

        <div class="verification-actions">
          <a class="button-secondary" href="former-staff.php">
            <i class="fa-solid fa-arrow-left"></i>Back to Former Staff
          </a>
          <button class="button-primary" type="button" onclick="window.print()">
            <i class="fa-solid fa-print"></i>Print / Save PDF
          </button>
        </div>

        <section class="verification-document" aria-labelledby="verification-title">
          <div class="verification-document-inner">
            <header class="verification-header">
              <img src="../img/starianlogo.png" alt="Starian Hotel">
              <p class="verification-hotel-name">Starian Hotel</p>
              <p class="verification-eyebrow">Official Employment Record</p>
              <h2 id="verification-title">Certificate of Employment</h2>
              <p class="verification-recipient">To whom it may concern</p>
            </header>

            <div class="verification-body">
              <p>
                This letter confirms that the individual listed below was employed
                by Starian Hotel. The information is based on the hotel's employment records.
              </p>

              <dl class="verification-details">
                <div>
                  <dt>Employee Name</dt>
                  <dd><?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?></dd>
                </div>
                <div>
                  <dt>Employee ID</dt>
                  <dd><?php echo (int) $staff["id"]; ?></dd>
                </div>
                <div>
                  <dt>Previous Position</dt>
                  <dd><?php echo htmlspecialchars(ucwords(str_replace("_", " ", $staff["role"])), ENT_QUOTES, "UTF-8"); ?></dd>
                </div>
                <div>
                  <dt>Employment Start Date</dt>
                  <dd><?php echo htmlspecialchars($joinDate, ENT_QUOTES, "UTF-8"); ?></dd>
                </div>
                <div>
                  <dt>Employment End Date</dt>
                  <dd><?php echo htmlspecialchars($departureDate, ENT_QUOTES, "UTF-8"); ?></dd>
                </div>
                <div>
                  <dt>Employment Status</dt>
                  <dd><?php echo htmlspecialchars(ucfirst($staff["employment_status"]), ENT_QUOTES, "UTF-8"); ?></dd>
                </div>
              </dl>

              <p>
                This document is issued upon request for employment verification purposes.
              </p>
            </div>

            <footer class="verification-footer">
              <div class="verification-issued">
                <span>Issued on</span>
                <strong><?php echo htmlspecialchars($verificationDate, ENT_QUOTES, "UTF-8"); ?></strong>
              </div>

              <div class="verification-signature">
                <span class="verification-signature-line"></span>
                <strong>General Hotel Manager</strong>
                <span>Starian Hotel</span>
              </div>
            </footer>

            <p class="verification-document-reference">
              Prepared from Starian Hotel employment records
            </p>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>