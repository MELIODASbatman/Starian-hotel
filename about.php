<?php

session_start();

require_once __DIR__ . "/php/avatar_helpers.php";
$avatarFilename = getAvatarFilename($_SESSION["avatar"] ?? null);
$isLoggedIn = isset($_SESSION["user_id"]);

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="Get to know Starian Hotel, where thoughtful hospitality, comfortable rooms and memorable dining come together." />
  <title>About Us | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=9" />
  <link rel="stylesheet" href="css/about.css?v=1" />
</head>

<body class="about-page">
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
    <section class="about-hero" aria-labelledby="about-title">
      <div class="about-hero-content">
        <p class="eyebrow">ABOUT US</p>
        <h1 id="about-title">Starian <span>Hotel</span></h1>
        <p class="about-hero-tagline">
          More than just a stay — it's an experience.
        </p>
      </div>
    </section>

    <section class="story-section" aria-labelledby="story-title">
      <div class="story-copy">
        <span class="section-accent" aria-hidden="true"></span>
        <h2 id="story-title">Our Story</h2>
        <p>
          Starian Hotel was created around a simple idea: a great hotel should
          feel welcoming from the moment you arrive. We bring together warm
          hospitality, thoughtful comforts and inviting spaces to make every
          visit feel special.
        </p>
        <p>
          Whether you are joining us for a meal, a relaxing getaway or a
          longer stay, our team is here to make your time with us comfortable
          and memorable.
        </p>
        <a class="about-button" href="rooms.php">
          Explore Our Rooms
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      </div>

      <figure class="story-image">
        <img
          src="img/Starian Hotel at Golden Dusk.png"
          alt="A bright, elegant lounge with comfortable seating"
          loading="lazy" />
        <figcaption>Space to slow down and feel at home.</figcaption>
      </figure>

      <ul class="highlights" aria-label="The Starian experience">
        <li>
          <span class="highlight-icon">
            <i class="fa-solid fa-bed" aria-hidden="true"></i>
          </span>
          <div>
            <h3>Comfortable Rooms</h3>
            <p>
              Unwind in thoughtfully prepared spaces made for a restful stay.
            </p>
          </div>
        </li>
        <li>
          <span class="highlight-icon">
            <i class="fa-solid fa-utensils" aria-hidden="true"></i>
          </span>
          <div>
            <h3>Delicious Meals</h3>
            <p>Enjoy satisfying favorites and freshly prepared dishes.</p>
          </div>
        </li>
        <li>
          <span class="highlight-icon">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
          </span>
          <div>
            <h3>Warm Hospitality</h3>
            <p>
              Our friendly team is ready to help make your visit a pleasure.
            </p>
          </div>
        </li>
        <li>
          <span class="highlight-icon">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
          </span>
          <div>
            <h3>In Nakuru, Kenya</h3>
            <p>A welcoming place to relax, dine and enjoy your time away.</p>
          </div>
        </li>
      </ul>
    </section>

    <section class="purpose-section" aria-labelledby="purpose-title">
      <div class="section-heading">
        <span class="heading-line" aria-hidden="true"></span>
        <h2 id="purpose-title">Our Mission &amp; Vision</h2>
        <span class="heading-line" aria-hidden="true"></span>
      </div>
      <div class="purpose-grid">
        <article class="purpose-card">
          <span class="purpose-icon">
            <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
          </span>
          <div>
            <h3>Our Mission</h3>
            <p>
              To offer thoughtful hospitality, comfortable stays and memorable
              dining for every guest.
            </p>
          </div>
        </article>
        <article class="purpose-card">
          <span class="purpose-icon">
            <i class="fa-solid fa-eye" aria-hidden="true"></i>
          </span>
          <div>
            <h3>Our Vision</h3>
            <p>
              To be a trusted destination known for genuine care, quality
              experiences and a warm welcome.
            </p>
          </div>
        </article>
      </div>
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