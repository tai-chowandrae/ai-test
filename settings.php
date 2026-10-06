<?php
session_start();

require_once __DIR__ . '/config/Database.php';

if (empty($_SESSION['UserId'])) {
    header('Location: /login', true, 302);
    exit;
}

function EscapeValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

$SettingsMessage = $_SESSION['SettingsMessage'] ?? null;
$User = null;
$SettingsError = '';

unset($_SESSION['SettingsMessage']);

try {
    $DatabaseConnection = GetDatabaseConnection();
    $UserStatement = $DatabaseConnection->prepare(
        'SELECT UserId, FirstName, LastName, EmailAddress,
                IsCommuteCompensationEnabled, CommuteCompensationKilometers
         FROM users
         WHERE UserId = :UserId
         LIMIT 1'
    );
    $UserStatement->execute(['UserId' => (int)$_SESSION['UserId']]);
    $User = $UserStatement->fetch();

    if (!$User) {
        session_destroy();
        header('Location: /login', true, 302);
        exit;
    }
} catch (PDOException $Exception) {
    $SettingsError = 'Je gegevens konden niet worden geladen.';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="Pas je persoonlijke KM2WORK-gegevens aan.">
  <title>Settings | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="AdminShell">
    <header class="AdminTopbar">
      <a class="AdminLogo" href="/dashboard">KM<span>2</span>WORK <small>Settings</small></a>
      <div class="TopbarRight">
        <a class="TopbarButton" href="/dashboard">Dashboard</a>
        <form action="/api/index.php" method="post">
          <input type="hidden" name="Action" value="Logout">
          <button class="TopbarButton IsDanger" type="submit">Uitloggen</button>
        </form>
      </div>
    </header>

    <div class="AdminBody">
      <main class="MainContent" aria-label="Persoonlijke instellingen">
        <?php if (is_array($SettingsMessage)): ?>
          <section class="AlertCard <?= ($SettingsMessage['Type'] ?? '') === 'Success' ? 'IsSuccess' : '' ?>" role="alert">
            <?= EscapeValue((string)($SettingsMessage['Message'] ?? '')) ?>
          </section>
        <?php endif; ?>

        <?php if ($SettingsError !== ''): ?>
          <section class="AlertCard" role="alert"><?= EscapeValue($SettingsError) ?></section>
        <?php endif; ?>

        <?php if (is_array($User)): ?>
          <section class="ContentPanel">
            <div class="PanelTitle">
              <span>Mijn gegevens bewerken</span>
            </div>

            <form class="AdminForm UserCreateForm" action="/api/index.php" method="post">
              <input type="hidden" name="Action" value="UpdateOwnUser">

              <label>
                <span class="FormLabel">Voornaam</span>
                <input class="FormInput" name="FirstName" type="text" value="<?= EscapeValue((string)$User['FirstName']) ?>" required>
              </label>
              <label>
                <span class="FormLabel">Achternaam</span>
                <input class="FormInput" name="LastName" type="text" value="<?= EscapeValue((string)$User['LastName']) ?>" required>
              </label>
              <label>
                <span class="FormLabel">E-mailadres</span>
                <input class="FormInput" name="EmailAddress" type="email" value="<?= EscapeValue((string)$User['EmailAddress']) ?>" required>
              </label>

              <label class="AdminCheckboxLabel AdminFormFull">
                <input type="hidden" name="IsCommuteCompensationEnabled" value="0">
                <input name="IsCommuteCompensationEnabled" type="checkbox" value="1"<?= (int)$User['IsCommuteCompensationEnabled'] === 1 ? ' checked' : '' ?>>
                <span>Woon-werkcompensatie tonen bij het invoeren van een rit</span>
              </label>

              <label>
                <span class="FormLabel">Woon-werkcompensatie in kilometers</span>
                <input class="FormInput" name="CommuteCompensationKilometers" type="number" min="0" max="1000" step="0.01" value="<?= EscapeValue(number_format((float)$User['CommuteCompensationKilometers'], 2, '.', '')) ?>" required>
              </label>

              <button class="PrimaryAdminButton" type="submit">Wijzigingen opslaan</button>
            </form>
          </section>
        <?php endif; ?>
      </main>
    </div>
  </div>
</body>
</html>
