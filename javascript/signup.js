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

const signupForm = document.getElementById("signupform");
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

if (signupForm && notification) {
  signupForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const formData = new FormData(signupForm);
    const submitButton = signupForm.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch("php/signup_process.php", {
        method: "POST",
        body: formData,
      });
      const data = await response.json();

      if (
        !response.ok ||
        typeof data.success !== "boolean" ||
        typeof data.message !== "string"
      ) {
        throw new Error("The signup request returned an invalid response.");
      }

      showNotification(data.message, data.success ? "success" : "error");

      if (data.success) {
        signupForm.reset();
      }
    } catch (error) {
      showNotification("Something went wrong. Please try again.", "error");
      console.error("Signup request failed:", error);
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });
}
