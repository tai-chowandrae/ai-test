(function () {
  const MenuButton = document.getElementById("DashboardMenuButton");
  const MenuCloseButton = document.getElementById("DashboardMenuClose");
  const Menu = document.getElementById("DashboardMenu");
  const Backdrop = document.getElementById("DashboardMenuBackdrop");

  if (!MenuButton || !Menu || !Backdrop) {
    return;
  }

  function SetMenuOpen(IsOpen) {
    Menu.classList.toggle("IsOpen", IsOpen);
    Backdrop.classList.toggle("IsOpen", IsOpen);
    Menu.setAttribute("aria-hidden", IsOpen ? "false" : "true");
    MenuButton.setAttribute("aria-expanded", IsOpen ? "true" : "false");
  }

  MenuButton.addEventListener("click", function () {
    SetMenuOpen(true);
  });

  if (MenuCloseButton) {
    MenuCloseButton.addEventListener("click", function () {
      SetMenuOpen(false);
    });
  }

  Backdrop.addEventListener("click", function () {
    SetMenuOpen(false);
  });

  Menu.querySelectorAll("a").forEach(function (MenuLink) {
    MenuLink.addEventListener("click", function () {
      SetMenuOpen(false);
    });
  });
})();

(function () {
  const EndLocationSelects = document.querySelectorAll('select[name="EndLocationId"][data-description-target]');

  EndLocationSelects.forEach(function (EndLocationSelect) {
    EndLocationSelect.addEventListener("change", function () {
      const TripDescription = document.querySelector(EndLocationSelect.dataset.descriptionTarget);
      const SelectedOption = EndLocationSelect.options[EndLocationSelect.selectedIndex];
      const DefaultTripDescription = SelectedOption ? SelectedOption.dataset.defaultTripDescription || "" : "";

      if (TripDescription) {
        TripDescription.value = DefaultTripDescription;
      }
    });
  });
})();

(function () {
  document.querySelectorAll("form[data-confirm]").forEach(function (Form) {
    Form.addEventListener("submit", function (Event) {
      const Message = Form.dataset.confirm || "Weet je het zeker?";

      if (!window.confirm(Message)) {
        Event.preventDefault();
      }
    });
  });
})();

(function () {
  document.querySelectorAll(".TripEditToggle").forEach(function (ToggleButton) {
    ToggleButton.addEventListener("click", function () {
      const TripRow = ToggleButton.closest(".TripRow");
      const EditDetails = TripRow ? TripRow.nextElementSibling : null;

      if (!EditDetails || !EditDetails.classList.contains("TripEditDetails")) {
        return;
      }

      EditDetails.open = !EditDetails.open;
      ToggleButton.setAttribute("aria-expanded", EditDetails.open ? "true" : "false");
    });
  });
})();
