(function () {
  "use strict";

  var storageKey = "starian-food-order";
  var order = {};
  var orderItems = document.getElementById("order-items");
  var emptyOrder = document.getElementById("empty-order");
  var orderTotal = document.getElementById("order-total");
  var orderStatus = document.getElementById("order-status");
  var clearOrderButton = document.getElementById("clear-order");
  var placeOrderButton = document.getElementById("place-order");
  var cartButton = document.getElementById("open-cart");
  var cartCount = document.getElementById("cart-count");
  var cartFeedback = document.getElementById("cart-feedback");
  var cartDialog = document.getElementById("cart-dialog");
  var closeCartButton = document.getElementById("close-cart");
  var continueShoppingButton = document.getElementById("continue-shopping");

  function formatPrice(amount) {
    return "KSh " + amount.toLocaleString("en-KE");
  }

  function saveOrder() {
    try {
      window.localStorage.setItem(storageKey, JSON.stringify(order));
      return true;
    } catch (error) {
      console.error("Could not save the food order:", error);
      orderStatus.textContent =
        "Your order is available for this visit but could not be saved in this browser.";
      return false;
    }
  }

  function renderOrder() {
    orderItems.replaceChildren();

    var itemCount = 0;
    var total = 0;

    Object.keys(order).forEach(function (name) {
      var item = order[name];
      var lineTotal = item.price * item.quantity;
      itemCount += item.quantity;
      total += lineTotal;

      var row = document.createElement("li");
      row.className = "order-item";

      var itemName = document.createElement("span");
      itemName.className = "order-item-name";
      itemName.textContent = name;

      var itemDetail = document.createElement("span");
      itemDetail.className = "order-item-detail";
      itemDetail.textContent =
        item.quantity +
        " × " +
        formatPrice(item.price) +
        " = " +
        formatPrice(lineTotal);

      var removeButton = document.createElement("button");
      removeButton.className = "remove-order-item";
      removeButton.type = "button";
      removeButton.dataset.removeItem = name;
      removeButton.setAttribute("aria-label", "Remove " + name + " from cart");
      removeButton.textContent = "Remove";

      row.append(itemName, itemDetail, removeButton);
      orderItems.appendChild(row);
    });

    var isEmpty = itemCount === 0;
    emptyOrder.hidden = !isEmpty;
    orderTotal.hidden = isEmpty;
    orderTotal.textContent =
      "Total (" + itemCount + " items): " + formatPrice(total);
    clearOrderButton.disabled = isEmpty;
    placeOrderButton.disabled = isEmpty;
    cartCount.textContent = String(itemCount);
    cartButton.setAttribute(
      "aria-label",
      "Open shopping cart, " +
        itemCount +
        (itemCount === 1 ? " item" : " items"),
    );
  }

  try {
    var savedOrder = window.localStorage.getItem(storageKey);
    if (savedOrder) {
      var parsedOrder = JSON.parse(savedOrder);
      if (
        parsedOrder &&
        typeof parsedOrder === "object" &&
        !Array.isArray(parsedOrder)
      ) {
        Object.keys(parsedOrder).forEach(function (name) {
          var item = parsedOrder[name];
          if (
            !item ||
            !Number.isSafeInteger(item.foodId) ||
            item.foodId < 1 ||
            !Number.isSafeInteger(item.price) ||
            item.price <= 0 ||
            !Number.isSafeInteger(item.quantity) ||
            item.quantity < 1
          ) {
            throw new Error("Saved order contains an invalid item.");
          }
        });
        order = parsedOrder;
      } else {
        throw new Error("Saved order has an invalid format.");
      }
    }
  } catch (error) {
    console.error("Could not load the saved food order:", error);
    orderStatus.textContent =
      "Your saved cart could not be loaded. You can start a new cart.";
  }

  document.querySelectorAll(".food-card").forEach(function (card) {
    var heading = card.querySelector("h3");
    var name = heading.textContent.trim();
    var foodId = Number(heading.dataset.foodId);
    var priceText = card.querySelector(".food-price").textContent;
    var price = Number(priceText.replace(/[^\d]/g, ""));
    var form = document.createElement("form");
    form.className = "food-order-form";
    form.dataset.foodId = String(foodId);
    form.dataset.foodName = name;
    form.dataset.foodPrice = String(price);

    var quantityLabel = document.createElement("label");
    quantityLabel.className = "food-quantity-field";
    quantityLabel.append("Quantity");

    var quantityInput = document.createElement("input");
    quantityInput.className = "food-quantity";
    quantityInput.type = "number";
    quantityInput.name = "quantity";
    quantityInput.min = "1";
    quantityInput.step = "1";
    quantityInput.value = "1";
    quantityInput.required = true;
    quantityInput.inputMode = "numeric";
    quantityInput.setAttribute("aria-label", "Quantity for " + name);
    quantityLabel.appendChild(quantityInput);

    var orderButton = document.createElement("button");
    orderButton.className = "food-order-button";
    orderButton.type = "submit";
    orderButton.textContent = "Add to cart";

    form.append(quantityLabel, orderButton);
    card.querySelector(".food-info").appendChild(form);
  });

  orderItems.addEventListener("click", function (event) {
    var button = event.target.closest("[data-remove-item]");
    if (!button) {
      return;
    }

    delete order[button.dataset.removeItem];
    var saved = saveOrder();
    renderOrder();
    var message =
      button.dataset.removeItem +
      " removed from your cart." +
      (saved
        ? ""
        : " This change could not be saved after you leave this page.");
    orderStatus.textContent = message;
    cartFeedback.textContent = message;
  });

  document.querySelectorAll(".food-order-form").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var quantityInput = form.elements.quantity;
      var quantity = quantityInput.valueAsNumber;
      if (
        !quantityInput.reportValidity() ||
        !Number.isSafeInteger(quantity) ||
        quantity < 1
      ) {
        quantityInput.focus();
        return;
      }

      var name = form.dataset.foodName;
      var foodId = Number(form.dataset.foodId);
      if (!Number.isSafeInteger(foodId) || foodId < 1) {
        orderStatus.textContent =
          "The Food item could not be identified. Please refresh the page and try again.";
        return;
      }
      var price = Number(form.dataset.foodPrice);
      if (!Number.isFinite(price) || price <= 0) {
        orderStatus.textContent =
          "The price for this dish is invalid. Please try again later.";
        return;
      }

      var updatedQuantity = (order[name] ? order[name].quantity : 0) + quantity;
      if (
        !Number.isSafeInteger(updatedQuantity) ||
        !Number.isSafeInteger(price * updatedQuantity)
      ) {
        orderStatus.textContent =
          "That quantity is too large. Please enter a smaller amount.";
        return;
      }

      if (order[name]) {
        order[name].quantity = updatedQuantity;
      } else {
        order[name] = {
          foodId: foodId,
          price: price,
          quantity: updatedQuantity,
        };
      }

      var saved = saveOrder();
      renderOrder();
      var message =
        quantity +
        " × " +
        name +
        " added to your cart. Your cart has " +
        Object.keys(order).length +
        (Object.keys(order).length === 1 ? " dish." : " dishes.") +
        (saved ? "" : " It could not be saved after you leave this page.");
      orderStatus.textContent = message;
      cartFeedback.textContent = message;
    });
  });

  cartButton.addEventListener("click", function () {
    cartDialog.showModal();
    closeCartButton.focus();
  });

  function closeCart() {
    cartDialog.close();
    cartButton.focus();
  }

  closeCartButton.addEventListener("click", closeCart);
  continueShoppingButton.addEventListener("click", closeCart);

  cartDialog.addEventListener("click", function (event) {
    if (event.target === cartDialog) {
      closeCart();
    }
  });

  clearOrderButton.addEventListener("click", function () {
    order = {};
    var saved = saveOrder();
    renderOrder();
    var message = saved
      ? "Your cart has been cleared."
      : "Your cart has been cleared for this visit, but the change could not be saved.";
    orderStatus.textContent = message;
    cartFeedback.textContent = message;
  });

  renderOrder();

  placeOrderButton.addEventListener("click", async function () {
    var items = Object.values(order).map(function (item) {
      return {
        food_id: item.foodId,
        quantity: item.quantity,
      };
    });

    if (items.length === 0) {
      orderStatus.textContent = "Your cart is empty.";
      return;
    }

    placeOrderButton.disabled = true;
    placeOrderButton.textContent = "Placing order...";

    orderStatus.textContent = "Please wait while we place your order.";

    try {
      var response = await fetch("php/place_order.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          items: items,
        }),
      });

      var result = await response.json();

      if (!response.ok || !result.success) {
        if (response.status === 401 && result.redirect) {
          window.location.href = result.redirect;
          return;
        }

        throw new Error(result.message || "We could not place your order.");
      }

      order = {};

      saveOrder();

      renderOrder();

      var message =
        result.message +
        " Order number: " +
        result.order_id +
        ". Total: " +
        formatPrice(Number(result.total_amount));

      orderStatus.textContent = message;

      cartFeedback.textContent = message;
    } catch (error) {
      console.error("Order submission failed:", error);

      orderStatus.textContent =
        error.message || "We could not place your order. Please try again.";
    } finally {
      placeOrderButton.textContent = "Place Order";

      renderOrder();
    }
  });
})();
