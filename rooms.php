<?php
session_start();

require_once "php/db.php";

$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Room | Starian Hotel</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
  <link rel="stylesheet" href="css/signup.css?" />
  <link rel="stylesheet" href="css/rooms.css?" />
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

  <!-- hero section -->
  <section class="rooms-hero">
    <div class="rooms-hero-content">
      <h1>Our Rooms</h1>
      <p>Comfortable rooms for a relaxing stay</p>
      <div class="rooms-breadcrumb">
        <a href="index.php">Home</a>
        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
        <span>Rooms</span>
      </div>
    </div>
  </section>

  <!-- booking -->
  <section class="booking-search" id="booking">
    <div class="booking-heading">
      <span class="booking-icon">
        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
      </span>
      <div>
        <h2>Plan Your Stay</h2>
        <p>
          Choose a room here or from the list below, then confirm your dates
          and arrival time.
        </p>
      </div>
    </div>

    <form class="booking-form" id="availability-form">
      <div class="booking-field">
        <label for="room-category">Room Category</label>
        <select id="room-category" name="room_category" required>
          <option value="">Choose a category</option>
          <option value="standard">Standard Room</option>
          <option value="deluxe">Deluxe Room</option>
          <option value="executive">Executive Room</option>
          <option value="family">Family Room</option>
        </select>
      </div>

      <div class="booking-field">
        <label for="room-option">Room Option</label>
        <select id="room-option" name="room_option" required>
          <option value="">Choose a room</option>
          <optgroup label="Standard Rooms">
            <option value="standard-1">Simple bedroom</option>
            <option value="standard-2">Window view room</option>
            <option value="standard-3">Spacious classic room</option>
            <option value="standard-4">Classic room</option>
          </optgroup>
          <optgroup label="Deluxe Rooms">
            <option value="deluxe-1">Simple deluxe Room</option>
            <option value="deluxe-2">Greyish deluxe Room</option>
            <option value="deluxe-3">Spacious deluxe Room</option>
            <option value="deluxe-4">Wooden deluxe Room</option>
          </optgroup>
          <optgroup label="Executive Rooms">
            <option value="executive-1">Cave window view room</option>
            <option value="executive-2">Spacious executive Room</option>
            <option value="executive-3">Simple executive Room</option>
            <option value="executive-4">Morden executive Room</option>
          </optgroup>
          <optgroup label="Family Rooms">
            <option value="family-1">Classic family room</option>
            <option value="family-2">Simple family room</option>
            <option value="family-3">luxurious family room</option>
            <option value="family-4">Spacious Family Room</option>
          </optgroup>
        </select>
      </div>

      <div class="booking-field">
        <label for="check-in">Check-in Date</label>
        <input type="date" id="check-in" name="check_in" required />
      </div>

      <div class="booking-field">
        <label for="check-out">Check-out Date</label>
        <input type="date" id="check-out" name="check_out" required />
      </div>

      <div class="booking-field">
        <label for="arrival-time">Arrival Time</label>
        <input type="time" id="arrival-time" name="arrival_time" required />
      </div>

      <div class="booking-actions">
        <button
          type="button"
          class="availability-btn"
          id="check-availability-btn">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          Check Availability
        </button>
        <button type="button" class="availability-btn" id="book-now-btn">
          <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
          Book Now
        </button>
      </div>
    </form>

    <div
      class="availability-message"
      id="availability-result"
      aria-live="polite">
      Pick a room here, or click “Choose Room” on any room below and it will
      be filled in for you. Then confirm your dates and time.
    </div>
  </section>

  <div
    class="booking-notification"
    id="booking-notification"
    role="status"
    aria-live="polite"
    aria-atomic="true"
    hidden>
    <span class="booking-notification-icon" aria-hidden="true"></span>
    <div class="booking-notification-content">
      <strong class="booking-notification-title" id="booking-notification-title"></strong>
      <p id="booking-notification-message"></p>
    </div>
    <button
      class="booking-notification-close"
      id="booking-notification-close"
      type="button"
      aria-label="Dismiss notification">
      &times;
    </button>
  </div>

  <main class="room-catalogue">
    <!-- STANDARD ROOMS -->
    <section class="room-category" id="standard-rooms">
      <div class="category-heading">
        <div class="category-title">
          <h2>Standard Rooms</h2>
          <p>Comfortable and well-furnished rooms for everyday stays.</p>
        </div>
        <span class="category-count">4 Room Options</span>
      </div>

      <div class="room-grid">
        <article class="room-card" id="standard-1">
          <div class="room-photo">
            <img
              src="img/bed.jpg"
              alt="Comfortable standard hotel room"
              loading="lazy" />
            <span class="room-number">Option 01</span>
          </div>
          <div class="room-info">
            <h3>Simple bedroom</h3>
            <p class="room-description">
              A cosy bedroom with warm lighting and a relaxing atmosphere.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 Queen Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  Relax in a welcoming room with practical furnishings and a
                  comfortable sleeping area.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Bedside lighting</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 2,500</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="standard-1">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="standard-2">
          <div class="room-photo">
            <img
              src="img/sbed2.jpg"
              alt="Standard hotel room with neutral decor"
              loading="lazy" />
            <span class="room-number">Option 02</span>
          </div>
          <div class="room-info">
            <h3>Window view room</h3>
            <p class="room-description">
              Has a nice window view room. Well litted
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 Queen Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A neat and practical room for guests looking for a restful
                  place to stay.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Window view</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 5,800</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="standard-2">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="standard-3">
          <div class="room-photo">
            <img
              src="img/sbed3.jpg"
              alt="Standard hotel room with modern interior"
              loading="lazy" />
            <span class="room-number">Option 03</span>
          </div>
          <div class="room-info">
            <h3>Spacious classic room</h3>
            <p class="room-description">
              A welcoming space with practical furniture and soft colours.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 Queen Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A relaxed room with a simple design suited to short visits
                  and business trips.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Wardrobe</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 3,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="standard-3">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="standard-4">
          <div class="room-photo">
            <img
              src="img/sbed4.jpg"
              alt="Classic standard hotel bedroom"
              loading="lazy" />
            <span class="room-number">Option 04</span>
          </div>
          <div class="room-info">
            <h3>Classic room</h3>
            <p class="room-description">
              A classic hotel room with a calm and comfortable design.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 Queen Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A comfortable option with a classic layout and a restful
                  atmosphere.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Bedside lighting</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 4,500</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="standard-4">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- DELUXE ROOMS -->
    <section class="room-category" id="deluxe-rooms">
      <div class="category-heading">
        <div class="category-title">
          <h2>Deluxe Rooms</h2>
          <p>Elegant interiors, extra comfort and stylish furnishings.</p>
        </div>
        <span class="category-count">4 Room Options</span>
      </div>

      <div class="room-grid">
        <article class="room-card" id="deluxe-1">
          <div class="room-photo">
            <img
              src="img/dbed.jpg"
              alt="Deluxe room with elegant bed"
              loading="lazy" />
            <span class="room-number">Option 01</span>
          </div>
          <div class="room-info">
            <h3>Simple deluxe Room</h3>
            <p class="room-description">
              An elegant bedroom with a nice lighting
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 King Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  Enjoy upgraded furnishings and a spacious sleeping area for
                  a more comfortable stay.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Work desk</li>
                  <li>Wardrobe and luggage space</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 6,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="deluxe-1">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="deluxe-2">
          <div class="room-photo">
            <img
              src="img/dbed2.jpg"
              alt="Deluxe room with spacious sleeping area"
              loading="lazy" />
            <span class="room-number">Option 02</span>
          </div>
          <div class="room-info">
            <h3>Greyish deluxe Room</h3>
            <p class="room-description">
              A spacious bedroom with refined decor and a relaxing feel.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 King Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A well-appointed room with extra space to unwind after a
                  busy day.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Comfortable seating</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 8,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="deluxe-2">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="deluxe-3">
          <div class="room-photo">
            <img
              src="img/dbed3.jpg"
              alt="Deluxe room with contemporary decor"
              loading="lazy" />
            <span class="room-number">Option 03</span>
          </div>
          <div class="room-info">
            <h3>Spacious deluxe Room</h3>
            <p class="room-description">
              A contemporary room with inviting textures and stylish details.
              Its Highly spacious.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 King Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  Modern decor and a thoughtful layout make this room suitable
                  for a restful visit.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Work desk</li>
                  <li>Wardrobe</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 8,800</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="deluxe-3">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="deluxe-4">
          <div class="room-photo">
            <img
              src="img/dbed4.jpg"
              alt="Deluxe room with modern furnishings"
              loading="lazy" />
            <span class="room-number">Option 04</span>
          </div>
          <div class="room-info">
            <h3>Wooden deluxe Room</h3>
            <p class="room-description">
              A stylish retreat with coordinated furnishings and soft
              lighting.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 1 King Bed</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A polished, comfortable room with an inviting design and
                  useful essentials.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Bedside lighting</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 9,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="deluxe-4">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- EXECUTIVE ROOMS -->
    <section class="room-category" id="executive-rooms">
      <div class="category-heading">
        <div class="category-title">
          <h2>Executive Rooms</h2>
          <p>
            Premium rooms with spacious layouts and sophisticated interiors.
          </p>
        </div>
        <span class="category-count">4 Room Options</span>
      </div>

      <div class="room-grid">
        <article class="room-card" id="executive-1">
          <div class="room-photo">
            <img
              src="img/ebed.jpg"
              alt="Executive room with elegant lounge area"
              loading="lazy" />
            <span class="room-number">Option 01</span>
          </div>
          <div class="room-info">
            <h3>Cave window view room</h3>
            <p class="room-description">
              A premium suite with a nice cave view to enjoy.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> King Bed + Lounge</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A premium-style room for guests who want additional space to
                  work or relax.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Seating area</li>
                  <li>Work desk</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 10,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="executive-1">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="executive-2">
          <div class="room-photo">
            <img
              src="img/ebed2.jpg"
              alt="Executive room with lounge furniture"
              loading="lazy" />
            <span class="room-number">Option 02</span>
          </div>
          <div class="room-info">
            <h3>Spacious executive Room</h3>
            <p class="room-description">
              A spacious retreat with a lounge, stylish decor and modern
              comforts.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> King Bed + Lounge</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  Enjoy a more generous layout with space to sit, read or work
                  during your stay.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Lounge seating</li>
                  <li>Work desk</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 10,900</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="executive-2">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="executive-3">
          <div class="room-photo">
            <img
              src="img/ebed3.jpg"
              alt="Executive room with premium furnishings"
              loading="lazy" />
            <span class="room-number">Option 03</span>
          </div>
          <div class="room-info">
            <h3>Simple executive Room</h3>
            <p class="room-description">
              An upscale bedroom with elegant finishes and a peaceful
              atmosphere.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> King Bed + Lounge</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A sophisticated room designed around comfort, space and
                  understated style.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Seating area</li>
                  <li>Work desk</li>
                  <li>Wardrobe</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 9,800</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="executive-3">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="executive-4">
          <div class="room-photo">
            <img
              src="img/ebed4.jpg"
              alt="Executive room with contemporary luxury interior"
              loading="lazy" />
            <span class="room-number">Option 04</span>
          </div>
          <div class="room-info">
            <h3>Mordern executive Room</h3>
            <p class="room-description">
              A sophisticated option with a nice morden touch bringing a nice
              interior.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> King Bed + Lounge</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A refined setting with room to unwind and a practical layout
                  for longer visits.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Lounge seating</li>
                  <li>Work desk</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm specific amenities with reception before booking.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 10,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="executive-4">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- FAMILY ROOMS -->
    <section class="room-category" id="family-rooms">
      <div class="category-heading">
        <div class="category-title">
          <h2>Family Rooms</h2>
          <p>Spacious and family-friendly rooms with room for everyone.</p>
        </div>
        <span class="category-count">4 Room Options</span>
      </div>

      <div class="room-grid">
        <article class="room-card" id="family-1">
          <div class="room-photo">
            <img
              src="img/fbed.jpg"
              alt="Family room with two beds"
              loading="lazy" />
            <span class="room-number">Option 01</span>
          </div>
          <div class="room-info">
            <h3>Classic family room</h3>
            <p class="room-description">
              A comfortable family room with two beds and a welcoming layout.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 2 Queen Beds</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A practical family option with sleeping space for parents
                  and children.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Storage space</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm bed configuration and child policies with reception.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 12,000</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="family-1">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="family-2">
          <div class="room-photo">
            <img
              src="img/fbed2.jpg"
              alt="Family hotel room with spacious sleeping arrangements"
              loading="lazy" />
            <span class="room-number">Option 02</span>
          </div>
          <div class="room-info">
            <h3>Simple Family room</h3>
            <p class="room-description">
              A spacious room for family members to unwind after a busy day.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 2 Queen Beds</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A roomy layout that helps families relax together during
                  their visit.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Storage space</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm bed configuration and child policies with reception.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 12,500</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="family-2">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="family-3">
          <div class="room-photo">
            <img
              src="img/fbed3.png"
              alt="Bright family hotel bedroom"
              loading="lazy" />
            <span class="room-number">Option 03</span>
          </div>
          <div class="room-info">
            <h3>luxurious family room</h3>
            <p class="room-description">
              A bright, practical layout with comfortable beds for the family.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 2 Queen Beds</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A welcoming room with a simple layout and space to settle in
                  as a group.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Storage space</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm bed configuration and child policies with reception.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 14,500</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="family-3">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>

        <article class="room-card" id="family-4">
          <div class="room-photo">
            <img
              src="img/fbed4.jpg"
              alt="Comfortable family bedroom"
              loading="lazy" />
            <span class="room-number">Option 04</span>
          </div>
          <div class="room-info">
            <h3>Spacious Family Room</h3>
            <p class="room-description">
              A warm, family-friendly room designed for a relaxing group stay.
            </p>
            <div class="room-features">
              <span><i class="fa-solid fa-bed"></i> 2 Queen Beds</span>
              <span><i class="fa-solid fa-wifi"></i> Free Wi-Fi</span>
              <span><i class="fa-solid fa-bell-concierge"></i> Room Service</span>
            </div>
            <details class="room-more">
              <summary>View more room details</summary>
              <div class="room-more-content">
                <p>
                  A comfortable option for families looking for a shared and
                  relaxing space.
                </p>
                <h4>Room amenities</h4>
                <ul>
                  <li>Private bathroom</li>
                  <li>Television</li>
                  <li>Storage space</li>
                  <li>Daily housekeeping</li>
                </ul>
                <p class="room-note">
                  Confirm bed configuration and child policies with reception.
                </p>
              </div>
            </details>
            <div class="room-bottom">
              <p class="room-price">
                <strong>KSh 16,500</strong><span> / night</span>
              </p>
              <a href="#booking" class="view-room-btn" data-room="family-4">Choose Room <i class="fa-solid fa-arrow-right"></i></a>
            </div>
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
  <script src="javascript/navigation.js"></script>
  <script src="javascript/room.js?v=2"></script>
</body>

</html>