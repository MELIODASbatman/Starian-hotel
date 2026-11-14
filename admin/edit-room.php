<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

if (empty($_SESSION["room_csrf_token"])) {
  $_SESSION["room_csrf_token"] = bin2hex(random_bytes(32));
}

$roomId = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if (!$roomId) {
  http_response_code(400);
  exit("Invalid room id.");
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
if (!$stmt) {
  die("Unable to prepare room lookup: " . $conn->error);
}

$stmt->bind_param("i", $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
  http_response_code(404);
  exit("Room not found.");
}

$adminName = $_SESSION["fullname"] ?? "Administrator";
$allowedCategories = ["standard", "deluxe", "executive", "family"];
$allowedStatuses = ["available", "unavailable", "maintenance"];
$message = "";
$messageType = "";
$formValues = $room;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  foreach ([
    "room_code",
    "room_name",
    "category",
    "description",
    "bed_type",
    "room_details",
    "amenities",
    "status",
    "price_per_night"
  ] as $field) {
    $formValues[$field] = trim($_POST[$field] ?? "");
  }
  $formValues["wifi"] = isset($_POST["wifi"]) ? 1 : 0;
  $formValues["room_service"] = isset($_POST["room_service"]) ? 1 : 0;

  if (!hash_equals($_SESSION["room_csrf_token"], $_POST["csrf_token"] ?? "")) {
    $message = "Invalid request. Please try again.";
    $messageType = "error";
  } elseif (
    $formValues["room_code"] === "" ||
    $formValues["room_name"] === "" ||
    !in_array($formValues["category"], $allowedCategories, true) ||
    !in_array($formValues["status"], $allowedStatuses, true) ||
    $formValues["description"] === "" ||
    $formValues["bed_type"] === "" ||
    $formValues["room_details"] === "" ||
    $formValues["amenities"] === "" ||
    !is_numeric($formValues["price_per_night"]) ||
    !is_finite((float) $formValues["price_per_night"]) ||
    (float) $formValues["price_per_night"] <= 0
  ) {
    $message = "Please enter valid room details.";
    $messageType = "error";
  } else {
    $uploadError = $_FILES["room_image"]["error"] ?? UPLOAD_ERR_NO_FILE;
    $newImageName = null;
    $imageExtension = null;

    if (!in_array($uploadError, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
      $message = "There was a problem uploading the image.";
      $messageType = "error";
    } elseif ($uploadError === UPLOAD_ERR_OK) {
      if ($_FILES["room_image"]["size"] > 5 * 1024 * 1024) {
        $message = "Image size must not exceed 5MB.";
        $messageType = "error";
      } else {
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
          $imageExtension = $allowedTypes[$mimeType];
          $newImageName = bin2hex(random_bytes(16)) . "." . $imageExtension;
        }
      }
    }

    if ($message === "") {
      $imageName = $newImageName ?? $room["image"];

      if ($newImageName !== null) {
        $uploadDirectory = __DIR__ . "/../img/";
        if (!is_dir($uploadDirectory)) {
          mkdir($uploadDirectory, 0755, true);
        }

        if (!move_uploaded_file(
          $_FILES["room_image"]["tmp_name"],
          $uploadDirectory . $newImageName
        )) {
          $message = "Unable to upload the room image.";
          $messageType = "error";
        }
      }

      if ($message === "") {
        $updateStmt = $conn->prepare("
          UPDATE rooms
          SET room_code = ?, room_name = ?, price_per_night = ?, status = ?,
              image = ?, category = ?, description = ?, bed_type = ?,
              wifi = ?, room_service = ?, room_details = ?, amenities = ?
          WHERE id = ?
        ");

        if (!$updateStmt) {
          if ($newImageName !== null) {
            unlink(__DIR__ . "/../img/" . $newImageName);
          }
          die("Unable to prepare room update: " . $conn->error);
        }

        $price = $formValues["price_per_night"];
        $wifi = $formValues["wifi"];
        $roomService = $formValues["room_service"];
        $updateStmt->bind_param(
          "ssdsssssiissi",
          $formValues["room_code"],
          $formValues["room_name"],
          $price,
          $formValues["status"],
          $imageName,
          $formValues["category"],
          $formValues["description"],
          $formValues["bed_type"],
          $wifi,
          $roomService,
          $formValues["room_details"],
          $formValues["amenities"],
          $roomId
        );

        if ($updateStmt->execute()) {
          if (
            $newImageName !== null &&
            $room["image"] !== $newImageName
          ) {
            $oldImageFilename = basename(rawurldecode((string) $room["image"]));
            if (
              preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/i', $oldImageFilename) &&
              is_file(__DIR__ . "/../img/" . $oldImageFilename)
            ) {
              unlink(__DIR__ . "/../img/" . $oldImageFilename);
            }
          }

          $room = array_merge($formValues, ["image" => $imageName]);
          $message = "Room updated successfully.";
          $messageType = "success";
        } else {
          if ($newImageName !== null) {
            unlink(__DIR__ . "/../img/" . $newImageName);
          }

          $message = $updateStmt->errno === 1062
            ? "This room code is already in use."
            : "Unable to update room. Please try again.";
          $messageType = "error";
        }

        $updateStmt->close();
      }
    }
  }
}

$imageFilename = basename(rawurldecode((string) ($room["image"] ?? "")));
$roomImageUrl = "";
if ($imageFilename !== "") {
  if (is_file(__DIR__ . "/../img/rooms/" . $imageFilename)) {
    $roomImageUrl = "../img/rooms/" . rawurlencode($imageFilename);
  } elseif (is_file(__DIR__ . "/../img/" . $imageFilename)) {
    $roomImageUrl = "../img/" . rawurlencode($imageFilename);
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Room | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=20261115-room-edit">
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
            <h1>Edit Room</h1>
            <p>Update room information, availability, or image.</p>
          </div>
          <a href="rooms.php" class="button-secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Rooms
          </a>
        </div>

        <?php if ($message !== ""): ?>
          <div class="room-form-alert <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>

        <section class="panel room-form-panel">
          <div class="room-form-intro">
            <span class="room-form-icon"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
            <div>
              <h2><?= htmlspecialchars($formValues["room_name"], ENT_QUOTES, "UTF-8") ?></h2>
              <p>Fields marked with * are required.</p>
            </div>
          </div>

          <form class="room-form" method="POST" action="edit-room.php?id=<?= (int) $roomId ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["room_csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <div class="room-form-grid">
              <div class="room-form-field">
                <label for="room_code">Room code *</label>
                <input type="text" id="room_code" name="room_code" value="<?= htmlspecialchars($formValues["room_code"], ENT_QUOTES, "UTF-8") ?>" required>
              </div>

              <div class="room-form-field">
                <label for="room_name">Room name *</label>
                <input type="text" id="room_name" name="room_name" value="<?= htmlspecialchars($formValues["room_name"], ENT_QUOTES, "UTF-8") ?>" required>
              </div>

              <div class="form-group">
                <label for="category">Room category *</label>
                <select id="category" name="category" required>
                  <?php foreach ($allowedCategories as $category): ?>
                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>" <?= $formValues["category"] === $category ? "selected" : "" ?>>
                      <?= htmlspecialchars(ucfirst($category), ENT_QUOTES, "UTF-8") ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="description">Short description *</label>
                <textarea id="description" name="description" rows="3" required><?= htmlspecialchars($formValues["description"], ENT_QUOTES, "UTF-8") ?></textarea>
              </div>

              <div class="form-group">
                <label for="bed_type">Bed type *</label>
                <input type="text" id="bed_type" name="bed_type" value="<?= htmlspecialchars($formValues["bed_type"], ENT_QUOTES, "UTF-8") ?>" required>
              </div>

              <div class="form-group">
                <label for="room_details">Room details *</label>
                <textarea id="room_details" name="room_details" rows="4" required><?= htmlspecialchars($formValues["room_details"], ENT_QUOTES, "UTF-8") ?></textarea>
              </div>

              <div class="form-group">
                <label for="amenities">Room amenities *</label>
                <textarea id="amenities" name="amenities" rows="3" required><?= htmlspecialchars($formValues["amenities"], ENT_QUOTES, "UTF-8") ?></textarea>
              </div>

              <div class="form-group">
                <label>Room facilities</label>
                <div class="checkbox-group">
                  <label class="checkbox-item">
                    <input type="checkbox" name="wifi" value="1" <?= (int) $formValues["wifi"] === 1 ? "checked" : "" ?>>
                    <span>Free Wi-Fi</span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" name="room_service" value="1" <?= (int) $formValues["room_service"] === 1 ? "checked" : "" ?>>
                    <span>Room service</span>
                  </label>
                </div>
              </div>

              <div class="room-form-field">
                <label for="price_per_night">Price per night (KSh) *</label>
                <div class="room-price-input">
                  <span>KSh</span>
                  <input type="number" id="price_per_night" name="price_per_night" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $formValues["price_per_night"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                  <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>" <?= $formValues["status"] === $status ? "selected" : "" ?>>
                      <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, "UTF-8") ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="room-form-field room-image-field">
                <label for="room_image">Replace room image</label>
                <?php if ($roomImageUrl !== ""): ?>
                  <img class="room-thumbnail" src="<?= htmlspecialchars($roomImageUrl, ENT_QUOTES, "UTF-8") ?>" alt="Current image for <?= htmlspecialchars($room["room_name"], ENT_QUOTES, "UTF-8") ?>">
                <?php endif; ?>
                <input type="file" id="room_image" name="room_image" accept="image/jpeg,image/png,image/webp">
                <small>Optional. JPG, PNG or WEBP; maximum 5MB.</small>
              </div>
            </div>

            <div class="room-form-actions">
              <a href="rooms.php" class="button-secondary">Cancel</a>
              <button type="submit" class="button-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Save Changes
              </button>
            </div>
          </form>
        </section>
      </main>
    </div>
  </div>
</body>

</html>
