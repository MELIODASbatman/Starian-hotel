(function () {
  "use strict";

  document.querySelectorAll("[data-table-search]").forEach(function (form) {
    var table = document.getElementById(form.dataset.tableSearch);
    var input = form.querySelector('input[type="search"]');

    if (!table || !input) {
      return;
    }

    var rows = Array.prototype.slice.call(
      table.querySelectorAll("[data-search-row]"),
    );
    var categoryFilterList = document.querySelector(
      '[data-category-filter-list="' + table.id + '"]',
    );
    var activeCategory = "all";

    function filterRows() {
      var query = input.value.trim().toLocaleLowerCase();

      rows.forEach(function (row) {
        var matchesSearch =
          query === "" ||
          row.textContent.toLocaleLowerCase().includes(query);
        var rowCategory = (row.dataset.category || "").toLocaleLowerCase();
        var matchesCategory =
          activeCategory === "all" || rowCategory === activeCategory;

        row.hidden = !matchesSearch || !matchesCategory;
      });
    }

    input.addEventListener("input", filterRows);
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      filterRows();
    });

    if (categoryFilterList) {
      categoryFilterList.addEventListener("click", function (event) {
        var selectedFilter = event.target.closest("[data-category-filter]");

        if (!selectedFilter || !categoryFilterList.contains(selectedFilter)) {
          return;
        }

        activeCategory = selectedFilter.dataset.categoryFilter;
        categoryFilterList
          .querySelectorAll("[data-category-filter]")
          .forEach(function (filter) {
            var isSelected = filter === selectedFilter;
            filter.classList.toggle("active", isSelected);
            filter.setAttribute("aria-pressed", String(isSelected));
          });
        filterRows();
      });
    }
  });
})();
