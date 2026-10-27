<?php

session_start();

$isLoggedIn = isset($_SESSION["user_id"]);

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Starian Hotel | Home</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=6" />
  <link rel="stylesheet" href="css/style.css?v=4" />
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

        <?php if ($isLoggedIn): ?>
          <li>
            <a href="dashboard.php" class="account-link">
              <img src="img/user.png" alt="" class="nav-avatar">
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

  <!-- hero section part -->
  <section class="hero">
    <div class="hero-content">
      <p class="welcome">WELCOME TO</p>

      <h1>Starian <span>Hotel</span></h1>

      <p class="tagline">
        Great food <span>•</span> Comfortable rooms <span>•</span>
        Memorable stays
      </p>

      <a href="rooms.php" class="book-btn"> Book Your Stay </a>
    </div>
  </section>

  <!-- Featured Rooms -->
  <section class="featured-rooms" id="featured-rooms">
    <div class="section-heading">
      <span class="line"></span>
      <h2>Explore Our Rooms</h2>
      <span class="line"></span>
    </div>

    <p class="section-description">
      Find the perfect room for your comfort and relaxation.
    </p>

    <div class="rooms-container">
      <article class="room-card">
        <img src="img/hotelbed.jpg" alt="Comfortable standard hotel room" />

        <div class="room-info">
          <h3>Standard Room</h3>
          <p>A cozy room with a comfortable bed and modern essentials.</p>

          <div class="room-bottom">
            <p><strong>KSh 2,500</strong> <span>/ night</span></p>
            <a href="rooms.php#standard-rooms" class="room-btn"> Explore </a>
          </div>
        </div>
      </article>

      <article class="room-card">
        <img src="img/bedsofa.jpg" alt="Elegant deluxe hotel bedroom" />

        <div class="room-info">
          <h3>Deluxe Room</h3>
          <p>Enjoy extra space, elegant furnishings and a relaxing stay.</p>

          <div class="room-bottom">
            <p><strong>KSh 6,000</strong> <span>/ night</span></p>
            <a href="rooms.php#deluxe-rooms" class="room-btn"> Explore </a>
          </div>
        </div>
      </article>

      <article class="room-card">
        <img
          src="img/Executive Suite.jpg"
          alt="Luxury hotel suite interior" />

        <div class="room-info">
          <h3>Executive Suite</h3>
          <p>Experience premium comfort with a spacious, stylish interior.</p>

          <div class="room-bottom">
            <p><strong>KSh 10,000</strong> <span>/ night</span></p>
            <a href="rooms.php#executive-rooms" class="room-btn"> Explore </a>
          </div>
        </div>
      </article>
    </div>

    <a href="rooms.php" class="view-all-btn">
      Explore All Rooms <i class="fa-solid fa-arrow-right"></i>
    </a>
  </section>

  <!-- Menu section -->
  <section class="menu-section" id="featured-menu">
    <div class="section-heading">
      <span class="line"></span>
      <h2>Our Menu</h2>
      <span class="line"></span>
    </div>

    <p class="section-description">
      Delicious meals, made with fresh ingredients.
    </p>

    <div class="menu-container">
      <button class="food-card" type="button" data-food="chicken">
        <img src="img/grilled chicken.jpg" alt="Grilled chicken with sides" />

        <div class="food-info">
          <h3>Grilled Chicken</h3>
          <p>Tender chicken served with fries and fresh salad.</p>
          <span class="price">KSh 450</span>
          <span class="food-action">View Details</span>
        </div>
      </button>

      <button class="food-card" type="button" data-food="burger">
        <img src="img/burger.jpg" alt="Beef burger" />

        <div class="food-info">
          <h3>Beef Burger</h3>
          <p>Juicy beef patty with vegetables and special sauce.</p>
          <span class="price">KSh 400</span>
          <span class="food-action">View Details</span>
        </div>
      </button>

      <button class="food-card" type="button" data-food="pasta">
        <img src="img/pasta.jpg" alt="Vegetable pasta" />

        <div class="food-info">
          <h3>Vegetable Pasta</h3>
          <p>Pasta prepared with fresh vegetables and herbs.</p>
          <span class="price">KSh 350</span>
          <span class="food-action">View Details</span>
        </div>
      </button>

      <button class="food-card" type="button" data-food="fish">
        <img src="img/fish.jpg" alt="Fish meal with chips" />

        <div class="food-info">
          <h3>Fish and Chips</h3>
          <p>Crispy fish served with golden fries and lemon.</p>
          <span class="price">KSh 450</span>
          <span class="food-action">View Details</span>
        </div>
      </button>

      <button class="food-card" type="button" data-food="salad">
        <img src="img/chicken Caesar salad.jpg" alt="Fresh chicken salad" />

        <div class="food-info">
          <h3>Chicken Caesar Salad</h3>
          <p>Fresh lettuce, chicken, croutons and dressing.</p>
          <span class="price">KSh 350</span>
          <span class="food-action">View Details</span>
        </div>
      </button>
    </div>

    <!-- Food Details Popup -->
    <div class="food-modal" id="foodModal" aria-hidden="true">
      <div
        class="food-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalFoodName">
        <button
          type="button"
          class="close-modal"
          id="closeModal"
          aria-label="Close details">
          &times;
        </button>

        <img src="" alt="" id="modalFoodImage" />

        <div class="modal-food-info">
          <h2 id="modalFoodName">Food Name</h2>
          <p id="modalFoodDescription">Food description</p>

          <p class="modal-price" id="modalFoodPrice">KSh 0</p>

        </div>
      </div>
    </div>

    <a href="menu.html" class="view-all-btn">
      View Full Menu <i class="fa-solid fa-arrow-right"></i>
    </a>
  </section>

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
</body>

</html>