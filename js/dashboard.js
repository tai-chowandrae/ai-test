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

function InitializeDashboardTripControls(Root) {
  Root.querySelectorAll('select[name="EndLocationId"][data-description-target]').forEach(function (EndLocationSelect) {
    if (EndLocationSelect.dataset.descriptionBindingReady === "1") {
      return;
    }

    EndLocationSelect.dataset.descriptionBindingReady = "1";
    EndLocationSelect.addEventListener("change", function () {
      const TripDescription = document.querySelector(EndLocationSelect.dataset.descriptionTarget);
      const SelectedOption = EndLocationSelect.options[EndLocationSelect.selectedIndex];
      const DefaultTripDescription = SelectedOption ? SelectedOption.dataset.defaultTripDescription || "" : "";

      if (TripDescription) {
        TripDescription.value = DefaultTripDescription;
      }
    });
  });

  Root.querySelectorAll("form[data-confirm]").forEach(function (Form) {
    if (Form.dataset.confirmBindingReady === "1") {
      return;
    }

    Form.dataset.confirmBindingReady = "1";
    Form.addEventListener("submit", function (Event) {
      const Message = Form.dataset.confirm || "Weet je het zeker?";

      if (!window.confirm(Message)) {
        Event.preventDefault();
      }
    });
  });

  Root.querySelectorAll(".TripEditToggle").forEach(function (ToggleButton) {
    if (ToggleButton.dataset.editBindingReady === "1") {
      return;
    }

    ToggleButton.dataset.editBindingReady = "1";
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
}

InitializeDashboardTripControls(document);

(function () {
  const TripOverview = document.getElementById("TripOverview");
  const LoadSentinel = document.getElementById("TripLoadSentinel");
  const LoadingState = document.getElementById("TripLoadingState");

  if (!TripOverview || !LoadSentinel) {
    return;
  }

  let IsLoading = false;

  function SetLoadingState(IsActive) {
    IsLoading = IsActive;

    if (LoadingState) {
      LoadingState.hidden = !IsActive;
    }
  }

  async function LoadMoreTrips() {
    if (IsLoading || TripOverview.dataset.hasMore !== "1") {
      return;
    }

    SetLoadingState(true);

    try {
      const RequestBody = new URLSearchParams({
        Action: "LoadTripRegistrations",
        Offset: TripOverview.dataset.nextOffset || "0",
        Limit: TripOverview.dataset.tripPageSize || "20",
      });

      const Response = await fetch("/api/index.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: RequestBody,
      });

      const ResponseData = await Response.json();

      if (!Response.ok || !ResponseData.Ok) {
        TripOverview.dataset.hasMore = "0";
        return;
      }

      if (ResponseData.Html) {
        TripOverview.insertAdjacentHTML("beforeend", ResponseData.Html);
        InitializeDashboardTripControls(TripOverview);
      }

      TripOverview.dataset.nextOffset = String(ResponseData.NextOffset || TripOverview.dataset.nextOffset || "0");
      TripOverview.dataset.hasMore = ResponseData.HasMore ? "1" : "0";
    } catch (Error) {
      TripOverview.dataset.hasMore = "0";
    } finally {
      SetLoadingState(false);
    }
  }

  if ("IntersectionObserver" in window) {
    const Observer = new IntersectionObserver(function (Entries) {
      if (Entries.some(function (Entry) {
        return Entry.isIntersecting;
      })) {
        LoadMoreTrips();
      }
    }, {
      rootMargin: "0px",
    });

    Observer.observe(LoadSentinel);
    return;
  }

  window.addEventListener("scroll", function () {
    const SentinelPosition = LoadSentinel.getBoundingClientRect().top;

    if (SentinelPosition < window.innerHeight) {
      LoadMoreTrips();
    }
  });
})();
