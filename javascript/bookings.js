const bookingSearch = document.querySelector("#booking-search-input");
const bookingFilters = document.querySelectorAll(".booking-filter");
const bookingCards = document.querySelectorAll(".booking-list .booking-item");
const bookingEmpty = document.querySelector("#booking-empty");

if (bookingSearch && bookingFilters.length && bookingCards.length && bookingEmpty) {
  let activeFilter = "all";

  const filterBookings = () => {
    const searchTerm = bookingSearch.value.trim().toLowerCase();
    let visibleBookings = 0;

    bookingCards.forEach((booking) => {
      const matchesFilter =
        activeFilter === "all" || booking.dataset.category === activeFilter;
      const matchesSearch = booking.textContent.toLowerCase().includes(searchTerm);
      const isVisible = matchesFilter && matchesSearch;

      booking.hidden = !isVisible;
      visibleBookings += Number(isVisible);
    });

    bookingEmpty.hidden = visibleBookings > 0;
  };

  bookingFilters.forEach((filter) => {
    filter.addEventListener("click", () => {
      activeFilter = filter.dataset.filter ?? "all";
      bookingFilters.forEach((button) => {
        const isActive = button === filter;
        button.classList.toggle("active", isActive);
        button.setAttribute("aria-pressed", String(isActive));
      });
      filterBookings();
    });
  });

  bookingSearch.addEventListener("input", filterBookings);
}
