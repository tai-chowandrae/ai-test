<?php
session_start();

$RegisterError = (string)($_SESSION['RegisterError'] ?? '');
$RegisterOldInput = $_SESSION['RegisterOldInput'] ?? [];

unset($_SESSION['RegisterError'], $_SESSION['RegisterOldInput']);

function EscapeValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Account aanmaken | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/register.css">
</head>
<body>
  <main class="LoginPage" aria-labelledby="RegisterTitle">
    <section class="LoginShell RegisterShell">
      <header class="LoginTopbar">
        <a class="LoginLogo" href="/login" aria-label="KM2WORK login">
          KM<span>2</span>WORK
        </a>
      </header>

      <div class="LoginContent RegisterContent">
        <div class="LoginIntro RegisterIntro">
          <p class="LoginKicker">Nieuw account</p>
          <h1 id="RegisterTitle">Registreren</h1>
          <p>Maak een account aan om verder te gaan.</p>
        </div>

        <form class="LoginForm" id="RegisterForm" action="/api/index.php" method="post" novalidate>
          <input type="hidden" name="Action" value="Register">

          <div class="FormRow">
            <div class="FormGroup">
              <label for="FirstName">Voornaam</label>
              <input id="FirstName" name="FirstName" type="text" placeholder="Jan" autocomplete="given-name" value="<?= EscapeValue((string)($RegisterOldInput['FirstName'] ?? '')) ?>" required>
            </div>

            <div class="FormGroup">
              <label for="LastName">Achternaam</label>
              <input id="LastName" name="LastName" type="text" placeholder="de Vries" autocomplete="family-name" value="<?= EscapeValue((string)($RegisterOldInput['LastName'] ?? '')) ?>" required>
            </div>
          </div>

          <div class="FormGroup">
            <label for="EmailAddress">E-mailadres</label>
            <input id="EmailAddress" name="EmailAddress" type="email" placeholder="jouw@email.nl" autocomplete="email" value="<?= EscapeValue((string)($RegisterOldInput['EmailAddress'] ?? '')) ?>" required>
          </div>

          <div class="FormGroup">
            <label for="Password">Wachtwoord</label>
            <input id="Password" name="Password" type="password" placeholder="Minimaal 8 tekens" autocomplete="new-password" minlength="8" required>
            <p class="FormHint">Gebruik minimaal 8 tekens.</p>
          </div>

          <button class="PrimaryButton" type="submit">Account aanmaken</button>

          <div class="LoginDivider">
            <span>Al een account?</span>
          </div>

          <a class="SecondaryButton" href="/login">Terug naar inloggen</a>
        </form>
      </div>
    </section>
  </main>

  <div class="ModalOverlay<?= $RegisterError !== '' ? ' IsVisible' : '' ?>" id="RegisterModal" aria-hidden="<?= $RegisterError !== '' ? 'false' : 'true' ?>">
    <section class="ModalDialog" role="dialog" aria-modal="true" aria-labelledby="RegisterModalTitle">
      <p class="ModalKicker">Niet gelukt</p>
      <h2 id="RegisterModalTitle">Account niet aangemaakt</h2>
      <p id="RegisterModalMessage"><?= EscapeValue($RegisterError) ?></p>
      <button class="PrimaryButton ModalButton" type="button" data-modal-close>Opnieuw proberen</button>
    </section>
  </div>

  <script src="js/register.js" defer></script>
</body>
</html>
