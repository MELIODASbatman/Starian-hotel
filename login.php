<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>login</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css"
    />
    <link rel="stylesheet" href="css/signup.css" />
    <link rel="stylesheet" href="css/login.css" />
  </head>
  <body>
    <section class="navi">
      <img src="img/starianlogo.png" alt="Holel's logo" />
      <h1>Starian <span>Hotel</span></h1>
      <nav class="nav-links">
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="room.html">Rooms</a></li>
          <li><a href="menu.html">Menu</a></li>
          <li><a href="about.html">About</a></li>
          <li><a href="contact.html">Contact</a></li>
          <li><a class="loginup" href="login.html">Login</a></li>
          <li><a class="signup" href="signup.html">Sign Up</a></li>
        </ul>
      </nav>
    </section>
    <section class="hero-section">
      <div class="join">
        <h1>Welcome Back!</h1>
        <p>Login to your account and enjoy an easier booking experience.</p>
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

        <form id="loginform">
          <div class="inputbox">
            <i class="fa-solid fa-envelope"></i>
            <input
              type="email"
              name="email"
              placeholder="Email Address"
              required
            />
          </div>

          <div class="inputbox">
            <i class="fa-solid fa-lock"></i>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Password"
              required
              minlength="8"
            />
            <i
              id="togglePassword"
              class="fa-solid fa-eye-slash"
              aria-label="Show password"
            ></i>
          </div>
          <!--remember and forgot part-->
          <div class="login-option">
            <label class="remember"><input type="checkbox" />Remember Me</label>
            <a href="#">Forgot Password?</a>
          </div>

          <button type="submit" class="login-btn">Login</button>

          <p class="continue">Or Continue with</p>

          <button class="google">
            <i class="fa-brands fa-google"></i> Continue with Google
          </button>

          <button class="face">
            <i class="fa-brands fa-facebook"></i> Continue with Facebook
          </button>

          <div class="login-text">
            <p>Don't have an account? <a href="signup.html">Sign Up</a></p>
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
    <script src="javascript/login.js"></script>
  </body>
</html>
