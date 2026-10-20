const navigation = document.querySelector(".navi");
const navigationToggle = document.querySelector(".nav-toggle");
const navigationMenu = document.querySelector(".nav-links");

if (navigation && navigationToggle && navigationMenu) {
  const closeNavigation = () => {
    navigation.classList.remove("nav-open");
    navigationToggle.setAttribute("aria-expanded", "false");
    navigationToggle.setAttribute("aria-label", "Open navigation menu");
  };

  navigationToggle.addEventListener("click", () => {
    const isExpanded =
      navigationToggle.getAttribute("aria-expanded") === "true";

    navigation.classList.toggle("nav-open", !isExpanded);
    navigationToggle.setAttribute("aria-expanded", String(!isExpanded));
    navigationToggle.setAttribute(
      "aria-label",
      isExpanded ? "Open navigation menu" : "Close navigation menu",
    );
  });

  navigationMenu.addEventListener("click", (event) => {
    if (event.target instanceof Element && event.target.closest("a")) {
      closeNavigation();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeNavigation();
      navigationToggle.focus();
    }
  });

  window.matchMedia("(min-width: 761px)").addEventListener("change", (event) => {
    if (event.matches) {
      closeNavigation();
    }
  });
}
