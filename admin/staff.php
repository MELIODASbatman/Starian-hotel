<?php
require_once __DIR__ . "/includes/auth.php";
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel staff members">
  <title>Staff Management | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a><a class="active" href="staff.php" aria-current="page"><i class="fa-solid fa-user-group"></i>Staff</a><a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Staff Management</h1>
            <p>Manage hotel staff members, roles and access.</p>
          </div><a class="button-primary" href="#"><i class="fa-solid fa-plus"></i>Add New Staff</a>
        </div>
        <section class="staff-summary">
          <article class="stat-card"><span class="stat-icon"><i class="fa-solid fa-users"></i></span>
            <div><span class="stat-label">Total Staff</span><strong class="stat-value">12</strong><span class="stat-foot">Active staff members</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon green"><i class="fa-solid fa-user-check"></i></span>
            <div><span class="stat-label">Active Staff</span><strong class="stat-value">10</strong><span class="stat-foot">Currently working</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon" style="background:#ffe5e5;color:#ee5151"><i class="fa-solid fa-user-minus"></i></span>
            <div><span class="stat-label">Inactive Staff</span><strong class="stat-value">2</strong><span class="stat-foot">On leave / not active</span></div>
          </article>
          <article class="stat-card"><span class="stat-icon purple"><i class="fa-solid fa-briefcase"></i></span>
            <div><span class="stat-label">Departments</span><strong class="stat-value">6</strong><span class="stat-foot">Different roles</span></div>
          </article>
        </section>
        <section class="panel">
          <div class="heading-actions" style="justify-content:space-between;margin-bottom:14px">
            <label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Search staff by name, email or role..."></label>
            <div class="heading-actions"><label class="select-control"><select aria-label="Filter by role">
                  <option>All Roles</option>
                  <option>Manager</option>
                  <option>Receptionist</option>
                  <option>Chef</option>
                </select></label><label class="select-control"><select aria-label="Filter by status">
                  <option>All Status</option>
                  <option>Active</option>
                  <option>Inactive</option>
                </select></label></div>
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
                  <th>Join Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input type="checkbox" aria-label="Select John Mwangi"></td>
                  <td>1</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>John Mwangi</strong></td>
                  <td><span class="badge blue">Manager</span></td>
                  <td>john.mwangi@starianhotel.com</td>
                  <td>+254 712 345 678</td>
                  <td><span class="badge">Active</span></td>
                  <td>15 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit John Mwangi"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View John Mwangi"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete John Mwangi"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select Annita Wanjiku"></td>
                  <td>2</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>Annita Wanjiku</strong></td>
                  <td><span class="badge" style="background:#eee8ff;color:#7251d6">Receptionist</span></td>
                  <td>annita.wanjiku@starianhotel.com</td>
                  <td>+254 723 456 789</td>
                  <td><span class="badge">Active</span></td>
                  <td>16 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit Annita Wanjiku"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View Annita Wanjiku"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete Annita Wanjiku"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select David Ochieng"></td>
                  <td>3</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>David Ochieng</strong></td>
                  <td><span class="badge warning">Chef</span></td>
                  <td>david.ochieng@starianhotel.com</td>
                  <td>+254 734 567 890</td>
                  <td><span class="badge">Active</span></td>
                  <td>17 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit David Ochieng"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View David Ochieng"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete David Ochieng"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select Grace Njeri"></td>
                  <td>4</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>Grace Njeri</strong></td>
                  <td><span class="badge blue">Housekeeping</span></td>
                  <td>grace.njeri@starianhotel.com</td>
                  <td>+254 701 234 567</td>
                  <td><span class="badge">Active</span></td>
                  <td>19 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit Grace Njeri"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View Grace Njeri"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete Grace Njeri"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select Peter Kamau"></td>
                  <td>5</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>Peter Kamau</strong></td>
                  <td><span class="badge" style="background:#ffe4ef;color:#c43773">Waiter</span></td>
                  <td>peter.kamau@starianhotel.com</td>
                  <td>+254 756 987 654</td>
                  <td><span class="badge">Active</span></td>
                  <td>20 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit Peter Kamau"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View Peter Kamau"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete Peter Kamau"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select Lilian Achieng"></td>
                  <td>6</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>Lilian Achieng</strong></td>
                  <td><span class="badge" style="background:#eee8ff;color:#7251d6">Receptionist</span></td>
                  <td>lilian.achieng@starianhotel.com</td>
                  <td>+254 762 345 678</td>
                  <td><span class="badge danger">Inactive</span></td>
                  <td>22 Sep 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit Lilian Achieng"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View Lilian Achieng"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete Lilian Achieng"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select James Otieno"></td>
                  <td>7</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>James Otieno</strong></td>
                  <td><span class="badge blue">Maintenance</span></td>
                  <td>james.otieno@starianhotel.com</td>
                  <td>+254 715 789 012</td>
                  <td><span class="badge">Active</span></td>
                  <td>25 Oct 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit James Otieno"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View James Otieno"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete James Otieno"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><input type="checkbox" aria-label="Select Beatrice Ndagwa"></td>
                  <td>8</td>
                  <td><span class="person-avatar"><i class="fa-solid fa-user-tie"></i></span></td>
                  <td><strong>Beatrice Ndagwa</strong></td>
                  <td><span class="badge blue">Housekeeping</span></td>
                  <td>beatrice.ndagwa@starianhotel.com</td>
                  <td>+254 780 123 456</td>
                  <td><span class="badge danger">Inactive</span></td>
                  <td>27 Aug 2025</td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="Edit Beatrice Ndagwa"><i class="fa-solid fa-pen"></i></button><button class="icon-button" aria-label="View Beatrice Ndagwa"><i class="fa-regular fa-eye"></i></button><button class="icon-button danger-action" aria-label="Delete Beatrice Ndagwa"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="table-footer"><span>Showing 1 to 8 of 12 staff members</span>
            <div class="pagination"><span><i class="fa-solid fa-chevron-left"></i></span><span class="current">1</span><span>2</span><span><i class="fa-solid fa-chevron-right"></i></span></div>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>