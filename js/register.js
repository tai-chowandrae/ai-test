(function () {
  const RegisterForm = document.getElementById("RegisterForm");
  const RegisterModal = document.getElementById("RegisterModal");
  const RegisterModalMessage = document.getElementById("RegisterModalMessage");
  const ModalCloseButton = RegisterModal.querySelector("[data-modal-close]");
  const RequiredFields = [
    document.getElementById("FirstName"),
    document.getElementById("LastName"),
    document.getElementById("EmailAddress"),
    document.getElementById("Password"),
  ];

  function ShowRegisterModal(Message) {
    RegisterModalMessage.textContent = Message;
    RegisterModal.classList.add("IsVisible");
    RegisterModal.setAttribute("aria-hidden", "false");
    ModalCloseButton.focus();
  }

  function HideRegisterModal() {
    RegisterModal.classList.remove("IsVisible");
    RegisterModal.setAttribute("aria-hidden", "true");
  }

  function IsValidEmailAddress(Value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(Value);
  }

  RegisterForm.addEventListener("submit", function (Event) {
    const EmptyField = RequiredFields.find(function (Field) {
      return Field.value.trim().length === 0;
    });

    // Client-side validation mirrors the API validation for immediate feedback.
    if (EmptyField) {
      Event.preventDefault();
      ShowRegisterModal("Alle velden zijn verplicht.");
      return;
    }

    if (!IsValidEmailAddress(RequiredFields[2].value.trim())) {
      Event.preventDefault();
      ShowRegisterModal("Vul een geldig e-mailadres in.");
      return;
    }

    if (RequiredFields[3].value.length < 8) {
      Event.preventDefault();
      ShowRegisterModal("Het wachtwoord moet minimaal 8 tekens bevatten.");
    }
  });

  ModalCloseButton.addEventListener("click", HideRegisterModal);
})();
