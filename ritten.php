<?php
session_start();

require_once __DIR__ . '/config/Database.php';

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
$GroupedTripRegistrations = [];
$Locations = [];
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
        'SELECT LocationId, Name, DefaultTripDescription
         FROM Locations
         ORDER BY Name ASC'
    );
    $Locations = $LocationsStatement->fetchAll();

    $TripsStatement = $DatabaseConnection->prepare(
        'SELECT TripRegistrations.TripRegistrationId, TripRegistrations.TripDate,
                TripRegistrations.StartLocationId, TripRegistrations.EndLocationId,
                TripRegistrations.IsRoundTrip, TripRegistrations.TripDescription, TripRegistrations.DistanceKilometers,
                StartLocations.Name AS StartLocationName,
                EndLocations.Name AS EndLocationName
         FROM TripRegistrations
         INNER JOIN Locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
         INNER JOIN Locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
         WHERE TripRegistrations.UserId = :UserId
         ORDER BY TripRegistrations.TripDate DESC, TripRegistrations.TripRegistrationId DESC'
    );
    $TripsStatement->execute(['UserId' => (int)$_SESSION['UserId']]);
    $TripRegistrations = $TripsStatement->fetchAll();

    $CurrentMonth = date('Y-m');
    $PreviousMonth = date('Y-m', strtotime('first day of previous month'));
    $CurrentYear = date('Y');

    foreach ($TripRegistrations as $TripRegistration) {
        $TripDate = (string)$TripRegistration['TripDate'];
        $DistanceKilometers = (float)$TripRegistration['DistanceKilometers'];

        $GroupedTripRegistrations[$TripDate][] = $TripRegistration;

        if (substr($TripDate, 0, 7) === $PreviousMonth) {
            $TripTotals['PreviousMonth'] += $DistanceKilometers;
        }

        if (substr($TripDate, 0, 7) === $CurrentMonth) {
            $TripTotals['CurrentMonth'] += $DistanceKilometers;
        }

        if (substr($TripDate, 0, 4) === $CurrentYear) {
            $TripTotals['CurrentYear'] += $DistanceKilometers;
        }
    }
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
  <title>Ritten overzicht | Skills2Work</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <main class="DashboardPage" aria-labelledby="TripsTitle">
    <section class="DashboardShell">
      <header class="DashboardTopbar">
        <a class="DashboardLogo" href="/dashboard">SKILLS<span>2</span>WORK</a>
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

        <section class="TripOverview" aria-label="Ritten overzicht">
          <?php if ($GroupedTripRegistrations): ?>
            <?php foreach ($GroupedTripRegistrations as $TripDate => $TripsForDate): ?>
              <?php
                $DayTotal = 0.0;
                foreach ($TripsForDate as $TripForTotal) {
                    $DayTotal += (float)$TripForTotal['DistanceKilometers'];
                }
              ?>
              <article class="TripDayGroup">
                <header class="TripDayHeader">
                  <strong><?= EscapeValue(FormatTripDate((string)$TripDate)) ?></strong>
                  <span><?= EscapeValue(FormatDistance($DayTotal)) ?> km</span>
                </header>
                <?php foreach ($TripsForDate as $TripRegistration): ?>
                  <div class="TripRow">
                    <span class="TripSummary">
                      <span class="TripTitleLine">
                        <strong><?= EscapeValue((string)$TripRegistration['StartLocationName']) ?> naar <?= EscapeValue((string)$TripRegistration['EndLocationName']) ?><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' v.v.' : '' ?></strong>
                        <button class="TripEditToggle" type="button" aria-expanded="false">Bewerken</button>
                      </span>
                      <small><?= EscapeValue(FormatDistance((float)$TripRegistration['DistanceKilometers'])) ?> km</small>
                      <?php if (!empty($TripRegistration['TripDescription'])): ?>
                        <small class="TripDescriptionText"><?= EscapeValue((string)$TripRegistration['TripDescription']) ?></small>
                      <?php endif; ?>
                    </span>
                  </div>
                  <details class="TripEditDetails">
                    <summary>Bewerken</summary>
                    <form class="TripForm TripEditForm" action="/api/index.php" method="post">
                      <input type="hidden" name="Action" value="UpdateTripRegistration">
                      <input type="hidden" name="TripRegistrationId" value="<?= (int)$TripRegistration['TripRegistrationId'] ?>">

                      <label>
                        <span>Datum</span>
                        <input name="TripDate" type="date" value="<?= EscapeValue((string)$TripRegistration['TripDate']) ?>" required>
                      </label>

                      <label>
                        <span>Startlocatie</span>
                        <select name="StartLocationId" required>
                          <?php foreach ($Locations as $Location): ?>
                            <option value="<?= (int)$Location['LocationId'] ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>

                      <label>
                        <span>Eindlocatie</span>
                        <select name="EndLocationId" data-description-target="#TripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" required>
                          <?php foreach ($Locations as $Location): ?>
                            <option value="<?= (int)$Location['LocationId'] ?>" data-default-trip-description="<?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>

                      <label>
                        <span>Toelichting</span>
                        <textarea name="TripDescription" id="TripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" rows="4"><?= EscapeValue((string)($TripRegistration['TripDescription'] ?? '')) ?></textarea>
                      </label>

                      <label class="CheckboxLabel">
                        <input name="IsRoundTrip" type="checkbox" value="1"<?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' checked' : '' ?>>
                        <span>Heen en weer</span>
                      </label>

                      <div class="TripEditActions">
                        <button class="PrimaryDashboardButton" type="submit">Opslaan</button>
                      </div>
                    </form>
                    <form class="TripDeleteForm" action="/api/index.php" method="post" data-confirm="Weet je zeker dat je deze rit wilt verwijderen?">
                      <input type="hidden" name="Action" value="DeleteTripRegistration">
                      <input type="hidden" name="TripRegistrationId" value="<?= (int)$TripRegistration['TripRegistrationId'] ?>">
                      <button class="DangerDashboardButton" type="submit">Verwijderen</button>
                    </form>
                  </details>
                <?php endforeach; ?>
              </article>
            <?php endforeach; ?>
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
      </div>
    </section>
  </main>
  <script src="js/dashboard.js" defer></script>
</body>
</html>
