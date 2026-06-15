<?php
session_start();

require_once __DIR__ . '/config/Database.php';

// Redirect guests to the login page before rendering protected dashboard content.
if (empty($_SESSION['UserId'])) {
    header('Location: /login', true, 302);
    exit;
}

function EscapeValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

$FirstName = (string)($_SESSION['FirstName'] ?? '');
$IsAdmin = !empty($_SESSION['IsAdmin']);
$DashboardMessage = $_SESSION['DashboardMessage'] ?? null;
$Locations = [];
$DashboardError = '';

unset($_SESSION['DashboardMessage']);

try {
    $DatabaseConnection = GetDatabaseConnection();

    $LocationsStatement = $DatabaseConnection->query(
        'SELECT LocationId, Name, FormattedAddress, DefaultTripDescription
         FROM Locations
         WHERE IsActive = 1
         ORDER BY Name ASC'
    );
    $Locations = $LocationsStatement->fetchAll();

} catch (PDOException $Exception) {
    $DashboardError = 'Dashboardgegevens konden niet worden geladen.';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Dashboard | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <main class="DashboardPage" aria-label="Dashboard">
    <section class="DashboardShell">
      <header class="DashboardTopbar">
        <a class="DashboardLogo" href="/dashboard">KM<span>2</span>WORK</a>
        <button class="IconButton" id="DashboardMenuButton" type="button" aria-label="Menu openen" aria-expanded="false">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </header>

      <aside class="DashboardMenu" id="DashboardMenu" aria-hidden="true">
        <div class="DashboardMenuHeader">
          <strong>Menu</strong>
          <button class="MenuCloseButton" id="DashboardMenuClose" type="button" aria-label="Menu sluiten">X</button>
        </div>
        <nav class="DashboardMenuList" aria-label="Dashboard menu">
          <a href="/dashboard">Dashboard</a>
          <a href="/ritten">Ritten overzicht</a>
          <?php if ($IsAdmin): ?>
            <a href="/admin">Admin openen</a>
          <?php endif; ?>
          <form action="/api/index.php" method="post">
            <input type="hidden" name="Action" value="Logout">
            <button type="submit">Uitloggen</button>
          </form>
        </nav>
      </aside>
      <div class="DashboardMenuBackdrop" id="DashboardMenuBackdrop"></div>

      <div class="DashboardContent">
        <?php if (is_array($DashboardMessage)): ?>
          <section class="DashboardAlert <?= $DashboardMessage['Type'] === 'Success' ? 'IsSuccess' : '' ?>" role="alert">
            <?= EscapeValue((string)$DashboardMessage['Message']) ?>
          </section>
        <?php endif; ?>

        <?php if ($DashboardError !== ''): ?>
          <section class="DashboardAlert" role="alert"><?= EscapeValue($DashboardError) ?></section>
        <?php endif; ?>

        <section class="DashboardPanel">
          <p class="DashboardKicker">Rittenregistratie</p>
          <form class="TripForm" action="/api/index.php" method="post">
            <input type="hidden" name="Action" value="CreateTripRegistration">

            <label>
              <span>Datum</span>
              <input name="TripDate" type="date" value="<?= date('Y-m-d') ?>" required>
            </label>

            <label>
              <span>Startlocatie</span>
              <select name="StartLocationId" required>
                <option value="">Kies start</option>
                <?php foreach ($Locations as $Location): ?>
                  <option value="<?= (int)$Location['LocationId'] ?>"><?= EscapeValue((string)$Location['Name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>Eindlocatie</span>
              <select name="EndLocationId" data-description-target="#TripDescription" required>
                <option value="">Kies einde</option>
                <?php foreach ($Locations as $Location): ?>
                  <option value="<?= (int)$Location['LocationId'] ?>" data-default-trip-description="<?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?>"><?= EscapeValue((string)$Location['Name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <span>Toelichting</span>
              <textarea name="TripDescription" id="TripDescription" rows="4" placeholder="Kies een eindlocatie of vul zelf een toelichting in"></textarea>
            </label>

            <label class="CheckboxLabel">
              <input name="IsRoundTrip" type="checkbox" value="1">
              <span>Heen en weer</span>
            </label>

            <label class="CheckboxLabel">
              <input name="ApplyCommuteCompensation" type="checkbox" value="1">
              <span>Woon-werkcompensatie (-72 km)</span>
            </label>

            <button class="PrimaryDashboardButton" type="submit">Rit opslaan</button>
          </form>
        </section>
      </div>
    </section>
  </main>
  <script src="js/dashboard.js" defer></script>
</body>
</html>
