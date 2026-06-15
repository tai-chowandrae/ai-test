<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/includes/TripOverviewRenderer.php';

// Redirect guests to the login page before rendering protected trip content.
if (empty($_SESSION['UserId'])) {
    header('Location: /login', true, 302);
    exit;
}

function EscapeValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

function FormatTripDate(string $Value): string
{
    $DateTime = DateTime::createFromFormat('Y-m-d', $Value);

    $DayNames = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
        6 => 'zaterdag',
        7 => 'zondag',
    ];

    return $DateTime ? $DayNames[(int)$DateTime->format('N')] . ' ' . $DateTime->format('d-m-Y') : $Value;
}

function FormatDistance(float $Value): string
{
    return number_format($Value, 2, ',', '.');
}

$FirstName = (string)($_SESSION['FirstName'] ?? '');
$IsAdmin = !empty($_SESSION['IsAdmin']);
$TripRegistrations = [];
$Locations = [];
$TripPageSize = 20;
$HasMoreTrips = false;
$TripTotals = [
    'PreviousMonth' => 0.0,
    'CurrentMonth' => 0.0,
    'CurrentYear' => 0.0,
];
$TripsError = '';
$TripsMessage = $_SESSION['TripsMessage'] ?? null;

unset($_SESSION['TripsMessage']);

try {
    $DatabaseConnection = GetDatabaseConnection();

    $LocationsStatement = $DatabaseConnection->query(
        'SELECT LocationId, Name, DefaultTripDescription, IsActive
         FROM Locations
         ORDER BY Name ASC'
    );
    $Locations = $LocationsStatement->fetchAll();

    $CurrentMonth = date('Y-m');
    $PreviousMonth = date('Y-m', strtotime('first day of previous month'));
    $CurrentYear = date('Y');

    $TripTotalsStatement = $DatabaseConnection->prepare(
        'SELECT
             COALESCE(SUM(CASE WHEN DATE_FORMAT(TripDate, "%Y-%m") = :PreviousMonth THEN DistanceKilometers ELSE 0 END), 0) AS PreviousMonth,
             COALESCE(SUM(CASE WHEN DATE_FORMAT(TripDate, "%Y-%m") = :CurrentMonth THEN DistanceKilometers ELSE 0 END), 0) AS CurrentMonth,
             COALESCE(SUM(CASE WHEN DATE_FORMAT(TripDate, "%Y") = :CurrentYear THEN DistanceKilometers ELSE 0 END), 0) AS CurrentYear
         FROM TripRegistrations
         WHERE UserId = :UserId'
    );
    $TripTotalsStatement->execute([
        'PreviousMonth' => $PreviousMonth,
        'CurrentMonth' => $CurrentMonth,
        'CurrentYear' => $CurrentYear,
        'UserId' => (int)$_SESSION['UserId'],
    ]);
    $LoadedTripTotals = $TripTotalsStatement->fetch();

    if ($LoadedTripTotals) {
        $TripTotals['PreviousMonth'] = (float)$LoadedTripTotals['PreviousMonth'];
        $TripTotals['CurrentMonth'] = (float)$LoadedTripTotals['CurrentMonth'];
        $TripTotals['CurrentYear'] = (float)$LoadedTripTotals['CurrentYear'];
    }

    $TripsStatement = $DatabaseConnection->prepare(
        'SELECT TripRegistrations.TripRegistrationId, TripRegistrations.TripDate,
                TripRegistrations.StartLocationId, TripRegistrations.EndLocationId,
                TripRegistrations.IsRoundTrip, TripRegistrations.ApplyCommuteCompensation,
                TripRegistrations.TripDescription, TripRegistrations.DistanceKilometers,
                StartLocations.Name AS StartLocationName,
                EndLocations.Name AS EndLocationName
         FROM TripRegistrations
         INNER JOIN Locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
         INNER JOIN Locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
         WHERE TripRegistrations.UserId = :UserId
         ORDER BY TripRegistrations.TripDate DESC, TripRegistrations.TripRegistrationId DESC
         LIMIT :Limit OFFSET :Offset'
    );
    $TripsStatement->bindValue('UserId', (int)$_SESSION['UserId'], PDO::PARAM_INT);
    $TripsStatement->bindValue('Limit', $TripPageSize + 1, PDO::PARAM_INT);
    $TripsStatement->bindValue('Offset', 0, PDO::PARAM_INT);
    $TripsStatement->execute();
    $TripRegistrations = $TripsStatement->fetchAll();
    $HasMoreTrips = count($TripRegistrations) > $TripPageSize;
    $TripRegistrations = array_slice($TripRegistrations, 0, $TripPageSize);
} catch (PDOException $Exception) {
    $TripsError = 'Ritten konden niet worden geladen.';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Ritten overzicht | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <main class="DashboardPage" aria-labelledby="TripsTitle">
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
        <section class="TripTotalsPanel" aria-label="Kilometer totalen">
          <div class="TripTotalsGrid">
            <article class="TripTotalItem">
              <span>Vorige maand</span>
              <strong><?= EscapeValue(FormatDistance($TripTotals['PreviousMonth'])) ?> km</strong>
            </article>
            <article class="TripTotalItem">
              <span>Huidige maand</span>
              <strong><?= EscapeValue(FormatDistance($TripTotals['CurrentMonth'])) ?> km</strong>
            </article>
            <article class="TripTotalItem">
              <span>Huidig jaar</span>
              <strong><?= EscapeValue(FormatDistance($TripTotals['CurrentYear'])) ?> km</strong>
            </article>
          </div>
        </section>

        <?php if ($TripsError !== ''): ?>
          <section class="DashboardAlert" role="alert"><?= EscapeValue($TripsError) ?></section>
        <?php endif; ?>

        <?php if (is_array($TripsMessage)): ?>
          <section class="DashboardAlert <?= $TripsMessage['Type'] === 'Success' ? 'IsSuccess' : '' ?>" role="alert">
            <?= EscapeValue((string)$TripsMessage['Message']) ?>
          </section>
        <?php endif; ?>

        <section
          class="TripOverview"
          id="TripOverview"
          data-trip-page-size="<?= (int)$TripPageSize ?>"
          data-next-offset="<?= count($TripRegistrations) ?>"
          data-has-more="<?= $HasMoreTrips ? '1' : '0' ?>"
          aria-label="Ritten overzicht"
        >
          <?php if ($TripRegistrations): ?>
            <?= RenderTripOverviewGroups($TripRegistrations, $Locations) ?>
          <?php else: ?>
            <article class="ActionRow">
              <span class="ActionIcon">R</span>
              <span>
                <strong>Nog geen ritten</strong>
                <small>Je opgeslagen ritten verschijnen hier.</small>
              </span>
            </article>
          <?php endif; ?>
        </section>
        <div class="TripLoadingState" id="TripLoadingState" hidden>Ritten laden...</div>
        <div class="TripLoadSentinel" id="TripLoadSentinel" aria-hidden="true"></div>
      </div>
    </section>
  </main>
  <script src="js/dashboard.js" defer></script>
</body>
</html>
