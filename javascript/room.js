var roomsInBooking = [];

function getRequestedRoomCodes() {
  const roomSelect = document.getElementById("room-option");
  const roomCodes = roomsInBooking.slice();

  if (roomSelect.value && roomCodes.indexOf(roomSelect.value) === -1) {
    roomCodes.push(roomSelect.value);
  }

  return roomCodes;
}

(function () {
  "use strict";

  var categorySelect = document.getElementById("room-category");
  var roomSelect = document.getElementById("room-option");
  var addRoomButton = document.getElementById("add-room-btn");
  var selectedRoomsList = document.getElementById("selected-rooms");
  var checkIn = document.getElementById("check-in");
  var checkOut = document.getElementById("check-out");
  var arrivalTime = document.getElementById("arrival-time");
  var checkBtn = document.getElementById("check-availability-btn");
  var result = document.getElementById("availability-result");
  var bookingSection = document.getElementById("booking");
  var chooseButtons = document.querySelectorAll(".view-room-btn[data-room]");

  /* ---------- Room dropdown: keep every group so we can filter ---------- */
  var allGroups = Array.prototype.slice.call(
    roomSelect.querySelectorAll("optgroup"),
  );

  function groupCategory(group) {
    var first = group.querySelector("option");
    return first ? first.value.split("-")[0] : "";
  }

  function rebuildRoomOptions(category, keepValue) {
    roomSelect.innerHTML = '<option value="">Choose a room</option>';
    allGroups.forEach(function (g) {
      if (!category || groupCategory(g) === category) {
        roomSelect.appendChild(g);
      }
    });
    roomSelect.value = keepValue || "";
  }

  function roomLabel(roomCode) {
    for (var groupIndex = 0; groupIndex < allGroups.length; groupIndex += 1) {
      var option = allGroups[groupIndex].querySelector(
        'option[value="' + roomCode + '"]',
      );
      if (option) {
        return option.textContent;
      }
    }
    return roomCode;
  }

  function renderSelectedRooms() {
    selectedRoomsList.replaceChildren();
    selectedRoomsList.hidden = roomsInBooking.length === 0;

    roomsInBooking.forEach(function (roomCode) {
      var item = document.createElement("li");
      var name = document.createElement("span");
      var removeButton = document.createElement("button");

      name.textContent = roomLabel(roomCode);
      removeButton.type = "button";
      removeButton.className = "remove-selected-room";
      removeButton.dataset.roomCode = roomCode;
      removeButton.setAttribute("aria-label", "Remove " + roomLabel(roomCode));
      removeButton.textContent = "Remove";
      item.append(name, removeButton);
      selectedRoomsList.appendChild(item);
    });
  }

  function highlightCard(roomId) {
    document.querySelectorAll(".room-card.selected").forEach(function (c) {
      c.classList.remove("selected");
    });
    var card = roomId && document.getElementById(roomId);
    if (card) {
      card.classList.add("selected");
    }
  }

  /* ---------- Date helpers (local time, not UTC) ---------- */
  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function toDateValue(d) {
    return (
      d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate())
    );
  }

  function toTimeValue(d) {
    return pad(d.getHours()) + ":" + pad(d.getMinutes());
  }

  function addDays(dateValue, days) {
    var p = dateValue.split("-");
    var d = new Date(+p[0], +p[1] - 1, +p[2] + days);
    return toDateValue(d);
  }

  /* ---------- Defaults: today, tomorrow, current time ---------- */
  function setDefaults() {
    var now = new Date();
    var today = toDateValue(now);

    checkIn.min = today;
    checkIn.value = today;

    checkOut.min = addDays(today, 1);
    checkOut.value = addDays(today, 1);

    arrivalTime.value = toTimeValue(now);
  }

  checkIn.addEventListener("change", function () {
    if (!checkIn.value) {
      return;
    }
    var minOut = addDays(checkIn.value, 1);
    checkOut.min = minOut;
    if (!checkOut.value || checkOut.value < minOut) {
      checkOut.value = minOut;
    }
  });

  /* ---------- Choose Room -> fill the form ---------- */
  function selectRoom(roomId) {
    var category = roomId.split("-")[0];
    categorySelect.value = category;
    rebuildRoomOptions(category, roomId);
    highlightCard(roomId);

    var label = roomSelect.options[roomSelect.selectedIndex].text;
    result.innerHTML =
      "<strong>" +
      label +
      "</strong> is selected. " +
      "Confirm your check-in, check-out and arrival time, then press " +
      "<strong>Check Availability</strong>.";

    bookingSection.classList.remove("flash");
    void bookingSection.offsetWidth; // restart animation
    bookingSection.classList.add("flash");
  }

  // Client picks a category in the form -> show only that category's rooms
  categorySelect.addEventListener("change", function () {
    rebuildRoomOptions(categorySelect.value, "");
    highlightCard("");
  });

  // Client picks a room in the form -> sync category and card
  roomSelect.addEventListener("change", function () {
    if (!roomSelect.value) {
      highlightCard("");
      return;
    }
    var category = roomSelect.value.split("-")[0];
    if (categorySelect.value !== category) {
      categorySelect.value = category;
      rebuildRoomOptions(category, roomSelect.value);
    }
    highlightCard(roomSelect.value);
    var label = roomSelect.options[roomSelect.selectedIndex].text;
    result.innerHTML =
      "<strong>" +
      label +
      "</strong> is selected. " +
      "Confirm your dates and arrival time, then press <strong>Check Availability</strong>.";
  });

  addRoomButton.addEventListener("click", function () {
    if (!roomSelect.value) {
      showBookingNotification(
        "Choose a room",
        "Select a room before adding it to your booking.",
        "error",
      );
      return;
    }

    if (roomsInBooking.indexOf(roomSelect.value) !== -1) {
      showBookingNotification(
        "Room already added",
        "That room is already in your booking.",
        "error",
      );
      return;
    }

    if (roomsInBooking.length >= 16) {
      showBookingNotification(
        "Room limit reached",
        "You can book up to 16 different rooms at a time.",
        "error",
      );
      return;
    }

    var addedRoomCode = roomSelect.value;
    roomsInBooking.push(addedRoomCode);
    renderSelectedRooms();
    result.textContent =
      roomLabel(addedRoomCode) +
      " added. Choose another room or continue to check availability.";
    roomSelect.value = "";
    highlightCard("");
  });

  selectedRoomsList.addEventListener("click", function (event) {
    var removeButton = event.target.closest("[data-room-code]");
    if (!removeButton) {
      return;
    }

    roomsInBooking = roomsInBooking.filter(function (roomCode) {
      return roomCode !== removeButton.dataset.roomCode;
    });
    renderSelectedRooms();
  });

  chooseButtons.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      selectRoom(btn.dataset.room);
      bookingSection.scrollIntoView({ behavior: "smooth", block: "start" });
      history.replaceState(null, "", "#booking");
    });
  });

  /* ---------- Check availability ---------- */
  function nightsBetween(a, b) {
    var pa = a.split("-"),
      pb = b.split("-");
    var da = new Date(+pa[0], +pa[1] - 1, +pa[2]);
    var db = new Date(+pb[0], +pb[1] - 1, +pb[2]);
    return Math.round((db - da) / 86400000);
  }

  checkBtn.addEventListener("click", async function () {
    var roomCodes = getRequestedRoomCodes();
    if (roomCodes.length === 0) {
      showBookingNotification(
        "Choose a room",
        "Please choose at least one room before checking availability.",
        "error",
      );
      return;
    }
    if (roomCodes.length > 16) {
      showBookingNotification(
        "Room limit reached",
        "You can book up to 16 different rooms at a time.",
        "error",
      );
      return;
    }
    if (!checkIn.value || !checkOut.value || !arrivalTime.value) {
      showBookingNotification(
        "Booking details needed",
        "Please confirm your check-in date, check-out date and arrival time.",
        "error",
      );
      return;
    }
    var nights = nightsBetween(checkIn.value, checkOut.value);
    if (nights < 1) {
      showBookingNotification(
        "Invalid dates",
        "Check-out must be at least one day after check-in.",
        "error",
      );
      return;
    }

    var labels = roomCodes.map(roomLabel);
    var checkButtonLabel = checkBtn.innerHTML;
    checkBtn.disabled = true;
    checkBtn.textContent = "Checking...";

    try {
      var formData = new FormData();
      roomCodes.forEach(function (roomCode) {
        formData.append("room_codes[]", roomCode);
      });
      formData.append("check_in", checkIn.value);
      formData.append("check_out", checkOut.value);
      formData.append("arrival_time", arrivalTime.value);

      var response = await fetch("php/checkavailability.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });
      var availability = await response.json();

      if (!response.ok || !availability.success) {
        showBookingNotification(
          "Could not check availability",
          availability.message || "Please try again in a moment.",
          "error",
        );
        return;
      }

      if (!availability.available) {
        result.textContent = availability.message;
        showBookingNotification(
          "Room unavailable",
          availability.message,
          "error",
        );
        return;
      }

      var estimatedTotal = roomCodes.reduce(function (total, roomCode) {
        var card = document.getElementById(roomCode);
        var priceEl = card && card.querySelector(".room-price strong");
        return (
          total +
          (priceEl
            ? parseInt(priceEl.textContent.replace(/[^\d]/g, ""), 10) * nights
            : 0)
        );
      }, 0);

      result.textContent =
        labels.join(", ") +
        (roomCodes.length === 1 ? " is" : " are") +
        " available from " +
        checkIn.value +
        " to " +
        checkOut.value +
        " (" +
        nights +
        " night" +
        (nights > 1 ? "s" : "") +
        "), arriving at " +
        arrivalTime.value +
        ". Estimated total for " +
        roomCodes.length +
        (roomCodes.length === 1 ? " room: KSh " : " rooms: KSh ") +
        estimatedTotal.toLocaleString("en-KE") +
        ".";
      showBookingNotification(
        roomCodes.length === 1 ? "Room available" : "Rooms available",
        availability.message,
        "success",
      );
    } catch (error) {
      console.error("Availability check error:", error);
      showBookingNotification(
        "Unable to check availability",
        "Please check your connection and try again.",
        "error",
      );
    } finally {
      checkBtn.disabled = false;
      checkBtn.innerHTML = checkButtonLabel;
    }
  });

  setDefaults();
})();

// book now button
// ===================================
// BOOK ROOM
// ===================================

const bookNowBtn = document.getElementById("book-now-btn");
const bookingNotification = document.getElementById("booking-notification");
const bookingNotificationTitle = document.getElementById(
  "booking-notification-title",
);
const bookingNotificationIcon = bookingNotification
  ? bookingNotification.querySelector(".booking-notification-icon")
  : null;
const bookingNotificationMessage = document.getElementById(
  "booking-notification-message",
);
const bookingNotificationClose = document.getElementById(
  "booking-notification-close",
);
const bookingResult = document.getElementById("availability-result");
let bookingNotificationTimeout;

function showBookingNotification(title, message, type) {
  if (
    !bookingNotification ||
    !bookingNotificationTitle ||
    !bookingNotificationMessage
  ) {
    return;
  }

  window.clearTimeout(bookingNotificationTimeout);
  bookingNotificationTitle.textContent = title;
  bookingNotificationMessage.textContent = message;
  if (bookingNotificationIcon) {
    bookingNotificationIcon.textContent = type === "error" ? "!" : "✓";
  }
  bookingNotification.classList.remove("success", "error", "show");
  bookingNotification.classList.add(type);
  bookingNotification.hidden = false;
  bookingNotification.setAttribute(
    "role",
    type === "error" ? "alert" : "status",
  );

  window.requestAnimationFrame(function () {
    bookingNotification.classList.add("show");
  });

  bookingNotificationTimeout = window.setTimeout(
    function () {
      bookingNotification.classList.remove("show");
      window.setTimeout(function () {
        if (!bookingNotification.classList.contains("show")) {
          bookingNotification.hidden = true;
        }
      }, 220);
    },
    type === "error" ? 6000 : 8000,
  );
}

function showBookingResult(title, message, bookingIds = null) {
  if (!bookingResult) {
    return;
  }

  // Display the booking title and message.
  bookingResult.textContent = title + ": " + message;

  // Remove an old payment button if one exists.
  const oldPaymentButton = document.getElementById("proceed-to-payment-btn");

  if (oldPaymentButton) {
    oldPaymentButton.remove();
  }

  // Only show the payment button for a successfully created booking.
  const ids = Array.isArray(bookingIds)
    ? bookingIds
    : bookingIds
      ? [bookingIds]
      : [];

  if (ids.length > 0) {
    const paymentButton = document.createElement("button");

    paymentButton.id = "proceed-to-payment-btn";
    paymentButton.type = "button";
    paymentButton.textContent =
      "Proceed to Payment (" + ids.length + (ids.length === 1 ? " room)" : " rooms)");
    paymentButton.className = "pay-button";

    paymentButton.addEventListener("click", function () {
      const parameter = ids.length === 1 ? "booking_id=" : "booking_ids=";
      window.location.href =
        "checkout.php?" +
        parameter +
        encodeURIComponent(ids.join(","));
    });

    bookingResult.appendChild(document.createElement("br"));
    bookingResult.appendChild(document.createElement("br"));
    bookingResult.appendChild(paymentButton);
  }
}

if (bookingNotificationClose && bookingNotification) {
  bookingNotificationClose.addEventListener("click", function () {
    window.clearTimeout(bookingNotificationTimeout);
    bookingNotification.classList.remove("show");
    window.setTimeout(function () {
      bookingNotification.hidden = true;
    }, 220);
  });
}

if (bookNowBtn) {
  bookNowBtn.addEventListener("click", async function () {
    // Get the booking form fields
    const roomOption = document.getElementById("room-option");
    const checkIn = document.getElementById("check-in");
    const checkOut = document.getElementById("check-out");
    const arrivalTime = document.getElementById("arrival-time");

    // Check that the elements exist
    if (!roomOption || !checkIn || !checkOut || !arrivalTime) {
      showBookingResult(
        "Booking form unavailable",
        "Some booking details could not be found. Please reload the page and try again.",
      );
      return;
    }

    // Validate required fields
    const roomCodes = getRequestedRoomCodes();
    if (
      roomCodes.length === 0 ||
      !checkIn.value ||
      !checkOut.value ||
      !arrivalTime.value
    ) {
      showBookingResult(
        "Booking details needed",
        "Please complete all booking fields before submitting your request.",
      );
      return;
    }
    if (roomCodes.length > 16) {
      showBookingResult(
        "Room limit reached",
        "You can book up to 16 different rooms at a time.",
      );
      return;
    }

    // Prepare the booking information
    const formData = new FormData();

    roomCodes.forEach(function (roomCode) {
      formData.append("room_codes[]", roomCode);
    });
    formData.append("check_in", checkIn.value);
    formData.append("check_out", checkOut.value);
    formData.append("arrival_time", arrivalTime.value);

    try {
      // Prevent repeated clicks while processing
      bookNowBtn.disabled = true;

      // Send the booking to PHP
      const response = await fetch("php/bookroom.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      const result = await response.json();

      // Display the result
      if (result.success) {
        const bookingMessage =
          result.message +
          "\n" +
          result.room_count +
          (result.room_count === 1 ? " room · " : " rooms · ") +
          result.total_nights +
          (result.total_nights === 1 ? " night" : " nights") +
          "\n" +
          result.check_in +
          " to " +
          result.check_out +
          " · KSh " +
          result.total_price +
          "\nStatus: " +
          result.booking_status;
        showBookingResult(
          "Booking request received",
          bookingMessage,
          result.booking_ids || [result.booking_id],
        );
        roomsInBooking = [];
        document.getElementById("selected-rooms").replaceChildren();
        document.getElementById("selected-rooms").hidden = true;
      } else {
        showBookingResult(
          "Booking not completed",
          result.message ||
            "We could not process your booking. Please try again.",
        );
      }
    } catch (error) {
      console.error("Booking error:", error);

      showBookingResult(
        "Unable to process booking",
        "Please check your connection and try again.",
      );
    } finally {
      // Allow the guest to try again
      bookNowBtn.disabled = false;
    }
  });
}
