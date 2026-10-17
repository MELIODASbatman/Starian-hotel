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

const loginForm = document.getElementById("loginform");
const notification = document.getElementById("notification");

let notificationTimeout;
function showNotification(message, type) {
  notification.textContent = message;
  notification.classList.remove("success", "error", "show");
  notification.classList.add(type, "show");

  window.clearTimeout(notificationTimeout);
  notificationTimeout = window.setTimeout(() => {
    notification.classList.remove("show");
  }, 5000);
}

if (loginForm && notification) {
  loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const formData = new FormData(loginForm);
    const submitButton = loginForm.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch("php/login_process.php", {
        method: "POST",
        body: formData,
      });
      const data = await response.json();
      if (
        !response.ok ||
        typeof data.success !== "boolean" ||
        typeof data.message !== "string"
      ) {
        throw new Error("The login request returned an invalid response.");
      }

      showNotification(data.message, data.success ? "success" : "error");

      if (data.success) {
        window.setTimeout(() => {
          window.location.href = "index.php";
        }, 1000);
      }
    } catch (error) {
      showNotification("Something went wrong. Please try again.", "error");
      console.error("Login request failed:", error);
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });
}
