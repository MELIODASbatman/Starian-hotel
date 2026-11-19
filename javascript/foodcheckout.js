(() => {
  "use strict";

  const paymentMethods = Array.from(
    document.querySelectorAll('input[name="payment-method"]'),
  );
  const phoneInput = document.getElementById("mpesa-phone");
  const countryCode = document.getElementById("country-code");
  const agreeTerms = document.getElementById("agree-terms");
  const payButton = document.getElementById("pay-button");
  const feedback = document.getElementById("checkout-feedback");

  if (
    paymentMethods.length !== 3 ||
    !phoneInput ||
    !countryCode ||
    !agreeTerms ||
    !payButton ||
    !feedback
  ) {
    throw new Error("Food checkout page is missing required payment controls.");
  }

  const orderId = Number(payButton.dataset.orderId);
  if (!Number.isSafeInteger(orderId) || orderId < 1) {
    throw new Error("Food checkout page is missing a valid order ID.");
  }

  function selectedMethod() {
    const selected = paymentMethods.find((method) => method.checked);
    return selected ? selected.id : "";
  }

  function clearFeedback() {
    feedback.hidden = true;
    feedback.textContent = "";
    feedback.classList.remove("error");
    phoneInput.removeAttribute("aria-invalid");
  }

  function showFeedback(message, isError) {
    feedback.textContent = message;
    feedback.classList.toggle("error", isError);
    feedback.hidden = false;
  }

  function setActiveFields() {
    const useMpesa = selectedMethod() === "payment-mpesa";
    countryCode.disabled = !useMpesa;
    phoneInput.disabled = !useMpesa;
    phoneInput.required = useMpesa;

    if (!useMpesa) {
      phoneInput.value = "";
      phoneInput.removeAttribute("aria-invalid");
    }
  }

  paymentMethods.forEach((method) => {
    method.addEventListener("change", () => {
      clearFeedback();
      setActiveFields();
    });
  });

  phoneInput.addEventListener("input", () => {
    phoneInput.value = phoneInput.value.replace(/[^\d\s-]/g, "").slice(0, 14);
    clearFeedback();
  });

  agreeTerms.addEventListener("change", clearFeedback);

  payButton.addEventListener("click", () => {
    clearFeedback();

    if (!agreeTerms.checked) {
      showFeedback(
        agreeTerms.dataset.requiredMessage ||
          "Please agree to the food order and cancellation terms to continue.",
        true,
      );
      agreeTerms.focus();
      return;
    }

    if (selectedMethod() !== "payment-mpesa") {
      showFeedback(
        "Only M-Pesa demo checkout is available. Choose M-Pesa to continue.",
        true,
      );
      return;
    }

    let phone = phoneInput.value.replace(/\D/g, "");
    if (phone.startsWith("254")) {
      phone = phone.slice(3);
    } else if (phone.startsWith("0")) {
      phone = phone.slice(1);
    }

    if (!/^[17]\d{8}$/.test(phone)) {
      phoneInput.setAttribute("aria-invalid", "true");
      showFeedback(
        "Enter a valid Kenyan mobile number, such as 712 345 678.",
        true,
      );
      phoneInput.focus();
      return;
    }

    payButton.disabled = true;
    payButton.textContent = "Opening confirmation...";
    window.location.assign(
      `food_confirmation.php?order_id=${encodeURIComponent(orderId)}`,
    );
  });

  setActiveFields();
})();
