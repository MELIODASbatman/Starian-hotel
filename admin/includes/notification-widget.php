<?php

require_once __DIR__ . "/../../php/db.php";

$notificationUnreadCount = 0;
$notificationMessages = [];
$notificationLoadFailed = false;

$notificationCountResult = $conn->query("
    SELECT COUNT(*) AS unread_count
    FROM contact_messages
    WHERE message_status = 'unread'
");

if ($notificationCountResult) {
  $notificationUnreadCount = (int) $notificationCountResult->fetch_assoc()["unread_count"];
} else {
  error_log("Unable to count unread contact messages: " . $conn->error);
  $notificationLoadFailed = true;
}

$notificationListResult = $conn->query("
    SELECT id, name, message
    FROM contact_messages
    WHERE message_status = 'unread'
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");

if ($notificationListResult) {
  while ($notification = $notificationListResult->fetch_assoc()) {
    $notificationMessages[] = $notification;
  }
} else {
  error_log("Unable to load unread contact messages: " . $conn->error);
  $notificationLoadFailed = true;
}
?>
<div class="notification-wrapper">
  <button
    class="topbar-icon notification-button"
    type="button"
    id="notificationButton"
    aria-label="Notifications<?php echo $notificationUnreadCount > 0 ? ", " . $notificationUnreadCount . " unread" : ""; ?>"
    aria-expanded="false"
    aria-controls="notificationDropdown">
    <i class="fa-regular fa-bell" aria-hidden="true"></i>
    <?php if ($notificationUnreadCount > 0): ?>
      <span class="notification-dot">
        <?php echo $notificationUnreadCount > 99 ? "99+" : $notificationUnreadCount; ?>
      </span>
    <?php endif; ?>
  </button>

  <div
    class="notification-dropdown"
    id="notificationDropdown"
    aria-label="Notifications"
    hidden>
    <div class="notification-dropdown-header">
      <strong>Notifications</strong>
      <span><?php echo $notificationUnreadCount; ?> unread</span>
    </div>

    <div class="notification-dropdown-body">
      <?php if ($notificationLoadFailed): ?>
        <p class="notification-empty" role="alert">
          Notifications could not be loaded. Please try again later.
        </p>
      <?php elseif ($notificationMessages): ?>
        <?php foreach ($notificationMessages as $notification): ?>
          <a class="notification-item" href="contact-messages.php">
            <span class="notification-item-icon">
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
            </span>
            <span class="notification-item-content">
              <strong><?php echo htmlspecialchars($notification["name"], ENT_QUOTES, "UTF-8"); ?></strong>
              <small>
                <?php
                $preview = trim((string) $notification["message"]);
                echo htmlspecialchars(
                  mb_strimwidth($preview, 0, 75, "..."),
                  ENT_QUOTES,
                  "UTF-8"
                );
                ?>
              </small>
            </span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="notification-empty">You're all caught up!</p>
      <?php endif; ?>
    </div>

    <a class="notification-view-all" href="contact-messages.php">
      View all messages
      <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
  </div>
</div>
<script src="javascript/admin.js?v=<?php echo filemtime(__DIR__ . "/../javascript/admin.js"); ?>" defer></script>
