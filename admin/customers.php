<?php
require_once __DIR__ . "/includes/auth.php";
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel customers">
  <title>Customers | Starian Hotel Admin</title>
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
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a class="active" href="customers.php" aria-current="page"><i class="fa-solid fa-users"></i>Customers</a><a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a><a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div><form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Manage Customers</h1>
            <p>View and manage all registered customers.</p>
          </div>
          <div class="heading-actions"><label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Search customers..."></label><a class="button-primary" href="#"><i class="fa-solid fa-plus"></i>Add Customer</a></div>
        </div>
        <section class="panel">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Member Since</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="person-avatar"><i class="fa-solid fa-user"></i></span></td>
                  <td><strong>Mary Atieno</strong></td>
                  <td>mary@example.com</td>
                  <td>+254 712 345 678</td>
                  <td>16 Oct 2025</td>
                  <td><span class="badge">Active</span></td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="View Mary Atieno"><i class="fa-regular fa-eye"></i></button><button class="icon-button" aria-label="Edit Mary Atieno"><i class="fa-solid fa-pen"></i></button><button class="icon-button danger-action" aria-label="Delete Mary Atieno"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><span class="person-avatar"><i class="fa-solid fa-user"></i></span></td>
                  <td><strong>Peter Njuguna</strong></td>
                  <td>peter@example.com</td>
                  <td>+254 743 654 321</td>
                  <td>12 Oct 2025</td>
                  <td><span class="badge">Active</span></td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="View Peter Njuguna"><i class="fa-regular fa-eye"></i></button><button class="icon-button" aria-label="Edit Peter Njuguna"><i class="fa-solid fa-pen"></i></button><button class="icon-button danger-action" aria-label="Delete Peter Njuguna"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><span class="person-avatar"><i class="fa-solid fa-user"></i></span></td>
                  <td><strong>Faith Wanjiku</strong></td>
                  <td>faith@example.com</td>
                  <td>+254 723 456 789</td>
                  <td>08 Oct 2025</td>
                  <td><span class="badge">Active</span></td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="View Faith Wanjiku"><i class="fa-regular fa-eye"></i></button><button class="icon-button" aria-label="Edit Faith Wanjiku"><i class="fa-solid fa-pen"></i></button><button class="icon-button danger-action" aria-label="Delete Faith Wanjiku"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><span class="person-avatar"><i class="fa-solid fa-user"></i></span></td>
                  <td><strong>Samuel Kamau</strong></td>
                  <td>samuel@example.com</td>
                  <td>+254 718 987 654</td>
                  <td>05 Oct 2025</td>
                  <td><span class="badge">Active</span></td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="View Samuel Kamau"><i class="fa-regular fa-eye"></i></button><button class="icon-button" aria-label="Edit Samuel Kamau"><i class="fa-solid fa-pen"></i></button><button class="icon-button danger-action" aria-label="Delete Samuel Kamau"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
                <tr>
                  <td><span class="person-avatar"><i class="fa-solid fa-user"></i></span></td>
                  <td><strong>Esther Achieng</strong></td>
                  <td>esther@example.com</td>
                  <td>+254 709 111 222</td>
                  <td>03 Oct 2025</td>
                  <td><span class="badge">Active</span></td>
                  <td>
                    <div class="actions"><button class="icon-button" aria-label="View Esther Achieng"><i class="fa-regular fa-eye"></i></button><button class="icon-button" aria-label="Edit Esther Achieng"><i class="fa-solid fa-pen"></i></button><button class="icon-button danger-action" aria-label="Delete Esther Achieng"><i class="fa-regular fa-trash-can"></i></button></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="table-footer"><span>Showing 1 to 5 of 36 customers</span>
            <div class="pagination"><span><i class="fa-solid fa-chevron-left"></i></span><span class="current">1</span><span>2</span><span>3</span><span><i class="fa-solid fa-chevron-right"></i></span></div>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>