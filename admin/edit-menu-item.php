<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$categories = require __DIR__ . "/../php/menu_categories.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

$message = "";
$messageType = "";

// Get the menu item ID
$id = filter_var(
  $_POST["id"] ?? $_GET["id"] ?? "",
  FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
  die("Invalid menu item ID.");
}

// Fetch the existing menu item
$stmt = $conn->prepare("
    SELECT id, food_name, description, category, price, image, is_available
    FROM food_menu
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$item = $result->fetch_assoc();

$stmt->close();

if (!$item) {
  die("Menu item not found.");
}

// Display success message after updating
if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
  $message = "Menu item updated successfully!";
  $messageType = "success";
}

// Process menu item update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $foodName = trim($_POST["food_name"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $price = $_POST["price"] ?? "";
  $availability = $_POST["is_available"] ?? "";

  // Keep submitted values in the form if validation fails
  $item["food_name"] = $foodName;
  $item["description"] = $description;
  $item["category"] = $category;
  $item["price"] = $price;
  $item["is_available"] = (int) $availability;

  if (
    $foodName === "" ||
    $description === "" ||
    !in_array($category, $categories, true) ||
    !is_numeric($price) ||
    !is_finite((float) $price) ||
    (float) $price <= 0 ||
    !in_array($availability, ["0", "1"], true)
  ) {
    $message = "Please enter valid menu item details.";
    $messageType = "error";
  } else {

    // Keep the current image unless a new one is uploaded
    $imageName = $item["image"];
    $newImagePath = null;
    $uploadError = $_FILES["food_image"]["error"] ?? UPLOAD_ERR_NO_FILE;

    if ($uploadError !== UPLOAD_ERR_NO_FILE) {

      if ($uploadError !== UPLOAD_ERR_OK) {
        $message = "There was a problem uploading the image.";
        $messageType = "error";
      } elseif ($_FILES["food_image"]["size"] > 5 * 1024 * 1024) {
        $message = "Image size must not exceed 5MB.";
        $messageType = "error";
      } else {

        $imageInfo = getimagesize($_FILES["food_image"]["tmp_name"]);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES["food_image"]["tmp_name"]);

        $allowedTypes = [
          "image/jpeg" => "jpg",
          "image/png" => "png",
          "image/webp" => "webp"
        ];

        if ($imageInfo === false || !isset($allowedTypes[$mimeType])) {
          $message = "Only JPG, PNG and WEBP images are allowed.";
          $messageType = "error";
        } else {

          $extension = $allowedTypes[$mimeType];
          $imageName = bin2hex(random_bytes(16)) . "." . $extension;

          $uploadDirectory = __DIR__ . "/../img/";
          $imagePath = $uploadDirectory . $imageName;

          if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
          }

          if (move_uploaded_file(
            $_FILES["food_image"]["tmp_name"],
            $imagePath
          )) {
            $newImagePath = $imagePath;
          } else {
            $message = "Unable to upload the new image.";
            $messageType = "error";
          }
        }
      }
    }

    // Update the database if there are no errors
    if ($message === "") {

      $priceValue = (float) $price;
      $isAvailable = (int) $availability;

      $updateStmt = $conn->prepare("
                UPDATE food_menu
                SET
                    food_name = ?,
                    description = ?,
                    category = ?,
                    price = ?,
                    image = ?,
                    is_available = ?
                WHERE id = ?
            ");

      if (!$updateStmt) {

        if ($newImagePath !== null) {
          unlink($newImagePath);
        }

        $message = "Unable to prepare the menu item update.";
        $messageType = "error";
      } else {

        $updateStmt->bind_param(
          "sssdsii",
          $foodName,
          $description,
          $category,
          $priceValue,
          $imageName,
          $isAvailable,
          $id
        );

        if ($updateStmt->execute()) {

          $updateStmt->close();

          header("Location: edit-menu-item.php?id=" . $id . "&updated=1");
          exit;
        } else {

          if ($newImagePath !== null) {
            unlink($newImagePath);
          }

          $message = "Unable to update menu item. Please try again.";
          $messageType = "error";

          $updateStmt->close();
        }
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

  <title>Edit Menu Item | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="css/admin.css?v=<?php echo filemtime(__DIR__ . "/css/admin.css"); ?>">
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
          <strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong>
          <small>Administrator</small>
        </span>
      </div>

      <nav class="side-nav" aria-label="Admin navigation">

        <a href="index.php">
          <i class="fa-solid fa-house"></i>Dashboard
        </a>

        <a href="rooms.php">
          <i class="fa-solid fa-bed"></i>Rooms
        </a>

        <a class="active" href="menu-orders.php" aria-current="page">
          <i class="fa-solid fa-utensils"></i>Menu / Orders
        </a>

        <a href="bookings.php">
          <i class="fa-regular fa-calendar-check"></i>Bookings
        </a>

        <a href="customers.php">
          <i class="fa-solid fa-users"></i>Customers
        </a>

        <a href="staff.php">
          <i class="fa-solid fa-user-group"></i>Staff
        </a>

        <a href="reports.php">
          <i class="fa-solid fa-chart-column"></i>Reports
        </a>
        <a href="contact-messages.php">
          <i class="fa-regular fa-envelope"></i>Contact Messages
        </a>

      </nav>

      <p class="sidebar-motto">
        Better Service<span>Better Stays</span>
      </p>

    </aside>

    <div class="workspace">

      <header class="topbar">

        <label class="menu-toggle" for="admin-menu"
          aria-label="Toggle admin menu">
          <i class="fa-solid fa-bars"></i>
        </label>

        <span class="topbar-spacer"></span>

        <?php require __DIR__ . "/includes/notification-widget.php"; ?>

        <div class="top-admin">
          <span class="admin-avatar">
            <i class="fa-solid fa-user-shield"></i>
          </span>

          <span>
            <strong><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></strong>
            <small>Administrator</small>
          </span>
        </div>

        <form class="admin-logout-form" method="post" action="../logout.php">
          <input type="hidden" name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["profile_csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>">

          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>

      </header>

      <main class="page-content">

        <div class="page-heading room-form-heading">

          <div>
            <h1>Edit Menu Item</h1>
            <p>Update the details of this food or drink.</p>
          </div>

          <a href="menu-orders.php" class="button-secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Menu
          </a>

        </div>

        <?php if ($message !== ""): ?>

          <div class="room-form-alert <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>"
            role="status">

            <i class="fa-solid <?= $messageType === "success"
                                  ? "fa-circle-check"
                                  : "fa-circle-exclamation" ?>"
              aria-hidden="true"></i>

            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>

          </div>

        <?php endif; ?>

        <section class="panel room-form-panel">

          <div class="room-form-intro">

            <span class="room-form-icon">
              <i class="fa-solid fa-utensils" aria-hidden="true"></i>
            </span>

            <div>
              <h2>Food details</h2>
              <p>Fields marked with * are required.</p>
            </div>

          </div>

          <form class="room-form"
            method="POST"
            action="edit-menu-item.php?id=<?php echo (int) $id; ?>"
            enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

            <div class="room-form-grid">

              <div class="room-form-field">

                <label for="food_name">
                  Food name *
                </label>

                <input
                  type="text"
                  id="food_name"
                  name="food_name"
                  placeholder="Example: Grilled Chicken"
                  value="<?= htmlspecialchars($_POST["food_name"] ?? $item["food_name"], ENT_QUOTES, "UTF-8") ?>"
                  required>

                <small>Enter the name of the food or drink.</small>

              </div>

              <div class="room-form-field">

                <label for="category">
                  Category *
                </label>

                <select
                  id="category"
                  name="category"
                  required>
                  <option value="" disabled
                    <?= empty($_POST["category"]) && empty($item["category"]) ? "selected" : "" ?>>
                    Select a category
                  </option>
                  <?php foreach ($categories as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>"
                      <?= ($_POST["category"] ?? $item["category"]) === $option ? "selected" : "" ?>>
                      <?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <small>Choose the menu section where this item should appear.</small>

              </div>

              <div class="form-group">

                <label for="description">
                  Food description *
                </label>

                <textarea
                  name="description"
                  id="description"
                  rows="4"
                  placeholder="Describe the food or drink..."
                  required><?= htmlspecialchars($_POST["description"] ?? $item["description"], ENT_QUOTES, "UTF-8") ?></textarea>

              </div>

              <div class="room-form-field">

                <label for="price">
                  Price (KSh) *
                </label>

                <div class="room-price-input">
                  <span>KSh</span>

                  <input
                    type="number"
                    id="price"
                    name="price"
                    min="0.01"
                    step="0.01"
                    placeholder="450.00"
                    value="<?= htmlspecialchars($_POST["price"] ?? $item["price"], ENT_QUOTES, "UTF-8") ?>"
                    required>
                </div>

                <small>Enter the selling price of the item.</small>

              </div>

              <div class="room-form-field">

                <label for="is_available">
                  Availability *
                </label>

                <select id="is_available" name="is_available" required>

                  <option value="1"
                    <?= (string) ($_POST["is_available"] ?? $item["is_available"] ?? "1") === "1" ? "selected" : "" ?>>
                    Available
                  </option>

                  <option value="0"
                    <?= (string) ($_POST["is_available"] ?? $item["is_available"] ?? "1") === "0" ? "selected" : "" ?>>
                    Unavailable
                  </option>

                </select>

                <small>Choose whether customers can order this item.</small>

              </div>

              <div class="room-form-field room-image-field">

                <label for="food_image">
                  Replace food image (optional)
                </label>

                <div>
                  <img
                    src="../img/<?= htmlspecialchars(basename($item["image"]), ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($item["food_name"], ENT_QUOTES, "UTF-8") ?>"
                    style="width: 150px; height: 110px; object-fit: cover; border-radius: 8px;">
                </div>

                <input
                  type="file"
                  id="food_image"
                  name="food_image"
                  accept="image/jpeg,image/png,image/webp">

                <small>
                  Leave empty to keep the current image. JPG, PNG or WEBP.
                  Maximum file size: 5MB.
                </small>

              </div>

            </div>

            <div class="room-form-actions">

              <a href="menu-orders.php" class="button-secondary">
                Cancel
              </a>

              <button type="submit" class="button-primary">
                <i class="fa-solid fa-save" aria-hidden="true"></i>
                Update Menu Item
              </button>

            </div>

          </form>

        </section>

      </main>

    </div>

  </div>

</body>

</html>