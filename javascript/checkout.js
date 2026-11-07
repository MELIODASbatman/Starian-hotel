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
  const payButtonLabel = payButton ? payButton.textContent.trim() : "";

  if (
    paymentMethods.length !== 3 ||
    !phoneInput ||
    !countryCode ||
    !agreeTerms ||
    !payButton ||
    !feedback
  ) {
    throw new Error("Checkout page is missing required payment controls.");
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
    const method = selectedMethod();
    const useMpesa = method === "payment-mpesa";

    countryCode.disabled = !useMpesa;
    phoneInput.disabled = !useMpesa;
    phoneInput.required = useMpesa;

    if (!useMpesa) {
      phoneInput.value = "";
    }
  }

  function showValidationError(message, input) {
    input.setAttribute("aria-invalid", "true");
    showFeedback(message, true);
    input.focus();
  }

  paymentMethods.forEach((method) => {
    method.addEventListener("change", () => {
      clearFeedback();
      setActiveFields();
    });
  });

  phoneInput.addEventListener("input", () => {
    phoneInput.value = phoneInput.value.replace(/[^\d\s-]/g, "").slice(0, 14);
  });

  [phoneInput, agreeTerms].forEach((control) => {
    control.addEventListener("input", clearFeedback);
    control.addEventListener("change", clearFeedback);
  });

  payButton.addEventListener("click", () => {
    clearFeedback();

    if (!agreeTerms.checked) {
      showFeedback(
        agreeTerms.dataset.requiredMessage ||
          "Please agree to the booking terms and cancellation policy to continue.",
        true,
      );
      agreeTerms.focus();
      return;
    }

    const method = selectedMethod();

    if (method !== "payment-mpesa") {
      showFeedback(
        "Only M-Pesa demo payments are currently available. Choose M-Pesa to continue.",
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
      showValidationError(
        "Enter a valid Kenyan mobile number, such as 712 345 678.",
        phoneInput,
      );
      return;
    }

    payButton.disabled = true;
    payButton.textContent = "Processing...";

    fetch("php/processpayment.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        booking_ids: checkoutBookingIds,
        payment_method: "mpesa",
        phone: phone,
      }),
    })
      .then(async (response) => {
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || "Payment failed.");
        }

        return data;
      })
      .then((data) => {
        const confirmedBookingIds = Array.isArray(data.booking_ids)
          ? data.booking_ids
          : checkoutBookingIds;
        const confirmationQuery = new URLSearchParams({
          booking_ids: confirmedBookingIds.join(","),
        });
        window.location.assign(`confirmation.php?${confirmationQuery}`);
      })
      .catch((error) => {
        showFeedback(
          error.message || "Payment failed. Please try again.",
          true,
        );
        payButton.disabled = false;
        payButton.textContent = payButtonLabel;
      });
  });

  setActiveFields();
})();
