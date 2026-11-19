<?php

// Protect this page - only administrators can access it
require_once __DIR__ . "/includes/auth.php";
// Create a CSRF token for contact message actions
if (empty($_SESSION["contact_messages_csrf_token"])) {
  $_SESSION["contact_messages_csrf_token"] = bin2hex(random_bytes(32));
}

// Connect to the database
require_once __DIR__ . "/../php/db.php";

// Fetch contact messages from the database
$sql = "
    SELECT *
    FROM contact_messages
    ORDER BY id DESC
";

$result = $conn->query($sql);

if (!$result) {
  error_log("Unable to load contact messages: " . $conn->error);
  http_response_code(500);
  exit("Unable to load contact messages right now.");
}

$messages = [];

while ($row = $result->fetch_assoc()) {
  $messages[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Review messages sent to Starian Hotel">
  <title>Contact Messages | Starian Hotel Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__ . "/admin.css"); ?>">
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
        <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
        <span>
          <strong><?php echo htmlspecialchars($_SESSION["fullname"] ?? "Administrator", ENT_QUOTES, "UTF-8"); ?></strong>
          <small>Administrator</small>
        </span>
      </div>

      <nav class="side-nav" aria-label="Admin navigation">
        <a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a>
        <a href="rooms.php"><i class="fa-solid fa-bed"></i>Rooms</a>
        <a href="menu-orders.php"><i class="fa-solid fa-utensils"></i>Menu / Orders</a>
        <a href="bookings.php"><i class="fa-regular fa-calendar-check"></i>Bookings</a>
        <a href="customers.php"><i class="fa-solid fa-users"></i>Customers</a>
        <a href="staff.php"><i class="fa-solid fa-user-group"></i>Staff</a>
        <a href="reports.php"><i class="fa-solid fa-chart-column"></i>Reports</a>
        <a class="active" href="contact-messages.php" aria-current="page">
          <i class="fa-regular fa-envelope"></i>Contact Messages
        </a>
      </nav>

      <p class="sidebar-motto">Better Service<span>Better Stays</span></p>
    </aside>

    <div class="workspace">
      <header class="topbar">
        <label class="menu-toggle" for="admin-menu" aria-label="Toggle admin menu">
          <i class="fa-solid fa-bars"></i>
        </label>
        <span class="topbar-spacer"></span>
        <?php require __DIR__ . "/includes/notification-widget.php"; ?>
        <div class="top-admin">
          <span class="admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span>
            <strong><?php echo htmlspecialchars($_SESSION["fullname"] ?? "Administrator", ENT_QUOTES, "UTF-8"); ?></strong>
            <small>Administrator</small>
          </span>
        </div>
        <form class="admin-logout-form" method="post" action="../logout.php">
          <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["profile_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
          <button class="logout-link" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
          </button>
        </form>
      </header>

      <main class="page-content">
        <div class="page-heading">
          <div>
            <h1>Contact Messages</h1>
            <p>Messages submitted by website visitors.</p>
          </div>
        </div>

        <section class="panel">
          <div class="panel-heading">
            <h2>Inbox</h2>
            <small><?php echo count($messages); ?> messages</small>
          </div>

          <?php if ($messages): ?>
            <div class="table-wrap">
              <table class="data-table contact-messages-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Date Received</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($messages as $message): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($message["name"] ?? "", ENT_QUOTES, "UTF-8"); ?></strong></td>
                      <td>
                        <a href="mailto:<?php echo htmlspecialchars($message["email"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
                          <?php echo htmlspecialchars($message["email"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                        </a>
                      </td>
                      <td>
                        <?php if (!empty($message["phone"])): ?>
                          <a href="tel:<?php echo htmlspecialchars($message["phone"], ENT_QUOTES, "UTF-8"); ?>">
                            <?php echo htmlspecialchars($message["phone"], ENT_QUOTES, "UTF-8"); ?>
                          </a>
                        <?php else: ?>
                          Not provided
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="contact-message-content">
                          <?php echo nl2br(htmlspecialchars($message["message"] ?? "", ENT_QUOTES, "UTF-8")); ?>
                        </div>
                      </td>
                      <td class="contact-message-date">
                        <?php echo htmlspecialchars($message["created_at"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                      </td>
                      <td>
                        <?php
                        if ($message["message_status"] === "unread") {
                          echo '<span style="color: red; font-weight: bold;">Unread</span>';
                        } else {
                          echo '<span style="color: green; font-weight: bold;">Read</span>';
                        }
                        ?>
                      </td>
                      <td>
                        <?php if ($message["message_status"] === "unread"): ?>

                          <form
                            class="contact-message-read-form"
                            action="mark-contact-message-read.php"
                            method="POST">

                            <input
                              type="hidden"
                              name="csrf_token"
                              value="<?php echo htmlspecialchars($_SESSION["contact_messages_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                            <input
                              type="hidden"
                              name="message_id"
                              value="<?php echo (int) $message["id"]; ?>">

                            <button
                              class="button-secondary contact-message-read-button"
                              type="submit"
                              title="Mark as Read">
                              <i class="fa-solid fa-envelope-open"></i>
                              Mark as Read
                            </button>

                          </form>

                        <?php endif; ?>
                        <form
                          class="contact-message-delete-form"
                          action="delete-contact-message.php"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this message?');">

                          <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION["contact_messages_csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                          <input
                            type="hidden"
                            name="message_id"
                            value="<?php echo (int) $message["id"]; ?>">
                          <button
                            class="icon-button danger-action"
                            type="submit"
                            aria-label="Delete message from <?php echo htmlspecialchars($message["name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                            title="Delete message">
                            <i class="fa-regular fa-trash-can"></i>
                          </button>
                        </form>

                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="contact-messages-empty">
              <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
              <strong>No contact messages yet</strong>
              <p>Messages from your website contact form will appear here.</p>
            </div>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>

</body>

</html>