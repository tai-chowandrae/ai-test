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
$ExportRows = [];
$AdminError = '';
$AdminMessage = $_SESSION['AdminMessage'] ?? null;
$ExportStartDate = (string)($_GET['ExportStartDate'] ?? date('Y-m-01'));
$ExportEndDate = (string)($_GET['ExportEndDate'] ?? date('Y-m-d'));
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
        'SELECT LocationId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, CreatedAt
         FROM Locations
         ORDER BY Name ASC'
    );
    $Locations = $LocationsStatement->fetchAll();

    $TripsStatement = $DatabaseConnection->query(
        'SELECT TripRegistrations.TripRegistrationId, TripRegistrations.TripDate,
                TripRegistrations.StartLocationId, TripRegistrations.EndLocationId,
                TripRegistrations.DistanceKilometers, TripRegistrations.IsRoundTrip,
                TripRegistrations.TripDescription, TripRegistrations.CreatedAt,
                Users.FirstName, Users.LastName, Users.EmailAddress,
                StartLocations.Name AS StartLocationName,
                EndLocations.Name AS EndLocationName
         FROM TripRegistrations
         INNER JOIN Users ON Users.UserId = TripRegistrations.UserId
         INNER JOIN Locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
         INNER JOIN Locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
         ORDER BY TripRegistrations.TripDate DESC, TripRegistrations.TripRegistrationId DESC'
    );
    $TripRegistrations = $TripsStatement->fetchAll();

    foreach ($TripRegistrations as $TripRegistration) {
        $TripDate = (string)$TripRegistration['TripDate'];
        $TripMonth = substr($TripDate, 0, 7);
        $GroupedTripRegistrations[$TripDate][] = $TripRegistration;
        $GroupedTripRegistrationsByMonth[$TripMonth][$TripDate][] = $TripRegistration;
    }

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
$UserCount = count($Users);
$LocationCount = count($Locations);
$TripCount = count($TripRegistrations);
$LatestUser = $Users[0] ?? null;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin | Skills2Work</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="AdminShell">
    <header class="AdminTopbar">
      <a class="AdminLogo" href="/admin">SKILLS<span>2</span>WORK <small>Beheer</small></a>
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
          <?php if ($TripRegistrations): ?>
            <?php foreach ($TripRegistrations as $TripRegistration): ?>
              <?php
                $FullName = trim((string)$TripRegistration['FirstName'] . ' ' . (string)$TripRegistration['LastName']);
                $RouteName = (string)$TripRegistration['StartLocationName'] . ' naar ' . (string)$TripRegistration['EndLocationName'];
                $SearchValue = strtolower($FullName . ' ' . (string)$TripRegistration['EmailAddress'] . ' ' . $RouteName . ' ' . (string)$TripRegistration['TripDescription']);
              ?>
              <button class="SidebarCard" type="button" data-filter-item="Trips" data-search-value="<?= EscapeValue($SearchValue) ?>">
                <span class="SidebarDot"></span>
                <span>
                  <strong><?= EscapeValue($RouteName) ?></strong>
                  <small><?= EscapeValue($FullName) ?> - <?= EscapeValue(FormatDateValue((string)$TripRegistration['TripDate'])) ?></small>
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
              <h1 id="AdminTitle">Welkom<?= $FirstName !== '' ? ', ' . EscapeValue($FirstName) : '' ?></h1>
              <p>Kies links wat je wilt beheren: gebruikers, locaties of export.</p>
            </div>
            <div class="LiveBadge"><span></span>Live</div>
          </div>

          <div class="Chips">
            <span class="Chip"><strong><?= $UserCount ?></strong> accounts</span>
            <span class="Chip"><strong><?= $LocationCount ?></strong> locaties</span>
            <span class="Chip"><strong><?= $TripCount ?></strong> ritten</span>
            <span class="Chip">Nieuwste: <strong><?= $LatestUser ? EscapeValue((string)$LatestUser['FirstName']) : '-' ?></strong></span>
          </div>
        </section>

        <?php if (is_array($AdminMessage)): ?>
          <section class="AlertCard <?= $AdminMessage['Type'] === 'Success' ? 'IsSuccess' : '' ?>" role="alert">
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
                <?php
                  $SearchValue = strtolower((string)$Location['Name'] . ' ' . (string)$Location['FormattedAddress'] . ' ' . (string)($Location['DefaultTripDescription'] ?? ''));
                ?>
                <article class="RowCard LocationRow" data-filter-item="Locations" data-search-value="<?= EscapeValue($SearchValue) ?>">
                <span class="UserAvatar">L</span>
                <div class="RowBody">
                  <strong><?= EscapeValue((string)$Location['Name']) ?></strong>
                  <small><?= EscapeValue((string)$Location['FormattedAddress']) ?></small>
                </div>
                <span class="RowMeta">#<?= (int)$Location['LocationId'] ?></span>
                <div class="RowActions">
                  <form class="InlineEditForm" action="/api/index.php" method="post">
                    <input type="hidden" name="Action" value="UpdateLocationName">
                    <input type="hidden" name="LocationId" value="<?= (int)$Location['LocationId'] ?>">
                    <textarea class="InlineEditTextarea IsNameTextarea" name="Name" rows="2" required><?= EscapeValue((string)$Location['Name']) ?></textarea>
                    <textarea class="InlineEditTextarea" name="DefaultTripDescription" rows="2" placeholder="Standaard toelichting"><?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?></textarea>
                    <button class="SmallActionButton" type="submit">Opslaan</button>
                  </form>
                  <form action="/api/index.php" method="post" data-confirm="Weet je zeker dat je deze locatie wilt verwijderen?">
                    <input type="hidden" name="Action" value="DeleteLocation">
                    <input type="hidden" name="LocationId" value="<?= (int)$Location['LocationId'] ?>">
                    <button class="SmallActionButton IsDanger" type="submit">Verwijderen</button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="InnerEmpty">Nog geen locaties opgeslagen.</div>
          <?php endif; ?>
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
            <small><?= $TripCount ?> totaal</small>
          </div>

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
                        $SearchValue = strtolower($FullName . ' ' . (string)$TripRegistration['EmailAddress'] . ' ' . $RouteName . ' ' . (string)$TripRegistration['TripDescription'] . ' ' . FormatDateValue((string)$TripRegistration['TripDate']) . ' ' . $TripMonth);
                      ?>
                      <article class="RowCard TripAdminRow" data-filter-item="Trips" data-search-value="<?= EscapeValue($SearchValue) ?>">
                        <span class="UserAvatar"><?= (int)$TripIndex + 1 ?></span>
                        <div class="RowBody">
                          <strong><?= EscapeValue($RouteName) ?><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' v.v.' : '' ?></strong>
                          <small><?= EscapeValue($FullName) ?> - <?= EscapeValue((string)$TripRegistration['EmailAddress']) ?></small>
                          <?php if (!empty($TripRegistration['TripDescription'])): ?>
                            <small><?= EscapeValue((string)$TripRegistration['TripDescription']) ?></small>
                          <?php endif; ?>
                        </div>
                        <div class="TripAdminActions">
                          <span class="RoleBadge DistanceBadge"><?= EscapeValue(FormatExportDistance((float)$TripRegistration['DistanceKilometers'])) ?> km</span>
                          <span class="RoleBadge ReturnBadge<?= (int)$TripRegistration['IsRoundTrip'] === 1 ? '' : ' IsEmpty' ?>"><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? 'Retour' : '' ?></span>
                          <button class="SmallActionButton IsOutline AdminTripEditToggle" type="button" aria-expanded="false">Bewerken</button>
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
                                <option value="<?= (int)$Location['LocationId'] ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </label>

                          <label>
                            <span class="FormLabel">Eindlocatie</span>
                            <select class="FormInput" name="EndLocationId" data-description-target="#AdminTripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" required>
                              <?php foreach ($Locations as $Location): ?>
                                <option value="<?= (int)$Location['LocationId'] ?>" data-default-trip-description="<?= EscapeValue((string)($Location['DefaultTripDescription'] ?? '')) ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId'] ? ' selected' : '' ?>><?= EscapeValue((string)$Location['Name']) ?></option>
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
  <script src="js/admin.js" defer></script>
</body>
</html>
