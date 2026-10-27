<?php
session_start();

$isLoggedIn = isset($_SESSION["user_id"]);

require_once "php/db.php";

$categories = [
  "Main Dishes",
  "Breakfast",
  "Salads",
  "Drinks"
];

$menuItems = [];

$sql = "SELECT id, food_name, description, category, price, image
        FROM food_menu
        WHERE is_available = 1
        ORDER BY id ASC";

$result = $conn->query($sql);

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="Explore fresh meals, breakfast favourites, salads and drinks at Starian Hotel." />
  <title>Menu | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?v=6" />
  <link rel="stylesheet" href="css/menu.css?v=3" />
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
              <img src="img/user.png" alt="" class="nav-avatar" />
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
    <section class="menu-hero">
      <div class="menu-hero-content">
        <p class="menu-eyebrow">FRESH FROM OUR KITCHEN</p>
        <h2>Our Menu</h2>
        <p>Delicious meals made with fresh ingredients.</p>
        <nav class="breadcrumbs" aria-label="Breadcrumb">
          <a href="index.php">Home</a>
          <span aria-hidden="true">&rsaquo;</span>
          <span aria-current="page">Menu</span>
        </nav>
      </div>
    </section>

    <section class="menu-content" aria-label="Hotel food and drinks">
      <nav class="category-nav" aria-label="Menu categories">
        <a href="#main-dishes">Main Dishes</a>
        <a href="#breakfast">Breakfast</a>
        <a href="#salads">Salads</a>
        <a href="#drinks">Drinks</a>
      </nav>

      <?php
      $categoryIds = [
        "Main Dishes" => "main-dishes",
        "Breakfast" => "breakfast",
        "Salads" => "salads",
        "Drinks" => "drinks"
      ];

      $categoryDescriptions = [
        "Main Dishes" => "FAVOURITES FROM THE KITCHEN",
        "Breakfast" => "A GREAT START TO YOUR DAY",
        "Salads" => "LIGHT, CRISP AND FRESH",
        "Drinks" => "REFRESH AND RECHARGE"
      ];

      foreach ($categories as $category):
        $categoryFoods = array_filter(
          $menuItems,
          function ($food) use ($category) {
            return $food["category"] === $category;
          }
        );
      ?>

        <section
          class="food-category"
          id="<?php echo htmlspecialchars($categoryIds[$category]); ?>">
          <div class="category-heading">
            <div>
              <p class="category-kicker">
                <?php echo htmlspecialchars($categoryDescriptions[$category]); ?>
              </p>

              <h2><?php echo htmlspecialchars($category); ?></h2>
            </div>

            <span>
              <?php echo count($categoryFoods); ?>
              <?php echo $category === "Drinks" ? "drinks" : "dishes"; ?>
            </span>
          </div>

          <div class="food-grid">

            <?php foreach ($categoryFoods as $food): ?>

              <article class="food-card">

                <img
                  src="<?php echo htmlspecialchars($food["image"]); ?>"
                  alt="<?php echo htmlspecialchars($food["food_name"]); ?>"
                  loading="lazy">

                <div class="food-info">

                  <h3
                    data-food-id="<?php echo (int) $food["id"]; ?>">
                    <?php echo htmlspecialchars($food["food_name"]); ?>
                  </h3>

                  <p>
                    <?php echo htmlspecialchars($food["description"]); ?>
                  </p>

                  <strong class="food-price">
                    KSh <?php echo number_format((float) $food["price"]); ?>
                  </strong>

                </div>

              </article>

            <?php endforeach; ?>

          </div>
        </section>

      <?php endforeach; ?>
  </main>

  <button
    class="cart-button"
    id="open-cart"
    type="button"
    aria-label="Open cart, 0 items"
    aria-haspopup="dialog"
    aria-controls="cart-dialog">
    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
    <span class="cart-count" id="cart-count" aria-hidden="true">0</span>
  </button>
  <p class="visually-hidden" id="cart-feedback" role="status" aria-live="polite"></p>

  <dialog class="cart-dialog" id="cart-dialog" aria-labelledby="cart-heading">
    <section class="cart-content">
      <header class="cart-header">
        <div>
          <p class="category-kicker">YOUR SELECTION</p>
          <h2 id="cart-heading">Shopping Cart</h2>
        </div>
        <button
          class="close-cart-button"
          id="close-cart"
          type="button"
          aria-label="Close shopping cart">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </header>
      <p class="empty-order" id="empty-order">
        Your cart is empty. Choose a quantity and add a dish.
      </p>
      <ul
        class="order-items"
        id="order-items"
        aria-label="Cart items"></ul>
      <p class="order-total" id="order-total" hidden></p>
      <div class="cart-actions">
        <button class="clear-order-button" id="clear-order" type="button">
          Clear cart
        </button>
        <button class="close-cart-action" id="continue-shopping" type="button">
          Continue shopping
        </button>
      </div>
      <button class="place-order-button" id="place-order" type="button" disabled>
        Place Order
      </button>
      <p
        class="order-status"
        id="order-status"
        role="status"
        aria-live="polite"></p>
    </section>
  </dialog>

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

  <script src="javascript/menu.js?v=5"></script>
</body>

</html>