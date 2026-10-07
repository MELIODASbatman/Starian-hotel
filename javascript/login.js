const password = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

if (password && togglePassword) {
  togglePassword.addEventListener("click", function () {
    const isPasswordHidden = password.type === "password";

    password.type = isPasswordHidden ? "text" : "password";
    togglePassword.className = isPasswordHidden
      ? "fa-solid fa-eye"
      : "fa-solid fa-eye-slash";
    togglePassword.setAttribute(
      "aria-label",
      isPasswordHidden ? "Hide password" : "Show password",
    );
  });
}
