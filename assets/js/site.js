const accountButton = document.querySelector(".account-link");
const accountMenu = document.querySelector("#account-menu");
const searchForm = document.querySelector("#car-search");
const searchInput = searchForm.querySelector('input[name="q"]');
const searchStatus = document.querySelector("#search-status");
const contrastToggle = document.querySelector("#contrast-toggle");
const textSizeButton = document.querySelector("#text-size-button");
const resetDisplayButton = document.querySelector("#reset-display-button");

function closeAccountMenu() {
  accountMenu.hidden = true;
  accountButton.setAttribute("aria-expanded", "false");
}

function openDialog(dialogId) {
  const dialog = document.getElementById(dialogId);
  if (dialog instanceof HTMLDialogElement && !dialog.open) {
    dialog.showModal();
  }
  closeAccountMenu();
}

accountButton.addEventListener("click", () => {
  const isExpanded = accountButton.getAttribute("aria-expanded") === "true";
  accountButton.setAttribute("aria-expanded", String(!isExpanded));
  accountMenu.hidden = isExpanded;
});

document.addEventListener("click", (event) => {
  if (!event.target.closest(".account-menu-wrap")) {
    closeAccountMenu();
  }

  const dialogTrigger = event.target.closest("[data-open-dialog]");
  if (dialogTrigger) {
    openDialog(dialogTrigger.dataset.openDialog);
  }

  const closeTrigger = event.target.closest("[data-close-dialog]");
  if (closeTrigger) {
    closeTrigger.closest("dialog").close();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeAccountMenu();
  }
});

accountMenu.addEventListener("click", (event) => {
  if (event.target.closest("a")) {
    closeAccountMenu();
  }
});

document.querySelectorAll(".site-dialog").forEach((dialog) => {
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });
});

searchForm.addEventListener("submit", (event) => {
  event.preventDefault();
  const query = searchInput.value.trim();

  if (!query) {
    searchStatus.textContent = "Enter a car name or type to search.";
    searchInput.focus();
    return;
  }

  searchStatus.textContent = `We can't search for "${query}" until vehicle listings are available.`;
  document.querySelector("#availability").scrollIntoView({ behavior: "smooth" });
});

contrastToggle.addEventListener("change", () => {
  document.body.classList.toggle("high-contrast", contrastToggle.checked);
});

textSizeButton.addEventListener("click", () => {
  const isLarge = document.body.classList.toggle("large-text");
  textSizeButton.textContent = isLarge ? "Use standard text size" : "Increase text size";
});

resetDisplayButton.addEventListener("click", () => {
  document.body.classList.remove("high-contrast", "large-text");
  contrastToggle.checked = false;
  textSizeButton.textContent = "Increase text size";
});
