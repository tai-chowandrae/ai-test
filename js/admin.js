(function () {
  const FilterInputs = Array.from(document.querySelectorAll("[data-filter-input]"));

  if (!FilterInputs.length) {
    return;
  }

  FilterInputs.forEach(function (FilterInput) {
    FilterInput.addEventListener("input", function () {
      const FilterName = FilterInput.dataset.filterInput;
      const SearchValue = FilterInput.value.trim().toLowerCase();
      const FilterItems = Array.from(document.querySelectorAll('[data-filter-item="' + FilterName + '"]'));
      const FilterGroups = Array.from(document.querySelectorAll('[data-filter-group="' + FilterName + '"]')).reverse();

      FilterItems.forEach(function (FilterItem) {
        const RowValue = FilterItem.dataset.searchValue || "";
        FilterItem.classList.toggle("IsHidden", SearchValue !== "" && !RowValue.includes(SearchValue));
      });

      FilterGroups.forEach(function (FilterGroup) {
        const VisibleItems = Array.from(FilterGroup.querySelectorAll('[data-filter-item="' + FilterName + '"]')).filter(function (FilterItem) {
          return !FilterItem.classList.contains("IsHidden");
        });
        const VisibleGroups = Array.from(FilterGroup.querySelectorAll('[data-filter-group="' + FilterName + '"]')).filter(function (NestedGroup) {
          return NestedGroup !== FilterGroup && !NestedGroup.classList.contains("IsHidden");
        });

        FilterGroup.classList.toggle("IsHidden", SearchValue !== "" && VisibleItems.length === 0 && VisibleGroups.length === 0);
      });
    });
  });
})();

(function () {
  const ConfirmForms = Array.from(document.querySelectorAll("form[data-confirm]"));

  ConfirmForms.forEach(function (ConfirmForm) {
    ConfirmForm.addEventListener("submit", function (Event) {
      const Message = ConfirmForm.dataset.confirm || "Weet je het zeker?";

      if (!window.confirm(Message)) {
        Event.preventDefault();
      }
    });
  });
})();

(function () {
  const ViewButtons = Array.from(document.querySelectorAll("[data-admin-view]"));
  const ViewSections = Array.from(document.querySelectorAll("[data-admin-section]"));

  if (!ViewButtons.length || !ViewSections.length) {
    return;
  }

  function SetActiveView(ViewName) {
    ViewButtons.forEach(function (ViewButton) {
      ViewButton.classList.toggle("IsActive", ViewButton.dataset.adminView === ViewName);
    });

    ViewSections.forEach(function (ViewSection) {
      ViewSection.classList.toggle("IsActive", ViewSection.dataset.adminSection === ViewName);
    });
  }

  ViewButtons.forEach(function (ViewButton) {
    ViewButton.addEventListener("click", function () {
      SetActiveView(ViewButton.dataset.adminView);
      window.history.replaceState(null, "", "#Admin" + ViewButton.dataset.adminView);
    });
  });

  if (window.location.hash.indexOf("#Admin") === 0) {
    const ViewName = window.location.hash.replace("#Admin", "");

    if (ViewButtons.some(function (ViewButton) {
      return ViewButton.dataset.adminView === ViewName;
    })) {
      SetActiveView(ViewName);
    }
  }

  if (window.location.hash === "#ExportPanel" || window.location.search.includes("ExportStartDate")) {
    SetActiveView("Export");
  }
})();

(function () {
  const ExportTextarea = document.getElementById("ExportTextarea");
  const ExportCopyButton = document.getElementById("ExportCopyButton");

  if (!ExportTextarea || !ExportCopyButton) {
    return;
  }

  ExportCopyButton.addEventListener("click", async function () {
    ExportTextarea.select();
    ExportTextarea.setSelectionRange(0, ExportTextarea.value.length);

    try {
      await navigator.clipboard.writeText(ExportTextarea.value);
      ExportCopyButton.textContent = "Gekopieerd";
    } catch (Error) {
      document.execCommand("copy");
      ExportCopyButton.textContent = "Gekopieerd";
    }

    window.setTimeout(function () {
      ExportCopyButton.textContent = "Kopieren";
    }, 1800);
  });
})();

(function () {
  document.querySelectorAll(".AdminTripEditToggle").forEach(function (ToggleButton) {
    ToggleButton.addEventListener("click", function () {
      const TripRow = ToggleButton.closest(".TripAdminRow");
      const EditPanel = TripRow ? TripRow.nextElementSibling : null;

      if (!EditPanel || !EditPanel.classList.contains("AdminTripEditPanel")) {
        return;
      }

      const IsOpen = EditPanel.classList.toggle("IsOpen");
      ToggleButton.setAttribute("aria-expanded", IsOpen ? "true" : "false");
    });
  });
})();

(function () {
  document.querySelectorAll('select[name="EndLocationId"][data-description-target]').forEach(function (EndLocationSelect) {
    EndLocationSelect.addEventListener("change", function () {
      const DescriptionInput = document.querySelector(EndLocationSelect.dataset.descriptionTarget);
      const SelectedOption = EndLocationSelect.options[EndLocationSelect.selectedIndex];
      const DefaultDescription = SelectedOption ? SelectedOption.dataset.defaultTripDescription || "" : "";

      if (DescriptionInput) {
        DescriptionInput.value = DefaultDescription;
      }
    });
  });
})();

(function () {
  let HasSelectedPlace = false;
  let SearchTimer = null;
  const MinimumSearchLength = 5;

  const LocationForm = document.getElementById("LocationForm");
  const SearchInput = document.getElementById("GoogleLocationSearch");
  const SuggestionsList = document.getElementById("LocationSuggestions");
  const PlaceIdInput = document.getElementById("GooglePlaceId");
  const AddressInput = document.getElementById("FormattedAddress");
  const LatitudeInput = document.getElementById("Latitude");
  const LongitudeInput = document.getElementById("Longitude");

  if (!LocationForm || !SearchInput || !SuggestionsList) {
    return;
  }

  async function PostApi(Action, Data) {
    const Body = new URLSearchParams();
    Body.set("Action", Action);

    Object.keys(Data).forEach(function (Key) {
      Body.set(Key, Data[Key]);
    });

    const Response = await fetch("/api/index.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: Body.toString(),
    });

    const ResponseData = await Response.json();

    if (!Response.ok || !ResponseData.Ok) {
      throw new Error(ResponseData.Error || "De Google Maps zoekactie is niet gelukt.");
    }

    return ResponseData;
  }

  function ClearSelectedPlace() {
    HasSelectedPlace = false;
    PlaceIdInput.value = "";
    AddressInput.value = "";
    LatitudeInput.value = "";
    LongitudeInput.value = "";
  }

  function ClearSuggestions() {
    SuggestionsList.innerHTML = "";
    SuggestionsList.classList.remove("IsVisible");
  }

  function RenderSuggestionMessage(Message) {
    SuggestionsList.innerHTML = "";

    const MessageElement = document.createElement("div");
    MessageElement.className = "LocationSuggestionMessage";
    MessageElement.textContent = Message;
    SuggestionsList.appendChild(MessageElement);
    SuggestionsList.classList.add("IsVisible");
  }

  function FillSelectedPlace(Place) {
    HasSelectedPlace = Boolean(Place.PlaceId);
    SearchInput.value = Place.FormattedAddress;
    PlaceIdInput.value = Place.PlaceId;
    AddressInput.value = Place.FormattedAddress;
    LatitudeInput.value = Place.Latitude || "";
    LongitudeInput.value = Place.Longitude || "";
    SearchInput.setCustomValidity("");
    ClearSuggestions();
  }

  async function SelectSuggestion(Suggestion) {
    try {
      RenderSuggestionMessage("Locatie laden...");
      const ResponseData = await PostApi("GetLocationDetails", {
        GooglePlaceId: Suggestion.PlaceId,
      });
      FillSelectedPlace(ResponseData.Place);
    } catch (Error) {
      RenderSuggestionMessage(Error.message);
    }
  }

  function RenderSuggestions(Suggestions) {
    SuggestionsList.innerHTML = "";

    if (!Suggestions.length) {
      RenderSuggestionMessage("Geen locaties gevonden.");
      return;
    }

    Suggestions.forEach(function (Suggestion) {
      const Button = document.createElement("button");
      Button.type = "button";
      Button.className = "LocationSuggestion";
      Button.textContent = Suggestion.Description;
      Button.addEventListener("click", function () {
        SelectSuggestion(Suggestion);
      });
      SuggestionsList.appendChild(Button);
    });

    SuggestionsList.classList.add("IsVisible");
  }

  async function SearchPlaces(Query) {
    if (Query.length < MinimumSearchLength) {
      ClearSuggestions();
      return;
    }

    try {
      RenderSuggestionMessage("Locaties zoeken...");
      const ResponseData = await PostApi("SearchLocations", {
        Query: Query,
      });
      RenderSuggestions(ResponseData.Suggestions || []);
    } catch (Error) {
      RenderSuggestionMessage(Error.message);
    }
  }

  SearchInput.addEventListener("input", function () {
    ClearSelectedPlace();

    window.clearTimeout(SearchTimer);
    SearchTimer = window.setTimeout(function () {
      SearchPlaces(SearchInput.value.trim());
    }, 220);
  });

  document.addEventListener("click", function (Event) {
    if (SuggestionsList.contains(Event.target) || Event.target === SearchInput) {
      return;
    }

    ClearSuggestions();
  });

  LocationForm.addEventListener("submit", function (Event) {
    if (!HasSelectedPlace || PlaceIdInput.value.trim() === "") {
      Event.preventDefault();
      SearchInput.focus();
      SearchInput.setCustomValidity("Selecteer een locatie uit de lijst met suggesties.");
      SearchInput.reportValidity();
      return;
    }

    SearchInput.setCustomValidity("");
  });
})();
