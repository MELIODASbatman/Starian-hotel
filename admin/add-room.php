<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

$message = "";
$messageType = "";

// Process room registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $roomCode = trim($_POST["room_code"] ?? "");
  $roomName = trim($_POST["room_name"] ?? "");
  $price = $_POST["price_per_night"] ?? "";

  // Validate input
  if ($roomCode === "" || $roomName === "" || !is_numeric($price) || (float)$price <= 0) {

    $message = "Please enter valid room details.";
    $messageType = "error";
  } elseif (empty($_FILES["room_image"]["name"])) {

    $message = "Please select a room image.";
    $messageType = "error";
  } elseif ($_FILES["room_image"]["error"] !== UPLOAD_ERR_OK) {

    $message = "There was a problem uploading the image.";
    $messageType = "error";
  } elseif ($_FILES["room_image"]["size"] > 5 * 1024 * 1024) {

    $message = "Image size must not exceed 5MB.";
    $messageType = "error";
  } else {

    // Validate image type
    $imageInfo = getimagesize($_FILES["room_image"]["tmp_name"]);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($_FILES["room_image"]["tmp_name"]);

    $allowedTypes = [
      "image/jpeg" => "jpg",
      "image/png" => "png",
      "image/webp" => "webp"
    ];

    if ($imageInfo === false || !isset($allowedTypes[$mimeType])) {

      $message = "Only JPG, PNG and WEBP images are allowed.";
      $messageType = "error";
    } else {

      // Generate a unique image filename
      $extension = $allowedTypes[$mimeType];
      $imageName = bin2hex(random_bytes(16)) . "." . $extension;

      $uploadDirectory = __DIR__ . "/../img/rooms/";
      $imagePath = $uploadDirectory . $imageName;

      if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
      }

      if (move_uploaded_file($_FILES["room_image"]["tmp_name"], $imagePath)) {

        // Save room details in database
        $stmt = $conn->prepare("
                    INSERT INTO rooms
                    (room_code, room_name, price_per_night, status, image)
                    VALUES (?, ?, ?, 'available', ?)
                ");

        $price = (float)$price;

        $stmt->bind_param(
          "ssds",
          $roomCode,
          $roomName,
          $price,
          $imageName
        );

        if ($stmt->execute()) {

          $message = "Room added successfully!";
          $messageType = "success";
        } else {

          // Remove uploaded image if database insertion fails
          unlink($imagePath);

          if ($stmt->errno === 1062) {
            $message = "This room code already exists.";
          } else {
            $message = "Unable to add room. Please try again.";
          }

          $messageType = "error";
        }

        $stmt->close();
      } else {

        $message = "Unable to upload the room image.";
        $messageType = "error";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Add Room | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="admin.css?v=20261031-rooms">
</head>

<body>

  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity">
        <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
        <span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong><small>Administrator</small></span>
      </div>
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
      <header class="topbar">
        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label>
        <span class="topbar-spacer"></span>
        <a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin">
          <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong><small>Administrator</small></span>
        </div>
        <form class="admin-logout-form" method="post" action="../logout.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8") ?>">
          <button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button>
        </form>
      </header>

      <main class="page-content">
        <div class="page-heading room-form-heading">
          <div>
            <h1>Add New Room</h1>
            <p>Add a room and its nightly rate to your hotel inventory.</p>
          </div>
          <a href="rooms.php" class="button-secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Rooms
          </a>
        </div>

        <?php if ($message !== ""): ?>
          <div class="room-form-alert <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>" role="status">
            <i class="fa-solid <?= $messageType === "success" ? "fa-circle-check" : "fa-circle-exclamation" ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>

        <section class="panel room-form-panel">
          <div class="room-form-intro">
            <span class="room-form-icon"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
            <div>
              <h2>Room details</h2>
              <p>Fields marked with <span aria-hidden="true">*</span> are required.</p>
            </div>
          </div>

          <form class="room-form" method="POST" action="add-room.php" enctype="multipart/form-data">
            <div class="room-form-grid">
              <div class="room-form-field">
                <label for="room_code">Room code <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="room_code"
                  name="room_code"
                  placeholder="Example: RM001"
                  value="<?= htmlspecialchars($_POST["room_code"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  autocomplete="off"
                  required>
                <small>A unique code used to identify this room.</small>
              </div>

              <div class="room-form-field">
                <label for="room_name">Room name <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="room_name"
                  name="room_name"
                  placeholder="Example: Deluxe Room"
                  value="<?= htmlspecialchars($_POST["room_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  required>
                <small>Choose a clear name guests and staff will recognize.</small>
              </div>

              <div class="room-form-field">
                <label for="price_per_night">Price per night (KSh) <span aria-hidden="true">*</span></label>
                <div class="room-price-input">
                  <span>KSh</span>
                  <input
                    type="number"
                    id="price_per_night"
                    name="price_per_night"
                    min="0.01"
                    step="0.01"
                    placeholder="3,500.00"
                    value="<?= htmlspecialchars($_POST["price_per_night"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    required>
                </div>
                <small>Enter the room's rate for one night.</small>
              </div>

              <div class="room-form-field room-image-field">
                <label for="room_image">Room image <span aria-hidden="true">*</span></label>
                <input
                  type="file"
                  id="room_image"
                  name="room_image"
                  accept="image/jpeg,image/png,image/webp"
                  aria-describedby="room-image-help"
                  required>
                <small id="room-image-help">JPG, PNG or WEBP. Maximum file size: 5MB.</small>
              </div>
            </div>

            <div class="room-form-actions">
              <a href="rooms.php" class="button-secondary">Cancel</a>
              <button type="submit" class="button-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Room
              </button>
            </div>
          </form>
        </section>
      </main>
    </div>
  </div>

</body>

</html>