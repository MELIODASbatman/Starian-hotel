const notificationButton = document.getElementById("notificationButton");
const notificationDropdown = document.getElementById("notificationDropdown");

if (notificationButton && notificationDropdown) {
  notificationButton.addEventListener("click", function (event) {
    event.stopPropagation();

    const isOpen = !notificationDropdown.hidden;

    notificationDropdown.hidden = isOpen;
    notificationButton.setAttribute("aria-expanded", String(!isOpen));
  });

  // Close when clicking outside
  document.addEventListener("click", function (event) {
    if (
      !notificationDropdown.contains(event.target) &&
      !notificationButton.contains(event.target)
    ) {
      notificationDropdown.hidden = true;
      notificationButton.setAttribute("aria-expanded", "false");
    }
  });

  // Close when pressing Escape
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      notificationDropdown.hidden = true;
      notificationButton.setAttribute("aria-expanded", "false");
    }
  });
}
