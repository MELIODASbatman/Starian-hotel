<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Create CSRF token for staff status changes
if (empty($_SESSION["staff_status_csrf_token"])) {
  $_SESSION["staff_status_csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch all employees, excluding customers
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
   ORDER BY FIELD(
    role,
    'admin',
    'finance',
    'receptionist',
    'chef',
    'waiter',
    'housekeeping',
    'maintenance',
    'security',
    'laundry',
    'groundskeeper'
), id DESC
");

if (!$sql) {
  error_log("Unable to fetch staff: " . $conn->error);
  http_response_code(500);
  exit("Unable to load staff members right now.");
}

// Store employee records
$staffMembers = [];

while ($row = $sql->fetch_assoc()) {
  $staffMembers[] = $row;
}

// Calculate staff statistics
$totalStaff = count($staffMembers);
$activeStaff = 0;
$inactiveStaff = 0;
$departments = [];

foreach ($staffMembers as $staff) {

  if ($staff["status"] === "active") {
    $activeStaff++;
  } else {
    $inactiveStaff++;
  }

  $departments[$staff["role"]] = true;
}

$totalDepartments = count($departments);

// Display success messages
$successMessages = [
  "added" => "Staff member registered successfully.",
  "updated" => "Staff details updated successfully.",
  "deactivated" => "Staff account deactivated successfully.",
  "reactivated" => "Staff account reactivated successfully."
];

$successMessage = "";
$errorMessages = [
  "employment_inactive" => "This staff member cannot be reactivated because their employment status is not active.",
  "invalid_request" => "The staff status request was invalid. Please try again.",
  "not_found" => "The selected staff member could not be found.",
  "self_deactivation" => "You cannot deactivate your own administrator account.",
  "update_failed" => "Unable to update the staff account status. Please try again."
];
$errorMessage = "";

if (isset($_GET["success"]) && isset($successMessages[$_GET["success"]])) {
  $successMessage = $successMessages[$_GET["success"]];
}

if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
  $errorMessage = $errorMessages[$_GET["error"]];
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel staff members">
  <title>Staff Management | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a><a class="active" href="staff.php" aria-current="page"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a><a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><?php require __DIR__ . "/includes/notification-widget.php"; ?>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Staff Management</h1>
            <p>Manage hotel staff members, roles and access.</p>
          </div>
          <a class="button-primary" href="add-staff.php">
            <i class="fa-solid fa-plus"></i>Add New Staff
          </a>
        </div>
        <?php if ($successMessage !== ""): ?>
          <div class="room-form-alert success staff-toast" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span class="staff-toast-message">
              <?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?>
            </span>
            <button class="staff-toast-dismiss" type="button" aria-label="Dismiss notification">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        <?php endif; ?>
        <?php if ($errorMessage !== ""): ?>
          <div class="room-form-alert staff-toast" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span class="staff-toast-message">
              <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8"); ?>
            </span>
            <button class="staff-toast-dismiss" type="button" aria-label="Dismiss notification">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        <?php endif; ?>

        <section class="staff-summary">
          <article class="stat-card"><span class="stat-icon"><i class="fa-solid fa-users"></i></span>
            <div><span class="stat-label">Total Staff</span>
              <strong class="stat-value"><?php echo $totalStaff; ?></strong>
              <span class="stat-foot">Active staff members</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon green"><i class="fa-solid fa-user-check"></i></span>
            <div><span class="stat-label">Active Staff</span>
              <strong class="stat-value"><?php echo $activeStaff; ?></strong><span class="stat-foot">Currently working</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon" style="background:#ffe5e5;color:#ee5151"><i class="fa-solid fa-user-minus"></i></span>
            <div><span class="stat-label">Inactive Staff</span>
              <strong class="stat-value"><?php echo $inactiveStaff; ?></strong><span class="stat-foot">On leave / not active</span>
            </div>
          </article>
          <article class="stat-card"><span class="stat-icon purple"><i class="fa-solid fa-briefcase"></i></span>
            <div><span class="stat-label">Departments</span>
              <strong class="stat-value"><?php echo $totalDepartments; ?></strong><span class="stat-foot">Different roles</span>
            </div>
          </article>
        </section>

        <section class="panel">
          <div class="heading-actions" style="justify-content:space-between;margin-bottom:14px">
            <label class="search-box"><i class="fa-solid fa-magnifying-glass"></i>
              <input
                type="search"
                id="staffSearch"
                placeholder="Search staff by name, email or role..."
                autocomplete="off">
            </label>
            <div class="heading-actions">
              <label class="select-control">
                <select id="roleFilter" aria-label="Filter by role">
                  <option value="">All Roles</option>
                  <option value="admin">Administrator</option>
                  <option value="receptionist">Receptionist</option>
                  <option value="chef">Chef</option>
                  <option value="finance">Finance</option>
                  <option value="waiter">Waiter</option>
                  <option value="housekeeping">Housekeeping</option>
                  <option value="maintenance">Maintenance</option>
                  <option value="security">Security</option>
                  <option value="laundry">Laundry</option>
                  <option value="groundskeeper">Groundskeeper</option>
                </select>
              </label>
              <label class="select-control">
                <select id="statusFilter" aria-label="Filter by status">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </label>
              <label class="select-control">
                <select id="employmentStatusFilter" aria-label="Filter by employment status">
                  <option value="">All Employment Statuses</option>
                  <option value="active">Active</option>
                  <option value="suspended">Suspended</option>
                  <option value="resigned">Resigned</option>
                  <option value="terminated">Terminated</option>
                  <option value="retired">Retired</option>
                </select>
              </label>
            </div>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th><input type="checkbox" aria-label="Select all staff"></th>
                  <th>#</th>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Role</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Status</th>
                  <th>Employment Status</th>
                  <th>Join Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($staffMembers)): ?>

                  <?php foreach ($staffMembers as $index => $staff): ?>

                    <tr>
                      <td>
                        <input
                          type="checkbox"
                          aria-label="Select <?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?>">
                      </td>

                      <td><?php echo $index + 1; ?></td>

                      <td>
                        <span class="person-avatar">
                          <i class="fa-solid fa-user-tie"></i>
                        </span>
                      </td>

                      <td>
                        <strong>
                          <?php echo htmlspecialchars($staff["fullname"], ENT_QUOTES, "UTF-8"); ?>
                        </strong>
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
                        <?php if ($staff["status"] === "active"): ?>
                          <span class="badge">Active</span>
                        <?php else: ?>
                          <span class="badge danger">Inactive</span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <?php
                        $employmentStatus = $staff["employment_status"] ?? "active";

                        $employmentLabels = [
                          "active" => "Active",
                          "suspended" => "Suspended",
                          "resigned" => "Resigned",
                          "terminated" => "Terminated",
                          "retired" => "Retired"
                        ];
                        ?>

                        <span class="badge">
                          <?php
                          echo htmlspecialchars(
                            $employmentLabels[$employmentStatus] ?? ucfirst($employmentStatus),
                            ENT_QUOTES,
                            "UTF-8"
                          );
                          ?>
                        </span>
                      </td>

                      <td>
                        <?php
                        echo !empty($staff["created_at"])
                          ? date("d M Y", strtotime($staff["created_at"]))
                          : "Not available";
                        ?>
                      </td>

                      <td>
                        <div class="actions">
                          <a class="icon-button"
                            href="edit-staff.php?id=<?php echo (int) $staff['id']; ?>"
                            aria-label="Edit staff details">
                            <i class="fa-solid fa-pen"></i>
                          </a>

                          <a class="icon-button"
                            href="view-staff.php?id=<?php echo (int) $staff['id']; ?>"
                            aria-label="View staff details">
                            <i class="fa-regular fa-eye"></i>
                          </a>

                          <form method="POST"
                            action="toggle-staff-status.php"
                            onsubmit="return confirm('Are you sure you want to change this staff member’s account status?');">

                            <input type="hidden" name="csrf_token"
                              value="<?php echo htmlspecialchars($_SESSION["staff_status_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                            <input type="hidden" name="staff_id"
                              value="<?php echo (int) $staff['id']; ?>">

                            <input type="hidden" name="status"
                              value="<?php echo $staff['status'] === 'active' ? 'inactive' : 'active'; ?>">

                            <button class="icon-button danger-action" type="submit"
                              aria-label="<?php echo $staff['status'] === 'active' ? 'Deactivate staff' : 'Reactivate staff'; ?>"
                              title="<?php echo $staff['status'] === 'active' ? 'Deactivate' : 'Reactivate'; ?>">

                              <i class="fa-solid <?php echo $staff['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>

                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>

                  <?php endforeach; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="11" style="text-align:center; padding:25px;">
                      No staff members found.
                    </td>
                  </tr>

                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="table-footer">
            <span>
              Showing <?php echo $totalStaff; ?> staff members
            </span>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script src="javascript/staff.js?v=<?php echo filemtime(__DIR__ . '/javascript/staff.js'); ?>"></script>
</body>

</html>