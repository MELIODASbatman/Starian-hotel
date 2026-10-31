<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../php/db.php";

$adminName = $_SESSION["fullname"] ?? "Administrator";

// Create a CSRF token for booking actions
if (empty($_SESSION["booking_csrf_token"])) {
  $_SESSION["booking_csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

// Process booking actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Verify CSRF token
  $submittedToken = $_POST["csrf_token"] ?? "";

  if (!hash_equals($_SESSION["booking_csrf_token"], $submittedToken)) {

    $message = "Invalid request. Please try again.";
    $messageType = "error";
  } else {

    $bookingId = filter_input(INPUT_POST, "booking_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!$bookingId || !in_array($action, ["confirm", "cancel"], true)) {

      $message = "Invalid booking request.";
      $messageType = "error";
    } else {

      // Get the selected booking
      $stmt = $conn->prepare("
                SELECT id, room_id, check_in, check_out,
                       booking_status, payment_status
                FROM bookings
                WHERE id = ?
            ");

      $stmt->bind_param("i", $bookingId);
      $stmt->execute();

      $booking = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$booking) {

        $message = "Booking not found.";
        $messageType = "error";
      } elseif ($action === "confirm") {

        if ($booking["booking_status"] !== "pending") {

          $message = "Only pending bookings can be confirmed.";
          $messageType = "error";
        } elseif ($booking["payment_status"] !== "paid") {

          $message = "Payment must be completed before confirming this booking.";
          $messageType = "error";
        } else {

          // Check whether another confirmed booking overlaps
          $stmt = $conn->prepare("
                        SELECT id
                        FROM bookings
                        WHERE room_id = ?
                        AND id != ?
                        AND booking_status = 'confirmed'
                        AND check_in < ?
                        AND check_out > ?
                        LIMIT 1
                    ");

          $stmt->bind_param(
            "iiss",
            $booking["room_id"],
            $bookingId,
            $booking["check_out"],
            $booking["check_in"]
          );

          $stmt->execute();
          $conflict = $stmt->get_result()->fetch_assoc();
          $stmt->close();

          if ($conflict) {

            $message = "This room already has a confirmed booking for overlapping dates.";
            $messageType = "error";
          } else {

            // Confirm the booking
            $stmt = $conn->prepare("
                            UPDATE bookings
                            SET booking_status = 'confirmed'
                            WHERE id = ?
                            AND booking_status = 'pending'
                            AND payment_status = 'paid'
                        ");

            $stmt->bind_param("i", $bookingId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
              $message = "Booking confirmed successfully.";
              $messageType = "success";
            } else {
              $message = "The booking could not be confirmed. Please refresh and try again.";
              $messageType = "error";
            }

            $stmt->close();
          }
        }
      } elseif ($action === "cancel") {

        if (in_array($booking["booking_status"], ["cancelled", "completed"], true)) {

          $message = "This booking cannot be cancelled.";
          $messageType = "error";
        } else {

          $stmt = $conn->prepare("
                        UPDATE bookings
                        SET booking_status = 'cancelled'
                        WHERE id = ?
                        AND booking_status IN ('pending', 'confirmed')
                    ");

          $stmt->bind_param("i", $bookingId);
          $stmt->execute();

          if ($stmt->affected_rows > 0) {
            $message = "Booking cancelled successfully.";
            $messageType = "success";
          } else {
            $message = "The booking could not be cancelled.";
            $messageType = "error";
          }

          $stmt->close();
        }
      }
    }
  }
}

// Fetch all bookings
// Get search and filter values
$searchTerm = trim(is_string($_GET["search"] ?? null) ? $_GET["search"] : "");
$bookingStatus = is_string($_GET["booking_status"] ?? null) ? $_GET["booking_status"] : "";
$paymentStatus = is_string($_GET["payment_status"] ?? null) ? $_GET["payment_status"] : "";

// Validate booking status
$allowedBookingStatuses = ["pending", "confirmed", "cancelled", "completed"];

if (!in_array($bookingStatus, $allowedBookingStatuses, true)) {
  $bookingStatus = "";
}

// Validate payment status
$allowedPaymentStatuses = ["pending", "paid"];

if (!in_array($paymentStatus, $allowedPaymentStatuses, true)) {
  $paymentStatus = "";
}

// Prepare search value
$searchLike = "%" . $searchTerm . "%";

// Pagination settings
$limit = 10;

// Get current page from URL
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);

if (!$page || $page < 1) {
  $page = 1;
}

// Calculate offset
$offset = ($page - 1) * $limit;

// Count matching bookings
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    INNER JOIN users ON bookings.user_id = users.id
    WHERE
        (? = ''
        OR users.fullname LIKE ?
        OR users.email LIKE ?
        OR CAST(bookings.id AS CHAR) LIKE ?)

        AND (? = '' OR bookings.booking_status = ?)

        AND (? = '' OR bookings.payment_status = ?)
");

$countStmt->bind_param(
  "ssssssss",
  $searchTerm,
  $searchLike,
  $searchLike,
  $searchLike,
  $bookingStatus,
  $bookingStatus,
  $paymentStatus,
  $paymentStatus
);

$countStmt->execute();

$countResult = $countStmt->get_result();
$totalBookings = $countResult->fetch_assoc()["total"];

$countStmt->close();

// Calculate total pages
$totalPages = (int) ceil($totalBookings / $limit);

// Prevent invalid page numbers
if ($totalPages > 0 && $page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $limit;
}

// Fetch bookings using search and filters
$stmt = $conn->prepare("
    SELECT
        bookings.id,
        users.fullname,
        users.email,
        rooms.room_name,
        bookings.check_in,
        bookings.check_out,
        bookings.booking_status,
        bookings.created_at,
        bookings.payment_status

    FROM bookings

    INNER JOIN users ON bookings.user_id = users.id
    INNER JOIN rooms ON bookings.room_id = rooms.id

    WHERE
        (? = ''
        OR users.fullname LIKE ?
        OR users.email LIKE ?
        OR CAST(bookings.id AS CHAR) LIKE ?)

        AND (? = '' OR bookings.booking_status = ?)

        AND (? = '' OR bookings.payment_status = ?)

    ORDER BY bookings.created_at DESC
    LIMIT ? OFFSET ?
");

$stmt->bind_param(
  "ssssssssii",
  $searchTerm,
  $searchLike,
  $searchLike,
  $searchLike,
  $bookingStatus,
  $bookingStatus,
  $paymentStatus,
  $paymentStatus,
  $limit,
  $offset
);

$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}

$stmt->close();
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage Starian Hotel bookings">
  <title>Bookings | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=20261031-2">
</head>

<body>
  <div class="admin-shell">
    <input class="menu-checkbox" type="checkbox" id="admin-menu">
    <aside class="sidebar">
      <a class="brand" href="index.php"><img src="../img/starianlogo.png" alt=""><span class="brand-name">Starian <span>Hotel</span></span></a>
      <div class="admin-identity"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a><a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a><a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a><a class="active" href="bookings.php" aria-current="page"><i class="fa-regular fa-calendar-check"></i>Bookings</a><a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a><a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a><a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a><a href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </nav>
      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>
    <div class="workspace">
      <header class="topbar"><label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu"><i class="fa-solid fa-bars"></i></label><span class="topbar-spacer"></span><a class="topbar-icon" href="#" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot">3</span></a>
        <div class="top-admin"><span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span><span><strong><?php echo htmlspecialchars($adminName, ENT_QUOTES, "UTF-8"); ?></strong><small>Administrator</small></span></div>
        <form class="admin-logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>" /><button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
      </header>
      <main class="page-content">
        <?php if (!empty($message)): ?>

          <div class="booking-alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
          </div>

        <?php endif; ?>
        <div class="page-heading">
          <div>
            <h1>Manage Bookings</h1>
            <p>View and manage all hotel bookings.</p>
          </div>
        </div>
        <form method="GET" action="bookings.php" class="booking-filters">
          <label class="booking-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input
              type="search"
              name="search"
              placeholder="Search by guest name, email or booking ID..."
              value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, "UTF-8") ?>"
              aria-label="Search by guest name, email or booking ID">
          </label>

          <select name="booking_status" aria-label="Booking status">
            <option value="">All Booking Statuses</option>
            <option value="pending" <?= $bookingStatus === "pending" ? "selected" : "" ?>>Pending</option>
            <option value="confirmed" <?= $bookingStatus === "confirmed" ? "selected" : "" ?>>Confirmed</option>
            <option value="cancelled" <?= $bookingStatus === "cancelled" ? "selected" : "" ?>>Cancelled</option>
            <option value="completed" <?= $bookingStatus === "completed" ? "selected" : "" ?>>Completed</option>
          </select>

          <select name="payment_status" aria-label="Payment status">
            <option value="">All Payment Statuses</option>
            <option value="pending" <?= $paymentStatus === "pending" ? "selected" : "" ?>>Pending</option>
            <option value="paid" <?= $paymentStatus === "paid" ? "selected" : "" ?>>Paid</option>
          </select>

          <button class="booking-filter-submit" type="submit">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>Apply Filters
          </button>

          <a class="booking-filter-reset" href="bookings.php">Reset</a>
        </form>
        <section class="panel">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Guest Name</th>
                  <th>Room</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>

                <?php if (!empty($bookings)): ?>

                  <?php foreach ($bookings as $booking): ?>

                    <?php
                    $status = strtolower($booking["booking_status"]);

                    $badgeClass = "badge";

                    if ($status === "pending") {
                      $badgeClass .= " warning";
                    } elseif ($status === "cancelled") {
                      $badgeClass .= " danger";
                    }
                    ?>

                    <tr>

                      <td>
                        <span class="table-person">
                          <span class="person-avatar">
                            <i class="fa-solid fa-user"></i>
                          </span>

                          <?= htmlspecialchars($booking["fullname"]) ?>
                        </span>
                      </td>

                      <td>
                        <?= htmlspecialchars($booking["room_name"]) ?>
                      </td>

                      <td>
                        <?= date("d M Y", strtotime($booking["check_in"])) ?>
                      </td>

                      <td>
                        <?= date("d M Y", strtotime($booking["check_out"])) ?>
                      </td>

                      <td>
                        <span class="<?= $badgeClass ?>">
                          <?= htmlspecialchars(ucfirst($status)) ?>
                        </span>
                      </td>

                      <td>
                        <div class="actions">

                          <a href="view-booking.php?id=<?= (int)$booking["id"] ?>"
                            class="icon-button"
                            title="View booking">
                            <i class="fa-solid fa-eye"></i>
                          </a>

                          <!-- Confirm booking -->
                          <?php if ($booking["booking_status"] === "pending"): ?>

                            <form method="POST" action="bookings.php">

                              <input type="hidden" name="booking_id"
                                value="<?= (int)$booking["id"] ?>">

                              <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["booking_csrf_token"]) ?>">

                              <input type="hidden" name="action" value="confirm">

                              <button class="icon-button" type="submit"
                                title="Confirm booking"
                                onclick="return confirm('Confirm this booking?');">

                                <i class="fa-solid fa-check"></i>

                              </button>

                            </form>

                          <?php endif; ?>


                          <!-- Cancel booking -->
                          <?php if (in_array($booking["booking_status"], ["pending", "confirmed"], true)): ?>

                            <form method="POST" action="bookings.php">

                              <input type="hidden" name="booking_id"
                                value="<?= (int)$booking["id"] ?>">

                              <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION["booking_csrf_token"]) ?>">

                              <input type="hidden" name="action" value="cancel">

                              <button class="icon-button danger-action" type="submit"
                                title="Cancel booking"
                                onclick="return confirm('Are you sure you want to cancel this booking?');">

                                <i class="fa-solid fa-xmark"></i>

                              </button>

                            </form>

                          <?php endif; ?>

                        </div>
                      </td>

                    </tr>

                  <?php endforeach; ?>

                <?php else: ?>

                  <tr>
                    <td colspan="6" style="text-align:center;">
                      No bookings found.
                    </td>
                  </tr>

                <?php endif; ?>

              </tbody>
            </table>
          </div>
          <div class="pagination-info">

            <?php
            $start = $totalBookings > 0 ? $offset + 1 : 0;
            $end = min($offset + $limit, $totalBookings);
            ?>

            <p>
              Showing <?= $start ?>–<?= $end ?>
              of <?= $totalBookings ?> bookings
            </p>

          </div>

          <?php if ($totalPages > 1): ?>

            <nav class="booking-pagination" aria-label="Booking pages">

              <?php
              // Preserve existing search and filter values
              $queryParams = [
                "search" => $searchTerm,
                "booking_status" => $bookingStatus,
                "payment_status" => $paymentStatus
              ];
              ?>

              <!-- Previous Button -->
              <?php if ($page > 1): ?>

                <a href="?<?= http_build_query(array_merge($queryParams, ["page" => $page - 1])) ?>"
                  aria-label="Go to previous page">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>

              <?php endif; ?>

              <!-- Page Numbers -->
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <a
                  href="?<?= http_build_query(array_merge($queryParams, ["page" => $i])) ?>"
                  class="<?= $page === $i ? 'active' : '' ?>"
                  <?= $page === $i ? 'aria-current="page"' : '' ?>>
                  <?= $i ?>
                </a>

              <?php endfor; ?>

              <!-- Next Button -->
              <?php if ($page < $totalPages): ?>

                <a href="?<?= http_build_query(array_merge($queryParams, ["page" => $page + 1])) ?>"
                  aria-label="Go to next page">
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>

              <?php endif; ?>

            </nav>

          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
</body>

</html>