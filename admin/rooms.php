<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Fetch all rooms
$sql = $conn->query("
    SELECT *
    FROM rooms
    ORDER BY id DESC
");

$rooms = [];

if ($sql) {
  while ($row = $sql->fetch_assoc()) {
    $rooms[] = $row;
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel rooms">
  <title>Manage Rooms | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=20261031-rooms">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a class="active" href="rooms.php" aria-current="page"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
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
            <h1>Manage Rooms</h1>
            <p>Add, edit, or manage hotel rooms.</p>
          </div>
          <a class="button-primary" href="add-room.php">
            <i class="fa-solid fa-plus"></i>
            Add New Room
          </a><label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Search rooms..."></label>
        </div>
        <nav class="filter-row" aria-label="Filter rooms"><a class="filter-pill active" href="#">All</a><a class="filter-pill" href="#">Standard</a><a class="filter-pill" href="#">Deluxe</a><a class="filter-pill" href="#">Executive</a><a class="filter-pill" href="#">Family</a></nav>
        <section class="panel">
          <div class="table-wrap">
            <table class="data-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Room Name</th>
              <th>Type</th>
              <th>Price (KSh)</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>

            <?php if (!empty($rooms)): ?>

              <?php foreach ($rooms as $room): ?>

                <?php
                $imageFilename = basename(rawurldecode((string)($room["image"] ?? "")));
                $roomImageUrl = "";

                if ($imageFilename !== "") {
                  if (is_file(__DIR__ . "/../img/rooms/" . $imageFilename)) {
                    $roomImageUrl = "../img/rooms/" . rawurlencode($imageFilename);
                  } elseif (is_file(__DIR__ . "/../img/" . $imageFilename)) {
                    $roomImageUrl = "../img/" . rawurlencode($imageFilename);
                  }
                }
                ?>

                <tr>

                  <!-- Room Image -->
                  <td>
                    <?php if ($roomImageUrl !== ""): ?>
                      <img
                        class="room-thumbnail"
                        src="<?= htmlspecialchars($roomImageUrl, ENT_QUOTES, "UTF-8") ?>"
                        alt="<?= htmlspecialchars($room["room_name"], ENT_QUOTES, "UTF-8") ?>">
                    <?php else: ?>
                      <span class="room-thumbnail-placeholder" role="img" aria-label="No image available for <?= htmlspecialchars($room["room_name"], ENT_QUOTES, "UTF-8") ?>">
                        <i class="fa-solid fa-bed" aria-hidden="true"></i>
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- Room Name -->
                  <td>
                    <strong>
                      <?= htmlspecialchars($room["room_name"]) ?>
                    </strong>
                  </td>

                  <!-- Room Code -->
                  <td>
                    <?= htmlspecialchars($room["room_code"]) ?>
                  </td>

                  <!-- Room Price -->
                  <td>
                    <?= number_format((float)$room["price_per_night"], 2) ?>
                  </td>

                  <!-- Room Status -->
                  <td>
                    <span class="badge">
                      <?= htmlspecialchars(ucfirst($room["status"])) ?>
                    </span>
                  </td>

                  <!-- Actions -->
                  <td>
                    <div class="actions">

                      <button
                        class="icon-button"
                        aria-label="Edit room"
                        title="Edit room">
                        <i class="fa-solid fa-pen"></i>
                      </button>

                      <button
                        class="icon-button danger-action"
                        aria-label="Delete room"
                        title="Delete room">
                        <i class="fa-regular fa-trash-can"></i>
                      </button>

                    </div>
                  </td>

                </tr>

              <?php endforeach; ?>

            <?php else: ?>

              <tr>
                <td colspan="6">No rooms found.</td>
              </tr>

            <?php endif; ?>

          </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>

</html>