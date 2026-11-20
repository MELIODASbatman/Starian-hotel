<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Fetch former employees
$sql = $conn->query("
    SELECT
        id,
        fullname,
        email,
        phone,
        avatar,
        role,
        status,
        employment_status,
        departure_date,
        departure_reason,
        created_at
    FROM users
    WHERE role != 'customer'
    AND employment_status IN ('resigned', 'terminated', 'retired')
    ORDER BY departure_date DESC, id DESC
");

if (!$sql) {
  error_log("Unable to fetch former staff: " . $conn->error);
  http_response_code(500);
  exit("Unable to load former staff members right now.");
}

// Store former employee records
$formerStaff = [];

while ($row = $sql->fetch_assoc()) {
  $formerStaff[] = $row;
}

// Calculate former staff statistics
$totalFormerStaff = count($formerStaff);

$resignedStaff = 0;
$terminatedStaff = 0;
$retiredStaff = 0;

foreach ($formerStaff as $staff) {

  if ($staff["employment_status"] === "resigned") {
    $resignedStaff++;
  } elseif ($staff["employment_status"] === "terminated") {
    $terminatedStaff++;
  } elseif ($staff["employment_status"] === "retired") {
    $retiredStaff++;
  }
}

?>


<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage former Starian Hotel employees">
  <title>Former Staff | Starian Hotel Admin</title>

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
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a class="active" href="former-staff.php">
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
            <i class="fa-solid fa-right-from-bracket"></i>Log out
          </button>
        </form>
      </header>

      <main class="page-content">

        <div class="page-heading">
          <div>
            <h1>Former Staff</h1>
            <p>View employees who have left Starian Hotel.</p>
          </div>

          <a class="button-primary" href="staff.php">
            <i class="fa-solid fa-arrow-left"></i>Back to Staff
          </a>
        </div>

        <section class="staff-summary">

          <!-- Total Former Staff -->
          <article class="stat-card">
            <span class="stat-icon">
              <i class="fa-solid fa-users"></i>
            </span>
            <div>
              <span class="stat-label">Total Former Staff</span>
              <strong class="stat-value">
                <?php echo $totalFormerStaff; ?>
              </strong>
              <span class="stat-foot">Employees who have left</span>
            </div>
          </article>

          <!-- Resigned -->
          <article class="stat-card">
            <span class="stat-icon green">
              <i class="fa-solid fa-person-walking-arrow-right"></i>
            </span>
            <div>
              <span class="stat-label">Resigned</span>
              <strong class="stat-value">
                <?php echo $resignedStaff; ?>
              </strong>
              <span class="stat-foot">Voluntary departures</span>
            </div>
          </article>

          <!-- Terminated -->
          <article class="stat-card">
            <span class="stat-icon" style="background:#ffe5e5;color:#ee5151">
              <i class="fa-solid fa-user-slash"></i>
            </span>
            <div>
              <span class="stat-label">Terminated</span>
              <strong class="stat-value">
                <?php echo $terminatedStaff; ?>
              </strong>
              <span class="stat-foot">Employment terminated</span>
            </div>
          </article>

          <!-- Retired -->
          <article class="stat-card">
            <span class="stat-icon purple">
              <i class="fa-solid fa-person-cane"></i>
            </span>
            <div>
              <span class="stat-label">Retired</span>
              <strong class="stat-value">
                <?php echo $retiredStaff; ?>
              </strong>
              <span class="stat-foot">Retired employees</span>
            </div>
          </article>

        </section>
        <section class="panel">

          <div class="heading-actions" style="justify-content:space-between;margin-bottom:14px">

            <label class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input
                type="search"
                id="formerStaffSearch"
                placeholder="Search former staff..."
                autocomplete="off">
            </label>

            <label class="select-control">
              <select id="formerStatusFilter" aria-label="Filter by departure status">
                <option value="">All Departure Statuses</option>
                <option value="resigned">Resigned</option>
                <option value="terminated">Terminated</option>
                <option value="retired">Retired</option>
              </select>
            </label>

          </div>

          <div class="table-wrap">
            <table class="data-table">

              <thead>
                <tr>
                  <th>#</th>
                  <th>Employee</th>
                  <th>Previous Role</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Employment Status</th>
                  <th>Departure Date</th>
                  <th>Reason for Leaving</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>

                <?php if (!empty($formerStaff)): ?>

                  <?php foreach ($formerStaff as $index => $staff): ?>

                    <tr>
                      <td><?php echo $index + 1; ?></td>

                      <td>
                        <strong>
                          <?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?>
                        </strong>
                        <br>
                        <small>
                          ID: <?php echo (int) $staff["id"]; ?>
                        </small>
                      </td>

                      <td>
                        <span class="badge blue">
                          <?php echo htmlspecialchars(ucfirst($staff["role"]), ENT_QUOTES, "UTF-8"); ?>
                        </span>
                      </td>

                      <td>
                        <?php echo htmlspecialchars($staff["email"], ENT_QUOTES, "UTF-8"); ?>
                      </td>

                      <td>
                        <?php echo htmlspecialchars($staff["phone"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?>
                      </td>

                      <td>
                        <span class="badge danger">
                          <?php echo htmlspecialchars(ucfirst($staff["employment_status"]), ENT_QUOTES, "UTF-8"); ?>
                        </span>
                      </td>

                      <td>
                        <?php
                        echo !empty($staff["departure_date"])
                          ? date("d M Y", strtotime($staff["departure_date"]))
                          : "Not provided";
                        ?>
                      </td>

                      <td>
                        <?php
                        echo !empty($staff["departure_reason"])
                          ? htmlspecialchars($staff["departure_reason"], ENT_QUOTES, "UTF-8")
                          : "Not provided";
                        ?>
                      </td>

                      <td>
                        <a class="icon-button"
                          href="view-staff.php?id=<?php echo (int) $staff['id']; ?>"
                          aria-label="View former staff details"
                          title="View Details">
                          <i class="fa-regular fa-eye"></i>
                        </a>

                        <a class="icon-button"
                          href="employment-verification.php?id=<?php echo (int) $staff['id']; ?>"
                          aria-label="Generate employment verification"
                          title="Employment Verification">
                          <i class="fa-solid fa-file-circle-check"></i>
                        </a>
                      </td>
                    </tr>

                  <?php endforeach; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="9" style="text-align:center; padding:25px;">
                      No former staff members found.
                    </td>
                  </tr>

                <?php endif; ?>

              </tbody>

            </table>
          </div>

          <div class="table-footer">
            <span>
              Showing <?php echo $totalFormerStaff; ?> former staff members
            </span>
          </div>


        </section>
      </main>
    </div>
  </div>

  <script src="javascript/former-staff.js?v=<?php echo filemtime(__DIR__ . '/javascript/former-staff.js'); ?>"></script>
</body>

</html>