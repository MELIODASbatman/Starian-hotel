<?php

session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

require_once "php/db.php";
require_once __DIR__ . "/php/avatar_helpers.php";

$userId = (int) $_SESSION["user_id"];
$profileStatement = $conn->prepare(
  "SELECT fullname, email, phone, password, avatar
   FROM users
   WHERE id = ?
   LIMIT 1"
);

if (!$profileStatement) {
  error_log("Unable to prepare customer profile query: " . $conn->error);
  http_response_code(500);
  exit("Unable to load your profile right now.");
}

$profileStatement->bind_param("i", $userId);

if (!$profileStatement->execute()) {
  error_log("Unable to load customer profile: " . $profileStatement->error);
  http_response_code(500);
  exit("Unable to load your profile right now.");
}

$profile = $profileStatement->get_result()->fetch_assoc();
$profileStatement->close();

if (!$profile) {
  $conn->close();
  unset($_SESSION["user_id"], $_SESSION["fullname"], $_SESSION["email"]);
  header("Location: login.php");
  exit;
}

$_SESSION["fullname"] = $profile["fullname"];
$_SESSION["email"] = $profile["email"];
$avatarFilename = getAvatarFilename($profile["avatar"] ?? null);
$_SESSION["avatar"] = $avatarFilename;
$avatarGender = getAvatarGender($avatarFilename) ?? "male";
$requestedAvatarGender = $_GET["avatar_gender"] ?? $avatarGender;
$selectedAvatarGender = is_string($requestedAvatarGender)
  && in_array($requestedAvatarGender, ["female", "male"], true)
  ? $requestedAvatarGender
  : $avatarGender;

if (!isset($_SESSION["profile_csrf_token"])) {
  $_SESSION["profile_csrf_token"] = bin2hex(random_bytes(32));
}

$notice = $_SESSION["profile_notice"] ?? "";
unset($_SESSION["profile_notice"]);
$error = "";
$postValue = static function (string $key): string {
  $value = $_POST[$key] ?? "";
  return is_string($value) ? $value : "";
};

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $submittedToken = $_POST["csrf_token"] ?? "";

  if (
    !is_string($submittedToken)
    || !hash_equals($_SESSION["profile_csrf_token"], $submittedToken)
  ) {
    http_response_code(400);
    $error = "Your session has expired. Refresh the page and try again.";
  } else {
    $action = $postValue("action");

    if ($action === "save_avatar") {
      $avatar = $postValue("avatar");
      $submittedGender = $postValue("avatar_gender");

      if (
        !in_array($avatar, getAvatarOptions(), true)
        || !in_array($submittedGender, ["female", "male"], true)
        || getAvatarGender($avatar) !== $submittedGender
      ) {
        http_response_code(400);
        $error = "Choose an avatar that matches the selected category.";
      } else {
        $avatarStatement = $conn->prepare(
          "UPDATE users
           SET avatar = ?
           WHERE id = ?"
        );

        if (!$avatarStatement) {
          error_log("Unable to prepare customer avatar update: " . $conn->error);
          http_response_code(500);
          $error = "Unable to update your avatar right now.";
        } else {
          $avatarStatement->bind_param("si", $avatar, $userId);

          if (!$avatarStatement->execute()) {
            error_log("Unable to update customer avatar: " . $avatarStatement->error);
            http_response_code(500);
            $error = "Unable to update your avatar right now.";
          } else {
            $_SESSION["avatar"] = $avatar;
            $_SESSION["profile_notice"] = "Your avatar has been updated.";
            $avatarStatement->close();
            $conn->close();
            header("Location: profile.php");
            exit;
          }

          $avatarStatement->close();
        }
      }
    } elseif ($action === "save_profile") {
      $fullname = trim($postValue("fullname"));
      $email = trim($postValue("email"));
      $phone = trim($postValue("phone"));

      if ($fullname === "" || $email === "" || $phone === "") {
        $error = "Please complete all personal information fields.";
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
      } else {
        $emailCheck = $conn->prepare(
          "SELECT id
           FROM users
           WHERE email = ? AND id != ?
           LIMIT 1"
        );

        if (!$emailCheck) {
          error_log("Unable to prepare profile email check: " . $conn->error);
          http_response_code(500);
          $error = "Unable to update your profile right now.";
        } else {
          $emailCheck->bind_param("si", $email, $userId);

          if (!$emailCheck->execute()) {
            error_log("Unable to check profile email: " . $emailCheck->error);
            http_response_code(500);
            $error = "Unable to update your profile right now.";
          } elseif ($emailCheck->get_result()->num_rows > 0) {
            $error = "That email address is already associated with another account.";
          } else {
            $emailCheck->close();
            $emailCheck = null;
            $updateStatement = $conn->prepare(
              "UPDATE users
               SET fullname = ?, email = ?, phone = ?
               WHERE id = ?"
            );

            if (!$updateStatement) {
              error_log("Unable to prepare profile update: " . $conn->error);
              http_response_code(500);
              $error = "Unable to update your profile right now.";
            } else {
              $updateStatement->bind_param("sssi", $fullname, $email, $phone, $userId);

              if (!$updateStatement->execute()) {
                error_log("Unable to update customer profile: " . $updateStatement->error);
                http_response_code(500);
                $error = "Unable to update your profile right now.";
              } else {
                $_SESSION["fullname"] = $fullname;
                $_SESSION["email"] = $email;
                $_SESSION["profile_notice"] = "Your personal information has been updated.";
                $updateStatement->close();
                $conn->close();
                header("Location: profile.php");
                exit;
              }

              $updateStatement->close();
            }
          }
        }

        if ($emailCheck instanceof mysqli_stmt) {
          $emailCheck->close();
        }
      }

      $profile["fullname"] = $fullname;
      $profile["email"] = $email;
      $profile["phone"] = $phone;
    } elseif ($action === "change_password") {
      $currentPassword = $postValue("current_password");
      $newPassword = $postValue("new_password");
      $confirmPassword = $postValue("confirm_password");

      if (!password_verify($currentPassword, $profile["password"])) {
        $error = "Your current password is incorrect.";
      } elseif (strlen($newPassword) < 8) {
        $error = "Your new password must be at least 8 characters.";
      } elseif ($newPassword !== $confirmPassword) {
        $error = "The new passwords do not match.";
      } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $passwordStatement = $conn->prepare(
          "UPDATE users
           SET password = ?
           WHERE id = ?"
        );

        if (!$passwordStatement) {
          error_log("Unable to prepare customer password update: " . $conn->error);
          http_response_code(500);
          $error = "Unable to change your password right now.";
        } else {
          $passwordStatement->bind_param("si", $hashedPassword, $userId);

          if (!$passwordStatement->execute()) {
            error_log("Unable to update customer password: " . $passwordStatement->error);
            http_response_code(500);
            $error = "Unable to change your password right now.";
          } else {
            $_SESSION["profile_notice"] = "Your password has been updated.";
            $passwordStatement->close();
            $conn->close();
            header("Location: profile.php");
            exit;
          }

          $passwordStatement->close();
        }
      }
    } else {
      http_response_code(400);
      $error = "Invalid profile request.";
    }
  }
}

$conn->close();
$fullname = $profile["fullname"];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Manage your Starian Hotel profile and account settings." />
  <title>My Profile | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/dashboard.css?v=3" />
  <link rel="stylesheet" href="css/profile.css?v=4" />
</head>

<body>
  <header class="navi">
    <img src="img/starianlogo.png" alt="Starian Hotel logo" />
    <h1>Starian <span>Hotel</span></h1>
    <button
      class="nav-toggle"
      type="button"
      aria-label="Open navigation menu"
      aria-expanded="false"
      aria-controls="main-navigation">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav class="nav-links" id="main-navigation" aria-label="Main navigation">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="rooms.php">Rooms</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li>
          <a href="dashboard.php" class="account-link">
            <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" class="nav-avatar" />
            <span><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?></span>
          </a>
        </li>
      </ul>
    </nav>
  </header>

  <div class="dashboard-layout">
    <aside class="sidebar" aria-label="Customer navigation">
      <div class="customer-profile">
        <div class="profile-avatar">
          <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" />
        </div>
        <h3><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?></h3>
        <p>Customer</p>
      </div>
      <nav class="sidebar-nav" aria-label="Account navigation">
        <a href="dashboard.php" class="sidebar-link">
          <i class="fa-solid fa-house" aria-hidden="true"></i>
          <span>Dashboard</span>
        </a>
        <a href="bookings.php" class="sidebar-link">
          <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
          <span>My Bookings</span>
        </a>
        <a href="orders.php" class="sidebar-link">
          <i class="fa-solid fa-utensils" aria-hidden="true"></i>
          <span>My Orders</span>
        </a>
        <a href="profile.php" class="sidebar-link active" aria-current="page">
          <i class="fa-solid fa-user" aria-hidden="true"></i>
          <span>My Profile</span>
        </a>
      </nav>
      <div class="sidebar-promo" aria-label="Good Food, Great Stays, Better Moments">
        <p>Good Food</p>
        <p>Great Stays</p>
        <p class="promo-highlight">Better Moments</p>
      </div>
    </aside>

    <main class="main-info profile-main">
      <div class="profile-heading">
        <div>
          <h2>My Profile</h2>
          <p>Manage your personal information and account settings.</p>
        </div>
      </div>

      <?php if ($notice !== ""): ?>
        <p class="profile-message success-message" role="status">
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
          <?php echo htmlspecialchars($notice, ENT_QUOTES, "UTF-8"); ?>
        </p>
      <?php endif; ?>
      <?php if ($error !== ""): ?>
        <p class="profile-message error-message" role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </p>
      <?php endif; ?>

      <div class="profile-layout">
        <section class="profile-card identity-card" aria-label="Account overview">
          <div class="identity-avatar">
            <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" />
          </div>
          <h3><?php echo htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"); ?></h3>
          <p class="identity-role">Customer</p>
          <span class="membership-badge">
            <i class="fa-solid fa-star" aria-hidden="true"></i>
            Starian Guest
          </span>
          <div class="identity-details">
            <p>
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
              <span><?php echo htmlspecialchars($profile["email"], ENT_QUOTES, "UTF-8"); ?></span>
            </p>
            <p>
              <i class="fa-solid fa-phone" aria-hidden="true"></i>
              <span><?php echo htmlspecialchars($profile["phone"], ENT_QUOTES, "UTF-8"); ?></span>
            </p>
          </div>
          <form method="post" action="logout.php" class="logout-form">
            <input
              type="hidden"
              name="csrf_token"
              value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" />
            <button class="logout-button" type="submit">
              <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
              Log Out
            </button>
          </form>
        </section>

        <div class="profile-forms">
          <section class="profile-card profile-form-card">
            <div class="form-card-heading">
              <div>
                <h3>Choose Your Avatar</h3>
                <p>Choose Male or Female to see matching avatars.</p>
              </div>
              <i class="fa-solid fa-user-astronaut" aria-hidden="true"></i>
            </div>
            <nav class="avatar-gender-picker" aria-label="Filter avatars by category">
              <a
                href="profile.php?avatar_gender=female"
                class="avatar-gender-choice<?php echo $selectedAvatarGender === "female" ? " is-active" : ""; ?>"
                <?php echo $selectedAvatarGender === "female" ? 'aria-current="true"' : ""; ?>>
                Female
              </a>
              <a
                href="profile.php?avatar_gender=male"
                class="avatar-gender-choice<?php echo $selectedAvatarGender === "male" ? " is-active" : ""; ?>"
                <?php echo $selectedAvatarGender === "male" ? 'aria-current="true"' : ""; ?>>
                Male
              </a>
            </nav>
            <form method="post" action="profile.php" class="profile-form">
              <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" />
              <input type="hidden" name="action" value="save_avatar" />
              <input
                type="hidden"
                name="avatar_gender"
                value="<?php echo htmlspecialchars($selectedAvatarGender, ENT_QUOTES, "UTF-8"); ?>" />
              <fieldset class="avatar-options">
                <legend class="visually-hidden">Available avatars</legend>
                <?php foreach (getAvatarOptions() as $option): ?>
                  <?php if (getAvatarGender($option) !== $selectedAvatarGender) continue; ?>
                  <label
                    class="avatar-option"
                    for="avatar-<?php echo htmlspecialchars(pathinfo($option, PATHINFO_FILENAME), ENT_QUOTES, "UTF-8"); ?>">
                    <input
                      type="radio"
                      id="avatar-<?php echo htmlspecialchars(pathinfo($option, PATHINFO_FILENAME), ENT_QUOTES, "UTF-8"); ?>"
                      name="avatar"
                      value="<?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>"
                      <?php echo $avatarFilename === $option ? "checked" : ""; ?>
                      required />
                    <img
                      src="img/avatars/<?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>"
                      alt="<?php echo htmlspecialchars(pathinfo($option, PATHINFO_FILENAME), ENT_QUOTES, "UTF-8"); ?>" />
                  </label>
                <?php endforeach; ?>
              </fieldset>
              <button class="profile-submit" type="submit">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Save Avatar
              </button>
            </form>
          </section>

          <section class="profile-card profile-form-card" id="personal-information">
            <div class="form-card-heading">
              <div>
                <h3>Personal Information</h3>
                <p>Keep your contact details up to date.</p>
              </div>
              <i class="fa-regular fa-id-card" aria-hidden="true"></i>
            </div>
            <form method="post" action="profile.php" class="profile-form">
              <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" />
              <input type="hidden" name="action" value="save_profile" />
              <div class="profile-fields">
                <div class="profile-field">
                  <label for="fullname">Full Name</label>
                  <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    autocomplete="name"
                    value="<?php echo htmlspecialchars($profile["fullname"], ENT_QUOTES, "UTF-8"); ?>"
                    required />
                </div>
                <div class="profile-field">
                  <label for="email">Email Address</label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="<?php echo htmlspecialchars($profile["email"], ENT_QUOTES, "UTF-8"); ?>"
                    required />
                </div>
                <div class="profile-field">
                  <label for="phone">Phone Number</label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    autocomplete="tel"
                    value="<?php echo htmlspecialchars($profile["phone"], ENT_QUOTES, "UTF-8"); ?>"
                    required />
                </div>
              </div>
              <button class="profile-submit" type="submit">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Update Information
              </button>
            </form>
          </section>

          <section class="profile-card profile-form-card">
            <div class="form-card-heading">
              <div>
                <h3>Change Password</h3>
                <p>Choose a new password with at least 8 characters.</p>
              </div>
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
            </div>
            <form method="post" action="profile.php" class="profile-form">
              <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" />
              <input type="hidden" name="action" value="change_password" />
              <div class="password-fields">
                <div class="profile-field">
                  <label for="current-password">Current Password</label>
                  <input
                    type="password"
                    id="current-password"
                    name="current_password"
                    autocomplete="current-password"
                    required />
                </div>
                <div class="profile-field">
                  <label for="new-password">New Password</label>
                  <input
                    type="password"
                    id="new-password"
                    name="new_password"
                    autocomplete="new-password"
                    minlength="8"
                    required />
                </div>
                <div class="profile-field">
                  <label for="confirm-password">Confirm New Password</label>
                  <input
                    type="password"
                    id="confirm-password"
                    name="confirm_password"
                    autocomplete="new-password"
                    minlength="8"
                    required />
                </div>
              </div>
              <button class="profile-submit" type="submit">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                Update Password
              </button>
            </form>
          </section>
        </div>
      </div>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-brand">
      <img src="img/starianlogo.png" alt="Starian Hotel logo" />
      <div>
        <h2>Starian <span>Hotel</span></h2>
        <p>Relax Dine Enjoy</p>
      </div>
    </div>
    <div class="footer-contact">
      <p>
        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
        &nbsp; section 58 Road, Nakuru, Kenya
      </p>
      <p>
        <i class="fa-solid fa-phone" aria-hidden="true"></i>
        &nbsp; +254 754 171 383
      </p>
      <p>
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
        &nbsp; brianmagana048@gmail.com
      </p>
    </div>
    <div class="footer-social">
      <p>Follow Us</p>
      <a href="#" aria-label="Facebook">
        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
      </a>
      <a href="#" aria-label="Instagram">
        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
      </a>
      <a href="#" aria-label="Twitter">
        <i class="fa-brands fa-twitter" aria-hidden="true"></i>
      </a>
    </div>
  </footer>
  <script src="javascript/navigation.js"></script>
</body>

</html>
