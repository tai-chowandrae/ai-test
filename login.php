<?php
session_start();

$LoginSuccess = (string)($_SESSION['LoginSuccess'] ?? '');
$LoginError = (string)($_SESSION['LoginError'] ?? '');
$LoginOldInput = $_SESSION['LoginOldInput'] ?? [];

unset($_SESSION['LoginSuccess'], $_SESSION['LoginError'], $_SESSION['LoginOldInput']);

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
  <title>Inloggen | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <main class="LoginPage" aria-labelledby="LoginTitle">
    <section class="LoginShell">
      <header class="LoginTopbar">
        <a class="LoginLogo" href="/login" aria-label="KM2WORK login">
          KM<span>2</span>WORK
        </a>
      </header>

      <div class="LoginContent">
        <div class="LoginIntro">
          <p class="LoginKicker">Welkom terug</p>
          <h1 id="LoginTitle">Inloggen</h1>
          <p>Log in om verder te gaan met je KM2WORK omgeving.</p>
        </div>

        <form class="LoginForm" id="LoginForm" action="/api/index.php" method="post" novalidate>
          <input type="hidden" name="Action" value="Login">

          <div class="LoginAlert<?= $LoginError !== '' ? ' IsVisible' : '' ?>" id="LoginAlert" role="alert" aria-live="polite"><?= EscapeValue($LoginError) ?></div>

          <div class="FormGroup">
            <label for="EmailAddress">E-mailadres</label>
            <input id="EmailAddress" name="EmailAddress" type="email" placeholder="jouw@email.nl" autocomplete="email" value="<?= EscapeValue((string)($LoginOldInput['EmailAddress'] ?? '')) ?>" required>
          </div>

          <div class="FormGroup">
            <label for="Password">Wachtwoord</label>
            <input id="Password" name="Password" type="password" placeholder="Voer je wachtwoord in" autocomplete="current-password" required>
          </div>

          <button class="PrimaryButton" type="submit">Inloggen</button>

          <div class="LoginDivider">
            <span>Nog geen account?</span>
          </div>

          <a class="SecondaryButton" href="/register">Account aanmaken</a>
        </form>
      </div>
    </section>
  </main>

  <div class="ModalOverlay<?= $LoginSuccess !== '' ? ' IsVisible' : '' ?>" id="LoginModal" aria-hidden="<?= $LoginSuccess !== '' ? 'false' : 'true' ?>">
    <section class="ModalDialog" role="dialog" aria-modal="true" aria-labelledby="LoginModalTitle">
      <p class="ModalKicker">Gelukt</p>
      <h2 id="LoginModalTitle">Account aangemaakt</h2>
      <p id="LoginModalMessage"><?= EscapeValue($LoginSuccess) ?></p>
      <button class="PrimaryButton ModalButton" type="button" data-modal-close>Inloggen</button>
    </section>
  </div>

  <script src="js/login.js" defer></script>
</body>
</html>
