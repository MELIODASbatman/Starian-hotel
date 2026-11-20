document.addEventListener("DOMContentLoaded", function () {
  const staffSearch = document.getElementById("staffSearch");
  const roleFilter = document.getElementById("roleFilter");
  const statusFilter = document.getElementById("statusFilter");
  const employmentStatusFilter = document.getElementById(
    "employmentStatusFilter",
  );

  const staffRows = document.querySelectorAll(".data-table tbody tr");

  function filterStaff() {
    const searchValue = staffSearch.value.toLowerCase().trim();
    const selectedRole = roleFilter.value.toLowerCase();
    const selectedStatus = statusFilter.value.toLowerCase();
    const selectedEmploymentStatus = employmentStatusFilter.value.toLowerCase();

    staffRows.forEach(function (row) {
      // Skip the empty-table message
      if (row.cells.length < 11) {
        return;
      }

      const rowText = row.textContent.toLowerCase();

      const staffRole = row.cells[4].textContent.trim().toLowerCase();
      const staffStatus = row.cells[7].textContent.trim().toLowerCase();
      const staffEmploymentStatus = row.cells[8].textContent
        .trim()
        .toLowerCase();

      const matchesSearch = rowText.includes(searchValue);

      const matchesRole = selectedRole === "" || staffRole === selectedRole;

      const matchesStatus =
        selectedStatus === "" || staffStatus === selectedStatus;

      const matchesEmploymentStatus =
        selectedEmploymentStatus === "" ||
        staffEmploymentStatus === selectedEmploymentStatus;

      row.style.display =
        matchesSearch && matchesRole && matchesStatus && matchesEmploymentStatus
          ? ""
          : "none";
    });
  }

  if (staffSearch) {
    staffSearch.addEventListener("input", filterStaff);
  }

  if (roleFilter) {
    roleFilter.addEventListener("change", filterStaff);
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", filterStaff);
  }

  if (employmentStatusFilter) {
    employmentStatusFilter.addEventListener("change", filterStaff);
  }
});
