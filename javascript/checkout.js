(() => {
  "use strict";

  const paymentMethods = Array.from(
    document.querySelectorAll('input[name="payment-method"]'),
  );
  const phoneInput = document.getElementById("mpesa-phone");
  const countryCode = document.getElementById("country-code");
  const cardNumberInput = document.getElementById("card-number");
  const cardExpiryInput = document.getElementById("card-expiry");
  const cardCvcInput = document.getElementById("card-cvc");
  const agreeTerms = document.getElementById("agree-terms");
  const payButton = document.getElementById("pay-button");
  const feedback = document.getElementById("checkout-feedback");

  if (
    paymentMethods.length !== 3 ||
    !phoneInput ||
    !countryCode ||
    !cardNumberInput ||
    !cardExpiryInput ||
    !cardCvcInput ||
    !agreeTerms ||
    !payButton ||
    !feedback
  ) {
    throw new Error("Checkout page is missing required payment controls.");
  }

  const cardFields = [cardNumberInput, cardExpiryInput, cardCvcInput];

  function selectedMethod() {
    const selected = paymentMethods.find((method) => method.checked);
    return selected ? selected.id : "";
  }

  function clearFeedback() {
    feedback.hidden = true;
    feedback.textContent = "";
    feedback.classList.remove("error");
    [phoneInput, ...cardFields].forEach((input) => {
      input.removeAttribute("aria-invalid");
    });
  }

  function showFeedback(message, isError) {
    feedback.textContent = message;
    feedback.classList.toggle("error", isError);
    feedback.hidden = false;
  }

  function setActiveFields() {
    const method = selectedMethod();
    const useMpesa = method === "payment-mpesa";
    const useCard = method === "payment-card";

    countryCode.disabled = !useMpesa;
    phoneInput.disabled = !useMpesa;
    phoneInput.required = useMpesa;

    cardFields.forEach((input) => {
      input.disabled = !useCard;
      input.required = useCard;
    });

    if (!useCard) {
      cardFields.forEach((input) => {
        input.value = "";
      });
    }

    if (!useMpesa) {
      phoneInput.value = "";
    }
  }

  function formatCardNumber() {
    const digits = cardNumberInput.value.replace(/\D/g, "").slice(0, 19);
    cardNumberInput.value = digits.replace(/(.{4})/g, "$1 ").trim();
  }

  function formatExpiry() {
    const digits = cardExpiryInput.value.replace(/\D/g, "").slice(0, 4);
    cardExpiryInput.value =
      digits.length > 2 ? `${digits.slice(0, 2)} / ${digits.slice(2)}` : digits;
  }

  function isValidCardNumber(value) {
    const digits = value.replace(/\D/g, "");
    if (
      digits.length < 13 ||
      digits.length > 19 ||
      /^0+$/.test(digits)
    ) {
      return false;
    }

    let sum = 0;
    let doubleDigit = false;

    for (let index = digits.length - 1; index >= 0; index -= 1) {
      let digit = Number(digits[index]);
      if (doubleDigit) {
        digit *= 2;
        if (digit > 9) {
          digit -= 9;
        }
      }
      sum += digit;
      doubleDigit = !doubleDigit;
    }

    return sum % 10 === 0;
  }

  function isValidExpiry(value) {
    const digits = value.replace(/\D/g, "");
    if (digits.length !== 4) {
      return false;
    }

    const month = Number(digits.slice(0, 2));
    const year = 2000 + Number(digits.slice(2));
    if (month < 1 || month > 12) {
      return false;
    }

    const now = new Date();
    const currentMonth = now.getFullYear() * 12 + now.getMonth();
    const expiryMonth = year * 12 + month - 1;
    return expiryMonth >= currentMonth;
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

  cardNumberInput.addEventListener("input", formatCardNumber);
  cardExpiryInput.addEventListener("input", formatExpiry);
  phoneInput.addEventListener("input", () => {
    phoneInput.value = phoneInput.value.replace(/[^\d\s-]/g, "").slice(0, 14);
  });
  cardCvcInput.addEventListener("input", () => {
    cardCvcInput.value = cardCvcInput.value.replace(/\D/g, "").slice(0, 4);
  });

  [phoneInput, ...cardFields, agreeTerms].forEach((control) => {
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

    if (method === "payment-mpesa") {
      let phoneDigits = phoneInput.value.replace(/\D/g, "");
      if (phoneDigits.startsWith("254")) {
        phoneDigits = phoneDigits.slice(3);
      } else if (phoneDigits.startsWith("0")) {
        phoneDigits = phoneDigits.slice(1);
      }

      if (!/^[17]\d{8}$/.test(phoneDigits)) {
        showValidationError(
          "Enter a valid Kenyan mobile number, such as 712 345 678.",
          phoneInput,
        );
        return;
      }
    } else if (method === "payment-card") {
      if (!isValidCardNumber(cardNumberInput.value)) {
        showValidationError("Enter a valid card number.", cardNumberInput);
        return;
      }
      if (!isValidExpiry(cardExpiryInput.value)) {
        showValidationError(
          "Enter a valid, unexpired card date in MM / YY format.",
          cardExpiryInput,
        );
        return;
      }
      if (!/^\d{3,4}$/.test(cardCvcInput.value)) {
        showValidationError("Enter a valid card security code.", cardCvcInput);
        return;
      }
    }

    showFeedback(
      payButton.dataset.demoMessage ||
        "Checkout is in demo mode. No payment gateway is connected, so no payment was made and no payment details were submitted.",
      false,
    );
    phoneInput.value = "";
    cardFields.forEach((input) => {
      input.value = "";
    });
  });

  setActiveFields();
})();
