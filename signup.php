<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=6" />
</head>

<body>
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
        <li><a class="loginup" href="login.php">Login</a></li>
        <li><a class="signup" href="signup.php" aria-current="page">Sign Up</a></li>
      </ul>
    </nav>
  </section>
  <section class="hero-section">
    <div class="join">
      <h1>Join <span>Starian Hotel</span></h1>
      <p>
        Create your account and get access to exclusive offers and easy
        booking
      </p>
      <div class="join-book">
        <i class="fa-solid fa-bed"></i>
        <p>Book Rooms</p>
        <i class="fa-solid fa-utensils"></i>
        <p>Order Food</p>
        <i class="fa-solid fa-tags"></i>
        <p>Get Special Discounts</p>
        <i class="fa-solid fa-user-gear"></i>
        <p>Manage Your Profile</p>
      </div>
    </div>

    <div class="signup-part">
      <h1>Create Account</h1>
      <h2>Fill in the details below to get started</h2>
      <div id="notification" role="status" aria-live="polite"></div>

      <form id="signupform" action="php/signup_process.php" method="POST">
        <div class="inputbox">
          <i class="fa-solid fa-user"></i>
          <input
            type="text"
            id="fullname"
            name="fullname"
            placeholder="Full Name"
            required />
        </div>

        <div class="inputbox">
          <i class="fa-solid fa-envelope"></i>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Email Address"
            required />
        </div>

        <div class="inputbox">
          <i class="fa-solid fa-phone-volume"></i>
          <input
            type="tel"
            id="phone"
            name="phone"
            placeholder="Phone Number"
            required />
        </div>

        <div class="inputbox">
          <i class="fa-solid fa-lock"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Password"
            required
            minlength="8" />
          <i id="togglepassword" class="fa-solid fa-eye-slash"></i>
        </div>

        <div class="inputbox">
          <i class="fa-solid fa-lock"></i>
          <input
            type="password"
            id="confirmpassword"
            name="confirmpassword"
            placeholder="Confirm Password"
            required
            minlength="8" />
          <i id="toggleconfirmpassword" class="fa-solid fa-eye-slash"></i>
        </div>

        <button type="submit" class="signup-btn">Sign Up</button>

        <div class="login-text">
          <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
      </form>
    </div>
  </section>

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
        &nbsp; starianhotel@gmail.com
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
  <script src="javascript/signup.js"></script>
</body>

</html>