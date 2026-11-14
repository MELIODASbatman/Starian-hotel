<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

$message = "";
$messageType = "";

// Process menu item registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $foodName = trim($_POST["food_name"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $price = $_POST["price"] ?? "";
  $isAvailable = isset($_POST["is_available"])
    ? (int) $_POST["is_available"]
    : 0;

  // Validate input
  if (
    $foodName === "" ||
    $description === "" ||
    $category === "" ||
    !is_numeric($price) ||
    !is_finite((float) $price) ||
    (float) $price <= 0 ||
    !in_array($isAvailable, [0, 1], true)
  ) {
    $message = "Please enter valid menu item details.";
    $messageType = "error";
  } elseif (empty($_FILES["food_image"]["name"])) {

    $message = "Please select a food image.";
    $messageType = "error";
  } elseif ($_FILES["food_image"]["error"] !== UPLOAD_ERR_OK) {

    $message = "There was a problem uploading the image.";
    $messageType = "error";
  } elseif ($_FILES["food_image"]["size"] > 5 * 1024 * 1024) {

    $message = "Image size must not exceed 5MB.";
    $messageType = "error";
  } else {

    // Validate image type
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

      // Generate a unique image filename
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

        // Save menu item in database
        $stmt = $conn->prepare("
                    INSERT INTO food_menu
                    (
                        food_name,
                        description,
                        category,
                        price,
                        image,
                        is_available
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

        $stmt->bind_param(
          "sssdsi",
          $foodName,
          $description,
          $category,
          $price,
          $imageName,
          $isAvailable
        );

        if ($stmt->execute()) {

          $message = "Menu item added successfully!";
          $messageType = "success";
        } else {

          // Remove uploaded image if database insertion fails
          unlink($imagePath);

          $message = "Unable to add menu item. Please try again.";
          $messageType = "error";
        }

        $stmt->close();
      } else {

        $message = "Unable to upload the food image.";
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

  <title>Add Menu Item | Starian Hotel Admin</title>

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="admin.css?v=20261113-menu-form">
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

        <a href="#">
          <i class="fa-solid fa-gear"></i>Settings
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

        <a class="topbar-icon" href="#" aria-label="Notifications">
          <i class="fa-regular fa-bell"></i>
          <span class="notification-dot">3</span>
        </a>

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
            <h1>Add New Menu Item</h1>
            <p>Add food and drinks to your hotel menu.</p>
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
            action="add-menu-item.php"
            enctype="multipart/form-data">

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
                  value="<?= htmlspecialchars($_POST["food_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  required>

                <small>Enter the name of the food or drink.</small>

              </div>

              <div class="room-form-field">

                <label for="category">
                  Category *
                </label>

                <input
                  type="text"
                  id="category"
                  name="category"
                  placeholder="Example: Main Course"
                  value="<?= htmlspecialchars($_POST["category"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  required>

                <small>Example: Main Course, Salad, Drinks or Desserts.</small>

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
                  required><?= htmlspecialchars($_POST["description"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>

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
                    value="<?= htmlspecialchars($_POST["price"] ?? "", ENT_QUOTES, "UTF-8") ?>"
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
                    <?= ($_POST["is_available"] ?? "1") === "1" ? "selected" : "" ?>>
                    Available
                  </option>

                  <option value="0"
                    <?= ($_POST["is_available"] ?? "1") === "0" ? "selected" : "" ?>>
                    Unavailable
                  </option>

                </select>

                <small>Choose whether customers can order this item.</small>

              </div>

              <div class="room-form-field room-image-field">

                <label for="food_image">
                  Food image *
                </label>

                <input
                  type="file"
                  id="food_image"
                  name="food_image"
                  accept="image/jpeg,image/png,image/webp"
                  required>

                <small>
                  JPG, PNG or WEBP. Maximum file size: 5MB.
                </small>

              </div>

            </div>

            <div class="room-form-actions">

              <a href="menu-orders.php" class="button-secondary">
                Cancel
              </a>

              <button type="submit" class="button-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Add Menu Item
              </button>

            </div>

          </form>

        </section>

      </main>

    </div>

  </div>

</body>

</html>