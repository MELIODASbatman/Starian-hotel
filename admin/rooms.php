<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

if (empty($_SESSION["room_csrf_token"])) {
  $_SESSION["room_csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

if (($_GET["deleted"] ?? "") === "1") {
  $message = "Room deleted successfully.";
  $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $submittedToken = $_POST["csrf_token"] ?? "";
  $roomId = filter_var($_POST["room_id"] ?? "", FILTER_VALIDATE_INT);

  if (!hash_equals($_SESSION["room_csrf_token"], $submittedToken)) {
    $message = "Invalid request. Please try again.";
    $messageType = "error";
  } elseif (($_POST["action"] ?? "") !== "delete" || !$roomId) {
    $message = "Invalid room request.";
    $messageType = "error";
  } else {
    $roomStmt = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
    if (!$roomStmt) {
      die("Unable to prepare room lookup: " . $conn->error);
    }

    $roomStmt->bind_param("i", $roomId);
    $roomStmt->execute();
    $room = $roomStmt->get_result()->fetch_assoc();
    $roomStmt->close();

    if (!$room) {
      $message = "Room not found.";
      $messageType = "error";
    } else {
      $bookingStmt = $conn->prepare("SELECT COUNT(*) AS booking_count FROM bookings WHERE room_id = ?");
      if (!$bookingStmt) {
        die("Unable to prepare booking lookup: " . $conn->error);
      }

      $bookingStmt->bind_param("i", $roomId);
      $bookingStmt->execute();
      $bookingCount = (int) $bookingStmt->get_result()->fetch_assoc()["booking_count"];
      $bookingStmt->close();

      if ($bookingCount > 0) {
        $message = "This room has booking records and cannot be deleted. You can mark it unavailable instead.";
        $messageType = "error";
      } else {
        $deleteStmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        if (!$deleteStmt) {
          die("Unable to prepare room deletion: " . $conn->error);
        }

        $deleteStmt->bind_param("i", $roomId);

        if ($deleteStmt->execute() && $deleteStmt->affected_rows === 1) {
          $imageFilename = basename(rawurldecode((string) ($room["image"] ?? "")));
          if (
            preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/i', $imageFilename) &&
            is_file(__DIR__ . "/../img/" . $imageFilename)
          ) {
            unlink(__DIR__ . "/../img/" . $imageFilename);
          }

          $deleteStmt->close();
          header("Location: rooms.php?deleted=1");
          exit;
        }

        $message = $deleteStmt->errno === 1451
          ? "This room has booking records and cannot be deleted. You can mark it unavailable instead."
          : "Unable to delete room. Please try again.";
        $messageType = "error";
        $deleteStmt->close();
      }
    }
  }
}

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
  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
  <script src="javascript/admin-search.js?v=20261118-room-filter" defer></script>
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
        <a href="former-staff.php"><i class="fa-solid fa-user-clock"></i>Former Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
       <a href="contact-messages.php"><i class="fa-regular fa-envelope"></i>Contact Messages</a>
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
            <h1>Manage Rooms</h1>
            <p>Add, edit, or manage hotel rooms.</p>
          </div>
          <a class="button-primary" href="add-room.php">
            <i class="fa-solid fa-plus"></i>
            Add New Room
          </a>
          <form class="search-box" role="search" data-table-search="rooms-table">
            <button type="submit" aria-label="Search rooms"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
            <input type="search" name="search" placeholder="Search rooms..." aria-label="Search rooms">
          </form>
        </div>
        <?php if ($message !== ""): ?>
          <div class="room-form-alert <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>
        <nav class="filter-row" aria-label="Filter rooms" data-category-filter-list="rooms-table">
          <button type="button" class="filter-pill active" data-category-filter="all" aria-pressed="true">All</button>
          <button type="button" class="filter-pill" data-category-filter="standard" aria-pressed="false">Standard</button>
          <button type="button" class="filter-pill" data-category-filter="deluxe" aria-pressed="false">Deluxe</button>
          <button type="button" class="filter-pill" data-category-filter="executive" aria-pressed="false">Executive</button>
          <button type="button" class="filter-pill" data-category-filter="family" aria-pressed="false">Family</button>
        </nav>
        <section class="panel">
          <div class="table-wrap">
            <table class="data-table" id="rooms-table">
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

                    <tr data-search-row data-category="<?= htmlspecialchars(strtolower((string) ($room["category"] ?? "")), ENT_QUOTES, "UTF-8") ?>">

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

                          <a
                            href="edit-room.php?id=<?= (int) $room["id"] ?>"
                            class="icon-button"
                            aria-label="Edit <?= htmlspecialchars($room["room_name"], ENT_QUOTES, "UTF-8") ?>"
                            title="Edit room">
                            <i class="fa-solid fa-pen"></i>
                          </a>

                          <form method="POST" action="rooms.php" onsubmit="return confirm('Delete this room?');">
                            <input type="hidden" name="room_id" value="<?= (int) $room["id"] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["room_csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="action" value="delete">
                            <button
                              type="submit"
                              class="icon-button danger-action"
                              aria-label="Delete <?= htmlspecialchars($room["room_name"], ENT_QUOTES, "UTF-8") ?>"
                              title="Delete room">
                              <i class="fa-regular fa-trash-can"></i>
                            </button>
                          </form>

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