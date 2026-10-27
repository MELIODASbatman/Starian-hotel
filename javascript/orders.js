const orderFilters = document.querySelectorAll(".order-filter");
const orderCards = document.querySelectorAll(".orders-list .order-row");
const noMatchingOrders = document.querySelector("#orders-filter-empty");

if (orderFilters.length && orderCards.length && noMatchingOrders) {
  orderFilters.forEach((filter) => {
    filter.addEventListener("click", () => {
      const activeFilter = filter.dataset.filter ?? "all";
      let visibleOrders = 0;

      orderFilters.forEach((button) => {
        const isActive = button === filter;
        button.classList.toggle("active", isActive);
        button.setAttribute("aria-pressed", String(isActive));
      });

      orderCards.forEach((order) => {
        const isVisible =
          activeFilter === "all" || order.dataset.category === activeFilter;
        order.hidden = !isVisible;
        visibleOrders += Number(isVisible);
      });

      noMatchingOrders.hidden = visibleOrders > 0;
    });
  });
}
