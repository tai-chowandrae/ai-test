(function () {
  const LoginForm = document.getElementById("LoginForm");
  const LoginAlert = document.getElementById("LoginAlert");
  const EmailAddress = document.getElementById("EmailAddress");
  const Password = document.getElementById("Password");
  const LoginModal = document.getElementById("LoginModal");
  const ModalCloseButton = LoginModal.querySelector("[data-modal-close]");

  function ShowLoginMessage(Message) {
    LoginAlert.textContent = Message;
    LoginAlert.classList.add("IsVisible");
  }

  function HideLoginMessage() {
    LoginAlert.textContent = "";
    LoginAlert.classList.remove("IsVisible");
  }

  function IsValidEmailAddress(Value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(Value);
  }

  LoginForm.addEventListener("submit", function (Event) {
    HideLoginMessage();

    // Client-side validation keeps the form clear before the API is connected.
    if (!IsValidEmailAddress(EmailAddress.value.trim())) {
      Event.preventDefault();
      ShowLoginMessage("Vul een geldig e-mailadres in.");
      EmailAddress.focus();
      return;
    }

    if (Password.value.trim().length === 0) {
      Event.preventDefault();
      ShowLoginMessage("Vul je wachtwoord in.");
      Password.focus();
    }
  });

  ModalCloseButton.addEventListener("click", function () {
    LoginModal.classList.remove("IsVisible");
    LoginModal.setAttribute("aria-hidden", "true");
  });
})();
