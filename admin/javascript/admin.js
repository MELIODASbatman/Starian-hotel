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

document.querySelectorAll(".staff-toast").forEach(function (toast) {
  let isDismissing = false;
  let dismissTimer;

  function dismissToast() {
    if (isDismissing) {
      return;
    }

    isDismissing = true;
    window.clearTimeout(dismissTimer);
    toast.classList.add("is-hiding");
    window.setTimeout(function () {
      toast.remove();
    }, 250);
  }

  const dismissButton = toast.querySelector(".staff-toast-dismiss");
  if (dismissButton) {
    dismissButton.addEventListener("click", dismissToast);
  }

  dismissTimer = window.setTimeout(dismissToast, 5000);
});
