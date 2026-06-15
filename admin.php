<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/GoogleMaps.php';

// Redirect guests to the login page before rendering protected admin content.
if (empty($_SESSION['UserId'])) {
    header('Location: /login', true, 302);
    exit;
}

if (empty($_SESSION['IsAdmin'])) {
    header('Location: /dashboard', true, 302);
    exit;
}

function EscapeValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

function FormatDateTimeValue(string $Value): string
{
    $DateTime = DateTime::createFromFormat('Y-m-d H:i:s', $Value);

    return $DateTime ? $DateTime->format('d-m-Y H:i') : $Value;
}

function FormatDateValue(string $Value): string
{
    $DateTime = DateTime::createFromFormat('Y-m-d', $Value);

    return $DateTime ? $DateTime->format('d-m-Y') : $Value;
}

function FormatMonthValue(string $Value): string
{
    $DateTime = DateTime::createFromFormat('Y-m', $Value);
    $MonthNames = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    if (!$DateTime) {
        return $Value;
    }

    return $MonthNames[(int)$DateTime->format('n')] . ' ' . $DateTime->format('Y');
}

function FormatDayValue(string $Value): string
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

    if (!$DateTime) {
        return $Value;
    }

    return $DayNames[(int)$DateTime->format('N')] . ' ' . $DateTime->format('d-m-Y');
}

function FormatExportDistance(float $Value): string
{
    return number_format($Value, 2, ',', '');
}

function NormalizeExportCell(?string $Value): string
{
    return trim(str_replace(["\t", "\r", "\n"], ' ', (string)$Value));
}

$Users = [];
$Locations = [];
$TripRegistrations = [];
$GroupedTripRegistrations = [];
$GroupedTripRegistrationsByMonth = [];
$TripLocations = [];
$ExportRows = [];
$AllTripCount = 0;
$AllTripKilometers = 0.0;
$MonthlyTripTotals = [];
$AdminError = '';
$AdminMessage = $_SESSION['AdminMessage'] ?? null;
$AdminMessageView = is_array($AdminMessage) ? (string)($AdminMessage['View'] ?? 'Welcome') : 'Welcome';
$ExportStartDate = (string)($_GET['ExportStartDate'] ?? date('Y-m-01'));
$ExportEndDate = (string)($_GET['ExportEndDate'] ?? date('Y-m-d'));
$HasSubmittedTripFilter = array_key_exists('TripStartDate', $_GET) || array_key_exists('TripEndDate', $_GET);
$ShowAllTrips = (string)($_GET['ShowAllTrips'] ?? '') === '1';

if ($ShowAllTrips) {
    $_SESSION['AdminTripFilter'] = [
        'StartDate' => '',
        'EndDate' => '',
    ];
} elseif ($HasSubmittedTripFilter) {
    $_SESSION['AdminTripFilter'] = [
        'StartDate' => (string)($_GET['TripStartDate'] ?? ''),
        'EndDate' => (string)($_GET['TripEndDate'] ?? ''),
    ];
} elseif (empty($_SESSION['AdminTripFilter']) || !is_array($_SESSION['AdminTripFilter'])) {
    $_SESSION['AdminTripFilter'] = [
        'StartDate' => date('Y-m-d', strtotime('-3 months')),
        'EndDate' => date('Y-m-d'),
    ];
}

$TripFilterStartDate = (string)($_SESSION['AdminTripFilter']['StartDate'] ?? '');
$TripFilterEndDate = (string)($_SESSION['AdminTripFilter']['EndDate'] ?? '');
$ExportContent = "DATUM\tVAN\tNAAR\tENKELE AFSTAND\tREISTIJD\tAFSTAND\tRETOUR\tToelichting";

unset($_SESSION['AdminMessage']);

try {
    $DatabaseConnection = GetDatabaseConnection();
    $UsersStatement = $DatabaseConnection->query(
        'SELECT UserId, FirstName, LastName, EmailAddress, IsAdmin, CreatedAt
         FROM Users
         ORDER BY CreatedAt DESC, UserId DESC'
    );
    $Users = $UsersStatement->fetchAll();

    $LocationsStatement = $DatabaseConnection->query(
        'SELECT LocationId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, IsActive, CreatedAt
         FROM Locations
         ORDER BY Name ASC'
    );
    $Locations = $LocationsStatement->fetchAll();

    $TripTotalsStatement = $DatabaseConnection->query(
        'SELECT COUNT(*) AS TripCount, COALESCE(SUM(DistanceKilometers), 0) AS TotalKilometers
         FROM TripRegistrations'
    );
    $TripTotals = $TripTotalsStatement->fetch();
    $AllTripCount = (int)($TripTotals['TripCount'] ?? 0);
    $AllTripKilometers = (float)($TripTotals['TotalKilometers'] ?? 0);

    $MonthlyTripTotalsStatement = $DatabaseConnection->query(
        'SELECT DATE_FORMAT(TripDate, "%Y-%m") AS TripMonth,
                COUNT(*) AS TripCount,
                COALESCE(SUM(DistanceKilometers), 0) AS TotalKilometers
         FROM TripRegistrations
         GROUP BY DATE_FORMAT(TripDate, "%Y-%m")
         ORDER BY TripMonth DESC'
    );
    $MonthlyTripTotals = $MonthlyTripTotalsStatement->fetchAll();

    $TripFilterConditions = [];
    $TripFilterParameters = [];

    if ($TripFilterStartDate !== '') {
        $TripFilterConditions[] = 'TripRegistrations.TripDate >= :TripFilterStartDate';
        $TripFilterParameters['TripFilterStartDate'] = $TripFilterStartDate;
    }

    if ($TripFilterEndDate !== '') {
        $TripFilterConditions[] = 'TripRegistrations.TripDate <= :TripFilterEndDate';
        $TripFilterParameters['TripFilterEndDate'] = $TripFilterEndDate;
    }

    $TripFilterWhereClause = $TripFilterConditions ? ' WHERE ' . implode(' AND ', $TripFilterConditions) : '';

    $TripsStatement = $DatabaseConnection->prepare(
        'SELECT TripRegistrations.TripRegistrationId, TripRegistrations.TripDate,
                TripRegistrations.StartLocationId, TripRegistrations.EndLocationId,
                TripRegistrations.DistanceKilometers, TripRegistrations.IsRoundTrip,
                TripRegistrations.ApplyCommuteCompensation,
                TripRegistrations.TripDescription, TripRegistrations.CreatedAt,
                Users.FirstName, Users.LastName, Users.EmailAddress,
                StartLocations.Name AS StartLocationName,
                EndLocations.Name AS EndLocationName
         FROM TripRegistrations
         INNER JOIN Users ON Users.UserId = TripRegistrations.UserId
         INNER JOIN Locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
         INNER JOIN Locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
         ' . $TripFilterWhereClause . '
         ORDER BY TripRegistrations.TripDate DESC, TripRegistrations.TripRegistrationId DESC'
    );
    $TripsStatement->execute($TripFilterParameters);
    $TripRegistrations = $TripsStatement->fetchAll();

    foreach ($TripRegistrations as $TripRegistration) {
        $TripDate = (string)$TripRegistration['TripDate'];
        $TripMonth = substr($TripDate, 0, 7);
        $GroupedTripRegistrations[$TripDate][] = $TripRegistration;
        $GroupedTripRegistrationsByMonth[$TripMonth][$TripDate][] = $TripRegistration;

        $TripLocationIds = [
            (int)$TripRegistration['StartLocationId'] => (string)$TripRegistration['StartLocationName'],
            (int)$TripRegistration['EndLocationId'] => (string)$TripRegistration['EndLocationName'],
        ];

        foreach ($TripLocationIds as $LocationId => $LocationName) {
            if (!isset($TripLocations[$LocationId])) {
                $TripLocations[$LocationId] = [
                    'Name' => $LocationName,
                    'TripCount' => 0,
                ];
            }

            $TripLocations[$LocationId]['TripCount']++;
        }
    }

    uasort($TripLocations, function (array $FirstLocation, array $SecondLocation): int {
        return strcasecmp((string)$FirstLocation['Name'], (string)$SecondLocation['Name']);
    });

    $ExportStatement = $DatabaseConnection->prepare(
        'SELECT TripRegistrations.TripDate, TripRegistrations.DistanceKilometers,
                TripRegistrations.IsRoundTrip, TripRegistrations.TripDescription,
                StartLocations.FormattedAddress AS StartLocationName,
                EndLocations.FormattedAddress AS EndLocationName
         FROM TripRegistrations
         INNER JOIN Locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
         INNER JOIN Locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
         WHERE TripRegistrations.TripDate BETWEEN :ExportStartDate AND :ExportEndDate
         ORDER BY TripRegistrations.TripDate ASC, TripRegistrations.TripRegistrationId ASC'
    );
    $ExportStatement->execute([
        'ExportStartDate' => $ExportStartDate,
        'ExportEndDate' => $ExportEndDate,
    ]);
    $ExportRows = $ExportStatement->fetchAll();

    foreach ($ExportRows as $ExportRow) {
        $ExportContent .= "\n"
            . NormalizeExportCell(FormatDateValue((string)$ExportRow['TripDate'])) . "\t"
            . NormalizeExportCell((string)$ExportRow['StartLocationName']) . "\t"
            . NormalizeExportCell((string)$ExportRow['EndLocationName']) . "\t"
            . "\t"
            . "\t"
            . NormalizeExportCell(FormatExportDistance((float)$ExportRow['DistanceKilometers'])) . "\t"
            . ((int)$ExportRow['IsRoundTrip'] === 1 ? 'Ja' : 'Nee') . "\t"
            . NormalizeExportCell($ExportRow['TripDescription'] ?? '');
    }
} catch (PDOException $Exception) {
    $AdminError = 'De beheergegevens konden niet worden geladen door een databasefout.';
}

$FirstName = (string)($_SESSION['FirstName'] ?? '');
$FullName = trim($FirstName . ' ' . (string)($_SESSION['LastName'] ?? ''));
$UserCount = count($Users);
$LocationCount = count($Locations);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin | KM2WORK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="AdminShell">
    <header class="AdminTopbar">
      <a class="AdminLogo" href="/admin">KM<span>2</span>WORK <small>Beheer</small></a>
      <div class="TopbarRight">
        <a class="TopbarButton" href="/dashboard">Dashboard</a>
        <form action="/api/index.php" method="post">
          <input type="hidden" name="Action" value="Logout">
          <button class="TopbarButton IsDanger" type="submit">Uitloggen</button>
        </form>
      </div>
    </header>

    <div class="AdminBody">
      <nav class="IconSidebar" aria-label="Admin hoofdmenu">
        <button class="SidebarIconButton" type="button" title="Gebruikers" data-admin-view="Users">
          <span class="IconGlyph">U</span>
          <span class="IconLabel">Users</span>
        </button>
        <button class="SidebarIconButton" type="button" title="Locaties" data-admin-view="Locations">
          <span class="IconGlyph">L</span>
          <span class="IconLabel">Locaties</span>
        </button>
        <button class="SidebarIconButton" type="button" title="Ritten" data-admin-view="Trips">
          <span class="IconGlyph">R</span>
          <span class="IconLabel">Ritten</span>
        </button>
        <button class="SidebarIconButton" type="button" title="Export" data-admin-view="Export">
          <span class="IconGlyph">E</span>
          <span class="IconLabel">Export</span>
        </button>
      </nav>

      <aside class="SubSidebar AdminViewSection" data-admin-section="Users" aria-label="Gebruikers">
        <div class="SectionLabel">Gebruikers</div>
        <input class="SidebarSearch" id="UserSearch" type="search" placeholder="Naam of e-mail zoeken..." autocomplete="off" data-filter-input="Users">

        <div class="SidebarList" id="SidebarUserList">
          <?php if ($Users): ?>
            <?php foreach ($Users as $User): ?>
              <?php
                $FullName = trim((string)$User['FirstName'] . ' ' . (string)$User['LastName']);
                $SearchValue = strtolower($FullName . ' ' . (string)$User['EmailAddress']);
              ?>
              <button class="SidebarCard" type="button" data-filter-item="Users" data-search-value="<?= EscapeValue($SearchValue) ?>">
                <span class="SidebarDot"></span>
                <span>
                  <strong><?= EscapeValue($FullName) ?></strong>
                  <small><?= EscapeValue((string)$User['EmailAddress']) ?><?= (int)$User['IsAdmin'] === 1 ? ' - Admin' : '' ?></small>
                </span>
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="SidebarEmpty">Geen gebruikers</div>
          <?php endif; ?>
        </div>
      </aside>

      <aside class="SubSidebar AdminViewSection" data-admin-section="Locations" aria-label="Locaties">
        <div class="SectionLabel">Locaties</div>
        <input class="SidebarSearch" id="LocationSearch" type="search" placeholder="Locatie zoeken..." autocomplete="off" data-filter-input="Locations">

        <div class="SidebarList" id="SidebarLocationList">
          <?php if ($Locations): ?>
            <?php foreach ($Locations as $Location): ?>
              <?php
                $SearchValue = strtolower((string)$Location['Name'] . ' ' . (string)$Location['FormattedAddress'] . ' ' . (string)($Location['DefaultTripDescription'] ?? ''));
              ?>
              <button class="SidebarCard" type="button" data-filter-item="Locations" data-search-value="<?= EscapeValue($SearchValue) ?>">
                <span class="SidebarDot"></span>
                <span>
                  <strong><?= EscapeValue((string)$Location['Name']) ?></strong>
                  <small><?= EscapeValue((string)$Location['FormattedAddress']) ?></small>
                </span>
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="SidebarEmpty">Geen locaties</div>
          <?php endif; ?>
        </div>
      </aside>

      <aside class="SubSidebar AdminViewSection" data-admin-section="Trips" aria-label="Ritten">
        <div class="SectionLabel">Ritten</div>
        <input class="SidebarSearch" id="TripSearch" type="search" placeholder="Rit zoeken..." autocomplete="off" data-filter-input="Trips">

        <div class="SidebarList" id="SidebarTripList">
          <?php if ($TripLocations): ?>
            <?php foreach ($TripLocations as $TripLocation): ?>
              <?php
                $TripLocationName = (string)$TripLocation['Name'];
                $SearchValue = strtolower($TripLocationName);
              ?>
              <button class="SidebarCard" type="button" data-filter-item="Trips" data-search-value="<?= EscapeValue($SearchValue) ?>" data-filter-apply="Trips" data-filter-value="<?= EscapeValue($TripLocationName) ?>">
                <span class="SidebarDot"></span>
                <span>
                  <strong><?= EscapeValue($TripLocationName) ?></strong>
                  <small><?= (int)$TripLocation['TripCount'] ?> ritten</small>
                </span>
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="SidebarEmpty">Geen ritten</div>
          <?php endif; ?>
        </div>
      </aside>

      <main class="MainContent" aria-label="Admin beheer">
        <section class="InfoCard AdminViewSection IsActive" data-admin-section="Welcome">
          <div class="InfoHeader">
            <div>
              <p class="SectionLabel">Beheersomgeving</p>
              <h1 id="AdminTitle">Welkom<?= $FullName !== '' ? ', ' . EscapeValue($FullName) : '' ?></h1>
              <p>Kies links wat je wilt beheren: gebruikers, locaties of export.</p>
            </div>
            <div class="LiveBadge"><span></span>Live</div>
          </div>

          <div class="Chips">
            <span class="Chip"><strong><?= $UserCount ?></strong> accounts</span>
            <span class="Chip"><strong><?= $LocationCount ?></strong> locaties</span>
            <span class="Chip"><strong><?= $AllTripCount ?></strong> ritten</span>
            <span class="Chip"><strong><?= EscapeValue(FormatExportDistance($AllTripKilometers)) ?></strong> km totaal</span>
          </div>

          <?php if ($MonthlyTripTotals): ?>
            <section class="MonthlyTotals" aria-label="Totalen per kalendermaand">
              <div class="MonthlyTotalsHeader">
                <span>Kalendermaanden</span>
                <small><?= count($MonthlyTripTotals) ?> maanden</small>
              </div>
              <div class="MonthlyTotalsGrid">
                <?php foreach ($MonthlyTripTotals as $MonthlyTripTotal): ?>
                  <?php
                    $MonthlyTripDate = DateTime::createFromFormat('Y-m-d', (string)$MonthlyTripTotal['TripMonth'] . '-01');
                    $MonthlyTripStartDate = $MonthlyTripDate ? $MonthlyTripDate->format('Y-m-01') : (string)$MonthlyTripTotal['TripMonth'] . '-01';
                    $MonthlyTripEndDate = $MonthlyTripDate ? $MonthlyTripDate->format('Y-m-t') : $MonthlyTripStartDate;
                    $MonthlyTripFilterUrl = '/admin?TripStartDate=' . rawurlencode($MonthlyTripStartDate) . '&TripEndDate=' . rawurlencode($MonthlyTripEndDate) . '#AdminTrips';
                  ?>
                  <article class="MonthlyTotalItem">
                    <a href="<?= EscapeValue($MonthlyTripFilterUrl) ?>"><?= EscapeValue(FormatMonthValue((string)$MonthlyTripTotal['TripMonth'])) ?></a>
                    <span><?= (int)$MonthlyTripTotal['TripCount'] ?> ritten</span>
                    <span><?= EscapeValue(FormatExportDistance((float)$MonthlyTripTotal['TotalKilometers'])) ?> km</span>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
        </section>

        <?php if (is_array($AdminMessage)): ?>
          <section class="AlertCard AdminViewSection <?= $AdminMessage['Type'] === 'Success' ? 'IsSuccess' : '' ?>" data-admin-section="<?= EscapeValue($AdminMessageView) ?>" data-auto-dismiss role="alert">
            <?= EscapeValue((string)$AdminMessage['Message']) ?>
          </section>
        <?php endif; ?>

        <?php if ($AdminError !== ''): ?>
          <section class="AlertCard" role="alert"><?= EscapeValue($AdminError) ?></section>
        <?php endif; ?>

        <section class="ContentPanel AdminViewSection" id="ExportPanel" data-admin-section="Export">
          <div class="PanelTitle">
            <span>Export</span>
            <small><?= count($ExportRows) ?> ritten</small>
          </div>

          <form class="ExportForm" method="get" action="/admin#ExportPanel">
            <label>
              <span class="FormLabel">Van datum</span>
              <input class="FormInput" name="ExportStartDate" type="date" value="<?= EscapeValue($ExportStartDate) ?>" required>
            </label>
            <label>
              <span class="FormLabel">Tot datum</span>
              <input class="FormInput" name="ExportEndDate" type="date" value="<?= EscapeValue($ExportEndDate) ?>" required>
            </label>
            <button class="PrimaryAdminButton" type="submit">Export maken</button>
          </form>

          <textarea class="ExportTextarea" id="ExportTextarea" readonly rows="10"><?= EscapeValue($ExportContent) ?></textarea>
          <button class="SmallActionButton ExportCopyButton" id="ExportCopyButton" type="button">Kopieren</button>
        </section>

        <section class="ContentPanel AdminViewSection" data-admin-section="Locations">
          <div class="PanelTitle">
            <span>Locatie toevoegen</span>
            <small>Google Maps</small>
          </div>

          <?php if (GoogleMapsApiKey === ''): ?>
            <div class="InlineHint">Vul eerst `GoogleMapsApiKey` in [config/GoogleMaps.php] in om Google Maps zoeken te gebruiken.</div>
          <?php endif; ?>

          <form class="AdminForm" id="LocationForm" action="/api/index.php" method="post">
            <input type="hidden" name="Action" value="CreateLocation">
            <input type="hidden" id="GooglePlaceId" name="GooglePlaceId">
            <input type="hidden" id="FormattedAddress" name="FormattedAddress">
            <input type="hidden" id="Latitude" name="Latitude">
            <input type="hidden" id="Longitude" name="Longitude">

            <label>
              <span class="FormLabel">Naam</span>
              <input class="FormInput" name="Name" type="text" placeholder="Bijv. Kantoor Amsterdam" required>
            </label>

            <label>
              <span class="FormLabel">Google Maps locatie</span>
              <input class="FormInput" id="GoogleLocationSearch" type="text" placeholder="Zoek adres of locatie..." autocomplete="off" required>
              <small class="FormHint">Typ minimaal 5 tekens om locaties te zoeken.</small>
              <div class="LocationSuggestions" id="LocationSuggestions"></div>
            </label>

            <label class="AdminFormWide">
              <span class="FormLabel">Standaard toelichting</span>
              <textarea class="FormTextarea" name="DefaultTripDescription" rows="3" placeholder="Bijv. Klantbezoek, overleg of vaste projectrit"></textarea>
            </label>

            <button class="PrimaryAdminButton" type="submit">Locatie opslaan</button>
          </form>
        </section>

        <section class="ContentPanel AdminViewSection" data-admin-section="Locations">
          <div class="PanelTitle">
            <span>Locaties</span>
            <small><?= $LocationCount ?> totaal</small>
          </div>

          <?php if ($Locations): ?>
            <?php foreach ($Locations as $Location): ?>
              <?php if ((int)$Location['IsActive'] !== 1) { continue; } ?>
                <?php
                  $LocationName = (string)$Location['Name'];
                  $LocationInitial = strtoupper(substr(trim($LocationName), 0, 1));
                  $SearchValue = strtolower($LocationName . ' ' . (string)$Location['FormattedAddress'] . ' ' . (string)($Location['DefaultTripDescription'] ?? ''));
                ?>
                <article class="RowCard LocationRow" data-filter-item="Locations" data-search-value="<?= EscapeValue($SearchValue) ?>">
                <span class="UserAvatar<?= (int)$Location['IsActive'] === 1 ? '' : ' IsInactive' ?>"><?= EscapeValue($LocationInitial !== '' ? $LocationInitial : 'L') ?></span>
                <div class="RowBody">
                  <strong><?= EscapeValue($LocationName) ?></strong>
                  <small><?= EscapeValue((string)$Location['FormattedAddress']) ?></small>
                </div>
                <span class="RoleBadge<?= (int)$Location['IsActive'] === 1 ? ' IsAdmin' : '' ?>"><?= (int)$Location['IsActive'] === 1 ? 'Actief' : 'Niet actief' ?></span>
                <div class="RowActions">
                  <form class="InlineEditForm" action="/api/index.php" method="post">
                    <input type="hidden" name="Action" value="UpdateLocationName">
                    <input type="hidden" name="LocationId" value="<?= (int)$Location['LocationId'] ?>">
                    <textarea class="InlineEditTextarea IsNameTextarea" name="Name" rows="2" required><?= EscapeValue((string)$Location['Name']) ?></textarea>
                    <textarea class="InlineEditTextarea" name="DefaultTripDescription" rows="2" placeholder="Standaard toelichting"><?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?></textarea>
                    <button class="SmallActionButton IsOutline" type="submit">Opslaan</button>
                  </form>
                  <form class="LocationDeleteForm" action="/api/index.php" method="post" data-confirm="Weet je zeker dat je deze locatie wilt verwijderen?">
                    <input type="hidden" name="Action" value="DeleteLocation">
                    <input type="hidden" name="LocationId" value="<?= (int)$Location['LocationId'] ?>">
                    <button class="SmallActionButton IsOutline IconActionButton" type="submit" aria-label="Locatie verwijderen" title="Locatie verwijderen">
                      <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-.7 11H7.7L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"></path>
                      </svg>
                    </button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="InnerEmpty">Nog geen locaties opgeslagen.</div>
          <?php endif; ?>
        </section>

        <section class="ContentPanel AdminViewSection" data-admin-section="Locations">
          <div class="PanelTitle">
            <span>Locaties kiezen</span>
            <small>Actief / niet actief</small>
          </div>

          <form class="LocationVisibilityForm" action="/api/index.php" method="post">
            <input type="hidden" name="Action" value="UpdateLocationVisibility">

            <div class="LocationDualList">
              <section class="LocationDualColumn" aria-label="Actieve locaties">
                <header>
                  <strong>Actief</strong>
                  <small>Beschikbaar in ritformulieren</small>
                </header>
                <div class="LocationDualListBox" data-location-list="active">
                  <?php foreach ($Locations as $Location): ?>
                    <?php if ((int)$Location['IsActive'] === 1): ?>
                      <?php
                        $LocationName = (string)$Location['Name'];
                        $SearchValue = strtolower($LocationName . ' ' . (string)$Location['FormattedAddress'] . ' ' . (string)($Location['DefaultTripDescription'] ?? ''));
                      ?>
                      <button class="LocationDualItem" type="button" data-location-option data-filter-item="Locations" data-search-value="<?= EscapeValue($SearchValue) ?>">
                        <input type="hidden" name="ActiveLocationIds[]" value="<?= (int)$Location['LocationId'] ?>">
                        <span><?= EscapeValue($LocationName) ?></span>
                        <small><?= EscapeValue((string)$Location['FormattedAddress']) ?></small>
                      </button>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </section>

              <div class="LocationDualActions" aria-label="Locaties verplaatsen">
                <button class="SmallActionButton IsOutline" type="button" data-location-move="inactive">Naar rechts</button>
                <button class="SmallActionButton IsOutline" type="button" data-location-move="active">Naar links</button>
              </div>

              <section class="LocationDualColumn" aria-label="Niet actieve locaties">
                <header>
                  <strong>Niet actief</strong>
                  <small>Verborgen in ritformulieren</small>
                </header>
                <div class="LocationDualListBox" data-location-list="inactive">
                  <?php foreach ($Locations as $Location): ?>
                    <?php if ((int)$Location['IsActive'] !== 1): ?>
                      <?php
                        $LocationName = (string)$Location['Name'];
                        $SearchValue = strtolower($LocationName . ' ' . (string)$Location['FormattedAddress'] . ' ' . (string)($Location['DefaultTripDescription'] ?? ''));
                      ?>
                      <button class="LocationDualItem" type="button" data-location-option data-filter-item="Locations" data-search-value="<?= EscapeValue($SearchValue) ?>">
                        <input type="hidden" name="ActiveLocationIds[]" value="<?= (int)$Location['LocationId'] ?>" disabled>
                        <span><?= EscapeValue($LocationName) ?></span>
                        <small><?= EscapeValue((string)$Location['FormattedAddress']) ?></small>
                      </button>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </section>
            </div>

            <button class="SmallActionButton IsOutline LocationVisibilitySaveButton" type="submit">Zichtbaarheid opslaan</button>
          </form>
        </section>

        <section class="ContentPanel AdminViewSection" data-admin-section="Users">
          <div class="PanelTitle">
            <span>Gebruikerslijst</span>
            <small><?= $UserCount ?> totaal</small>
          </div>

          <div id="UserRows">
            <?php if ($Users): ?>
              <?php foreach ($Users as $User): ?>
                <?php
                  $FullName = trim((string)$User['FirstName'] . ' ' . (string)$User['LastName']);
                  $Initials = strtoupper(substr((string)$User['FirstName'], 0, 1) . substr((string)$User['LastName'], 0, 1));
                  $SearchValue = strtolower($FullName . ' ' . (string)$User['EmailAddress']);
                ?>
                <article class="RowCard UserRow" data-filter-item="Users" data-search-value="<?= EscapeValue($SearchValue) ?>">
                  <span class="UserAvatar"><?= EscapeValue($Initials ?: '?') ?></span>
                  <div class="RowBody">
                    <strong><?= EscapeValue($FullName) ?></strong>
                    <small><?= EscapeValue((string)$User['EmailAddress']) ?></small>
                  </div>
                  <span class="RoleBadge<?= (int)$User['IsAdmin'] === 1 ? ' IsAdmin' : '' ?>"><?= (int)$User['IsAdmin'] === 1 ? 'Admin' : 'Gebruiker' ?></span>
                  <span class="RowMeta">User #<?= (int)$User['UserId'] ?></span>
                  <span class="RowMeta"><?= EscapeValue(FormatDateTimeValue((string)$User['CreatedAt'])) ?></span>
                </article>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="InnerEmpty">Nog geen gebruikers gevonden.</div>
            <?php endif; ?>
          </div>
        </section>

        <section class="ContentPanel AdminViewSection" data-admin-section="Trips">
          <div class="PanelTitle">
            <span>Rittenlijst</span>
            <small><?= count($TripRegistrations) ?> getoond</small>
          </div>

          <form class="PeriodFilterForm" method="get" action="/admin#AdminTrips">
            <label>
              <span class="FormLabel">Van datum</span>
              <input class="FormInput" name="TripStartDate" type="date" value="<?= EscapeValue($TripFilterStartDate) ?>">
            </label>
            <label>
              <span class="FormLabel">Tot datum</span>
              <input class="FormInput" name="TripEndDate" type="date" value="<?= EscapeValue($TripFilterEndDate) ?>">
            </label>
            <button class="PrimaryAdminButton" type="submit">Filter toepassen</button>
            <?php if ($TripFilterStartDate !== '' || $TripFilterEndDate !== ''): ?>
              <a class="SmallActionButton IsOutline PeriodFilterReset" href="/admin?ShowAllTrips=1#AdminTrips">Reset</a>
            <?php endif; ?>
          </form>

          <?php if ($GroupedTripRegistrationsByMonth): ?>
            <?php foreach ($GroupedTripRegistrationsByMonth as $TripMonth => $TripsForMonth): ?>
              <?php
                $MonthTotal = 0.0;
                foreach ($TripsForMonth as $TripsForMonthDate) {
                    foreach ($TripsForMonthDate as $TripForMonthTotal) {
                        $MonthTotal += (float)$TripForMonthTotal['DistanceKilometers'];
                    }
                }
                ?>
              <section class="TripAdminMonthGroup" data-filter-group="Trips">
                <header class="TripAdminMonthHeader">
                  <strong><?= EscapeValue(FormatMonthValue((string)$TripMonth)) ?></strong>
                  <span><?= EscapeValue(FormatExportDistance($MonthTotal)) ?> km</span>
                </header>

                <?php foreach ($TripsForMonth as $TripDate => $TripsForDate): ?>
                  <?php
                    $DayTotal = 0.0;
                    foreach ($TripsForDate as $TripForTotal) {
                        $DayTotal += (float)$TripForTotal['DistanceKilometers'];
                    }
                  ?>
                  <section class="TripAdminDayGroup" data-filter-group="Trips">
                    <header class="TripAdminDayHeader">
                      <strong><?= EscapeValue(FormatDayValue((string)$TripDate)) ?></strong>
                      <span><?= EscapeValue(FormatExportDistance($DayTotal)) ?> km</span>
                    </header>

                    <?php foreach ($TripsForDate as $TripIndex => $TripRegistration): ?>
                      <?php
                        $FullName = trim((string)$TripRegistration['FirstName'] . ' ' . (string)$TripRegistration['LastName']);
                        $RouteName = (string)$TripRegistration['StartLocationName'] . ' naar ' . (string)$TripRegistration['EndLocationName'];
                        $SearchValue = strtolower($FullName . ' ' . (string)$TripRegistration['EmailAddress'] . ' ' . $RouteName . ' ' . (string)$TripRegistration['TripDescription'] . ' ' . FormatDateValue((string)$TripRegistration['TripDate']) . ' ' . $TripMonth . ((int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? ' woon-werkcompensatie' : ''));
                      ?>
                      <article class="RowCard TripAdminRow" data-filter-item="Trips" data-search-value="<?= EscapeValue($SearchValue) ?>">
                        <span class="UserAvatar"><?= (int)$TripIndex + 1 ?></span>
                        <div class="RowBody">
                          <strong><?= EscapeValue($RouteName) ?><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' v.v.' : '' ?><?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? ' - woon-werk' : '' ?></strong>
                          <small><?= EscapeValue($FullName) ?> - <?= EscapeValue((string)$TripRegistration['EmailAddress']) ?></small>
                          <?php if (!empty($TripRegistration['TripDescription'])): ?>
                            <small><?= EscapeValue((string)$TripRegistration['TripDescription']) ?></small>
                          <?php endif; ?>
                        </div>
                        <div class="TripAdminActions">
                          <span class="RoleBadge DistanceBadge"><?= EscapeValue(FormatExportDistance((float)$TripRegistration['DistanceKilometers'])) ?> km</span>
                          <span class="RoleBadge ReturnBadge<?= (int)$TripRegistration['IsRoundTrip'] === 1 ? '' : ' IsEmpty' ?>"><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? 'Retour' : '' ?></span>
                          <span class="RoleBadge CommuteBadge<?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? '' : ' IsEmpty' ?>"><?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? '-72 km' : '' ?></span>
                          <button class="SmallActionButton IsOutline AdminTripEditToggle" type="button" aria-expanded="false">Bewerken</button>
                          <form class="AdminTripDeleteForm" action="/api/index.php" method="post" data-confirm="Weet je zeker dat je deze rit wilt verwijderen?">
                            <input type="hidden" name="Action" value="DeleteAdminTripRegistration">
                            <input type="hidden" name="TripRegistrationId" value="<?= (int)$TripRegistration['TripRegistrationId'] ?>">
                            <button class="SmallActionButton IsOutline IconActionButton" type="submit" aria-label="Rit verwijderen" title="Rit verwijderen">
                              <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-.7 11H7.7L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"></path>
                              </svg>
                            </button>
                          </form>
                        </div>
                      </article>
                      <div class="AdminTripEditPanel">
                        <form class="AdminTripEditForm" action="/api/index.php" method="post">
                          <input type="hidden" name="Action" value="UpdateAdminTripRegistration">
                          <input type="hidden" name="TripRegistrationId" value="<?= (int)$TripRegistration['TripRegistrationId'] ?>">

                          <label>
                            <span class="FormLabel">Datum</span>
                            <input class="FormInput" name="TripDate" type="date" value="<?= EscapeValue((string)$TripRegistration['TripDate']) ?>" required>
                          </label>

                          <label>
                            <span class="FormLabel">Startlocatie</span>
                            <select class="FormInput" name="StartLocationId" required>
                              <?php foreach ($Locations as $Location): ?>
                                <?php if ((int)$Location['IsActive'] === 1 || (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId']): ?>
                                  <option value="<?= (int)$Location['LocationId'] ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?><?= (int)$Location['IsActive'] === 1 ? '' : ' (niet actief)' ?></option>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </select>
                          </label>

                          <label>
                            <span class="FormLabel">Eindlocatie</span>
                            <select class="FormInput" name="EndLocationId" data-description-target="#AdminTripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" required>
                              <?php foreach ($Locations as $Location): ?>
                                <?php if ((int)$Location['IsActive'] === 1 || (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId']): ?>
                                  <option value="<?= (int)$Location['LocationId'] ?>" data-default-trip-description="<?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?><?= (int)$Location['IsActive'] === 1 ? '' : ' (niet actief)' ?></option>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </select>
                          </label>

                          <label>
                            <span class="FormLabel">Toelichting</span>
                            <textarea class="FormTextarea" name="TripDescription" id="AdminTripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" rows="3"><?= EscapeValue((string)($TripRegistration['TripDescription'] ?? '')) ?></textarea>
                          </label>

                          <label class="AdminCheckboxLabel">
                            <input name="IsRoundTrip" type="checkbox" value="1"<?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' checked' : '' ?>>
                            <span>Heen en weer</span>
                          </label>

                          <label class="AdminCheckboxLabel">
                            <input name="ApplyCommuteCompensation" type="checkbox" value="1"<?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? ' checked' : '' ?>>
                            <span>Woon-werkcompensatie (-72 km)</span>
                          </label>

                          <button class="SmallActionButton" type="submit">Opslaan</button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </section>
                <?php endforeach; ?>
              </section>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="InnerEmpty">Nog geen ritten gevonden.</div>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
  <div class="ConfirmModal" id="ConfirmModal" aria-hidden="true">
    <div class="ConfirmModalBackdrop" data-confirm-cancel></div>
    <section class="ConfirmModalPanel" role="dialog" aria-modal="true" aria-labelledby="ConfirmModalTitle" aria-describedby="ConfirmModalMessage">
      <header class="ConfirmModalHeader">
        <span class="ConfirmModalIcon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-.7 11H7.7L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"></path>
          </svg>
        </span>
        <div>
          <h2 id="ConfirmModalTitle">Verwijderen bevestigen</h2>
          <p id="ConfirmModalMessage">Weet je zeker dat je dit item wilt verwijderen?</p>
        </div>
      </header>
      <div class="ConfirmModalActions">
        <button class="SmallActionButton IsOutline" type="button" data-confirm-cancel>Annuleren</button>
        <button class="SmallActionButton" type="button" id="ConfirmModalSubmit">Verwijderen</button>
      </div>
    </section>
  </div>
  <script src="js/admin.js" defer></script>
</body>
</html>
