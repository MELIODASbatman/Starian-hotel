document.addEventListener("DOMContentLoaded", function () {
  const employmentStatus = document.getElementById("employment_status");
  const departureDateGroup = document.getElementById("departureDateGroup");
  const departureReasonGroup = document.getElementById("departureReasonGroup");

  function toggleDepartureFields() {
    const requiresDeparture = ["resigned", "terminated", "retired"].includes(
      employmentStatus.value,
    );

    departureDateGroup.style.display = requiresDeparture ? "" : "none";
    departureReasonGroup.style.display = requiresDeparture ? "" : "none";
  }

  employmentStatus.addEventListener("change", toggleDepartureFields);

  toggleDepartureFields();
});
