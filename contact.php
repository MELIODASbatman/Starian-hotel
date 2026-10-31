<?php
session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
$isLoggedIn = isset($_SESSION["user_id"]);

// Retrieve any notification saved during the previous request.
$successMessage = $_SESSION["contact_success"] ?? "";
$errorMessage = $_SESSION["contact_error"] ?? "";

// Remove the notifications after retrieving them.
unset($_SESSION["contact_success"], $_SESSION["contact_error"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $phone = trim($_POST["phone"] ?? "");
  $message = trim($_POST["message"] ?? "");

  if ($name === "" || $email === "" || $message === "") {

    $_SESSION["contact_error"] =
      "Please fill in your name, email address and message.";
  } elseif (
    !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($name) > 100
    || strlen($email) > 150
    || strlen($phone) > 30
    || strlen($message) > 5000
  ) {

    $_SESSION["contact_error"] =
      "Please check your information and try again. Your message must not exceed 5000 characters.";
  } else {

    require_once __DIR__ . "/php/db.php";

    $stmt = $conn->prepare(
      "INSERT INTO contact_messages (name, email, phone, message)
             VALUES (?, ?, ?, ?)"
    );

    if ($stmt) {

      $stmt->bind_param("ssss", $name, $email, $phone, $message);

      if ($stmt->execute()) {

        $_SESSION["contact_success"] =
          "Thank you! Your message has been received successfully.";
      } else {

        $_SESSION["contact_error"] =
          "Sorry, we could not save your message. Please try again.";
      }

      $stmt->close();
    } else {

      $_SESSION["contact_error"] =
        "Sorry, something went wrong. Please try again.";
    }

    $conn->close();
  }

  // Redirect after processing the submitted form.
  header("Location: contact.php", true, 303);
  exit;
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="Contact Starian Hotel in Nakuru for bookings, questions and assistance." />
  <title>Contact Us | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/contact.css?v=1" />
</head>

<body class="contact-page">
  <section class="navi">
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
    <nav class="nav-links" id="main-navigation">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="rooms.php">Rooms</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>

        <?php if ($isLoggedIn): ?>
          <li>
            <a href="dashboard.php" class="account-link">
              <img src="img/avatars/<?php echo htmlspecialchars($avatarFilename, ENT_QUOTES, "UTF-8"); ?>" alt="" class="nav-avatar" />
              <span><?php echo htmlspecialchars($_SESSION["fullname"] ?? "My Account"); ?></span>
            </a>
          </li>

        <?php else: ?>
          <li><a class="loginup" href="login.php">Login</a></li>
          <li><a class="signup" href="signup.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </section>

  <main>
    <section class="contact-hero" aria-labelledby="contact-title">
      <div class="contact-hero-content">
        <p class="eyebrow">GET IN TOUCH</p>
        <h1 id="contact-title">Contact <span>Us</span></h1>
        <p>
          We'd love to hear from you. Reach out with any questions, booking
          requests or enquiries.
        </p>
      </div>
    </section>

    <section class="contact-section" aria-label="Contact Starian Hotel">
      <div class="contact-details">
        <div class="section-heading">
          <span aria-hidden="true"></span>
          <h2>Our Contact Details</h2>
          <span aria-hidden="true"></span>
        </div>
        <p class="section-intro">
          We are here to help. Reach out through any of the following channels
          and we will get back to you as soon as possible.
        </p>

        <div class="details-grid">
          <article class="detail-item">
            <span class="detail-icon">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            </span>
            <div>
              <h3>Our Location</h3>
              <address>Section 58 Road,<br />Nakuru, Kenya</address>
            </div>
          </article>
          <article class="detail-item">
            <span class="detail-icon">
              <i class="fa-solid fa-phone" aria-hidden="true"></i>
            </span>
            <div>
              <h3>Phone Number</h3>
              <p><a href="tel:+254754171383">+254 754 171 383</a></p>
            </div>
          </article>
          <article class="detail-item">
            <span class="detail-icon">
              <i class="fa-solid fa-envelope" aria-hidden="true"></i>
            </span>
            <div>
              <h3>Email Address</h3>
              <p>
                <a href="mailto:brianmagana048@gmail.com">
                  brianmagana048@gmail.com
                </a>
              </p>
            </div>
          </article>
          <article class="detail-item">
            <span class="detail-icon">
              <i class="fa-solid fa-clock" aria-hidden="true"></i>
            </span>
            <div>
              <h3>Working Hours</h3>
              <p>Open 24/7</p>
              <p>We are always available.</p>
            </div>
          </article>
        </div>

        <ul class="service-points" aria-label="Our service commitments">
          <li>
            <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
            <span>Easy<br />Bookings</span>
          </li>
          <li>
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span>Secure<br />Payments</span>
          </li>
          <li>
            <i class="fa-solid fa-headset" aria-hidden="true"></i>
            <span>24/7<br />Support</span>
          </li>
          <li>
            <i class="fa-solid fa-star" aria-hidden="true"></i>
            <span>Excellent<br />Service</span>
          </li>
        </ul>
      </div>

      <section class="message-card" aria-labelledby="message-title">
        <div class="message-content">
          <div class="section-heading">
            <span aria-hidden="true"></span>
            <h2 id="message-title">Send Us a Message</h2>
            <span aria-hidden="true"></span>
          </div>
          <p class="message-intro">
            Fill in the form below and we'll get back to you shortly.
          </p>

          <?php if ($successMessage !== ""): ?>
            <div class="form-notification success" role="status">
              <?php echo htmlspecialchars($successMessage); ?>
            </div>
          <?php endif; ?>

          <?php if ($errorMessage !== ""): ?>
            <div class="form-notification error" role="alert">
              <?php echo htmlspecialchars($errorMessage); ?>
            </div>
          <?php endif; ?>

          <form
            class="contact-form"
            action="contact.php"
            method="post">
            <div class="form-row">
              <label class="form-field">
                <span class="field-icon">
                  <i class="fa-regular fa-user" aria-hidden="true"></i>
                </span>
                <span class="visually-hidden">Full Name</span>
                <input
                  type="text"
                  name="name"
                  placeholder="Full Name"
                  autocomplete="name"
                  required />
              </label>
              <label class="form-field">
                <span class="field-icon">
                  <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                </span>
                <span class="visually-hidden">Email Address</span>
                <input
                  type="email"
                  name="email"
                  placeholder="Email Address"
                  autocomplete="email"
                  required />
              </label>
            </div>
            <label class="form-field">
              <span class="field-icon">
                <i class="fa-solid fa-phone-volume" aria-hidden="true"></i>
              </span>
              <span class="visually-hidden">Phone Number (optional)</span>
              <input
                type="tel"
                name="phone"
                placeholder="Phone Number (optional)"
                autocomplete="tel" />
            </label>
            <label class="form-field message-field">
              <span class="field-icon">
                <i class="fa-regular fa-message" aria-hidden="true"></i>
              </span>
              <span class="visually-hidden">Your Message</span>
              <textarea
                name="message"
                placeholder="Your Message"
                rows="4"
                required></textarea>
            </label>
            <button class="send-button" type="submit">
              <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
              Send Message
            </button>
          </form>
        </div>
      </section>
    </section>
  </main>

  <!--footer-->
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
        <i class="fa-solid fa-location-dot"></i>
        &nbsp; section 58 Road, Nakuru, Kenya
      </p>

      <p>
        <i class="fa-solid fa-phone"></i>
        &nbsp; +254 754 171 383
      </p>

      <p>
        <i class="fa-solid fa-envelope"></i>
        &nbsp; brianmagana048@gmail.com
      </p>
    </div>

    <div class="footer-social">
      <p>Follow Us</p>

      <a href="#" aria-label="Facebook">
        <i class="fa-brands fa-facebook-f"></i>
      </a>

      <a href="#" aria-label="Instagram">
        <i class="fa-brands fa-instagram"></i>
      </a>

      <a href="#" aria-label="Twitter">
        <i class="fa-brands fa-twitter"></i>
      </a>
    </div>
  </footer>
  <script src="javascript/navigation.js?v=1"></script>
</body>

</html>