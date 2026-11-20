document.addEventListener("DOMContentLoaded", function () {
  const formerStaffSearch = document.getElementById("formerStaffSearch");
  const formerStatusFilter = document.getElementById("formerStatusFilter");

  const formerStaffRows = document.querySelectorAll(".data-table tbody tr");

  function filterFormerStaff() {
    const searchValue = formerStaffSearch.value.toLowerCase().trim();
    const selectedStatus = formerStatusFilter.value.toLowerCase();

    formerStaffRows.forEach(function (row) {
      // Skip the empty-table message
      if (row.cells.length < 9) {
        return;
      }

      const rowText = row.textContent.toLowerCase();

      const staffStatus = row.cells[5].textContent.trim().toLowerCase();

      const matchesSearch = rowText.includes(searchValue);

      const matchesStatus =
        selectedStatus === "" || staffStatus === selectedStatus;

      row.style.display = matchesSearch && matchesStatus ? "" : "none";
    });
  }

  formerStaffSearch.addEventListener("input", filterFormerStaff);
  formerStatusFilter.addEventListener("change", filterFormerStaff);
});
