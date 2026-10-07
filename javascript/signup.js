function setupPasswordToggle(passwordId, toggleId) {
  const password = document.getElementById(passwordId);
  const toggle = document.getElementById(toggleId);

  if (!password || !toggle) {
    return;
  }

  toggle.addEventListener("click", function () {
    const isPasswordHidden = password.type === "password";

    password.type = isPasswordHidden ? "text" : "password";
    toggle.className = isPasswordHidden
      ? "fa-solid fa-eye"
      : "fa-solid fa-eye-slash";

    toggle.setAttribute(
      "aria-label",
      isPasswordHidden ? "Hide password" : "Show password",
    );
  });
}

setupPasswordToggle("password", "togglepassword");
setupPasswordToggle("confirmpassword", "toggleconfirmpassword");
