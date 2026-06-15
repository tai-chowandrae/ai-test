<?php

function EscapeTripOverviewValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

function FormatTripOverviewDate(string $Value): string
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

function FormatTripOverviewDistance(float $Value): string
{
    return number_format($Value, 2, ',', '.');
}

function RenderTripOverviewGroups(array $TripRegistrations, array $Locations): string
{
    $GroupedTripRegistrations = [];

    foreach ($TripRegistrations as $TripRegistration) {
        $GroupedTripRegistrations[(string)$TripRegistration['TripDate']][] = $TripRegistration;
    }

    ob_start();

    foreach ($GroupedTripRegistrations as $TripDate => $TripsForDate) {
        $DayTotal = 0.0;

        foreach ($TripsForDate as $TripForTotal) {
            $DayTotal += (float)$TripForTotal['DistanceKilometers'];
        }
        ?>
        <article class="TripDayGroup" data-trip-date="<?= EscapeTripOverviewValue((string)$TripDate) ?>">
          <header class="TripDayHeader">
            <strong><?= EscapeTripOverviewValue(FormatTripOverviewDate((string)$TripDate)) ?></strong>
            <span><?= EscapeTripOverviewValue(FormatTripOverviewDistance($DayTotal)) ?> km</span>
          </header>
          <?php foreach ($TripsForDate as $TripRegistration): ?>
            <?= RenderTripOverviewRegistration($TripRegistration, $Locations) ?>
          <?php endforeach; ?>
        </article>
        <?php
    }

    return (string)ob_get_clean();
}

function RenderTripOverviewRegistration(array $TripRegistration, array $Locations): string
{
    ob_start();
    ?>
    <div class="TripRow">
      <span class="TripSummary">
        <span class="TripTitleLine">
          <strong><?= EscapeTripOverviewValue((string)$TripRegistration['StartLocationName']) ?> naar <?= EscapeTripOverviewValue((string)$TripRegistration['EndLocationName']) ?><?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' v.v.' : '' ?><?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? ' - woon-werk' : '' ?></strong>
          <button class="TripEditToggle" type="button" aria-expanded="false">Bewerken</button>
        </span>
        <small><?= EscapeTripOverviewValue(FormatTripOverviewDistance((float)$TripRegistration['DistanceKilometers'])) ?> km</small>
        <?php if (!empty($TripRegistration['TripDescription'])): ?>
          <small class="TripDescriptionText"><?= EscapeTripOverviewValue((string)$TripRegistration['TripDescription']) ?></small>
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
          <input name="TripDate" type="date" value="<?= EscapeTripOverviewValue((string)$TripRegistration['TripDate']) ?>" required>
        </label>

        <label>
          <span>Startlocatie</span>
          <select name="StartLocationId" required>
            <?php foreach ($Locations as $Location): ?>
              <?php if ((int)$Location['IsActive'] === 1 || (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId']): ?>
                <option value="<?= (int)$Location['LocationId'] ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['StartLocationId'] ? ' selected' : '' ?>><?= EscapeTripOverviewValue((string)$Location['Name']) ?><?= (int)$Location['IsActive'] === 1 ? '' : ' (niet actief)' ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          <span>Eindlocatie</span>
          <select name="EndLocationId" data-description-target="#TripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" required>
            <?php foreach ($Locations as $Location): ?>
              <?php if ((int)$Location['IsActive'] === 1 || (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId']): ?>
                <option value="<?= (int)$Location['LocationId'] ?>" data-default-trip-description="<?= EscapeTripOverviewValue((string)($Location['DefaultTripDescription'] ?? '')) ?>"<?= (int)$Location['LocationId'] === (int)$TripRegistration['EndLocationId'] ? ' selected' : '' ?>><?= EscapeTripOverviewValue((string)$Location['Name']) ?><?= (int)$Location['IsActive'] === 1 ? '' : ' (niet actief)' ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          <span>Toelichting</span>
          <textarea name="TripDescription" id="TripDescription-<?= (int)$TripRegistration['TripRegistrationId'] ?>" rows="4"><?= EscapeTripOverviewValue((string)($TripRegistration['TripDescription'] ?? '')) ?></textarea>
        </label>

        <label class="CheckboxLabel">
          <input name="IsRoundTrip" type="checkbox" value="1"<?= (int)$TripRegistration['IsRoundTrip'] === 1 ? ' checked' : '' ?>>
          <span>Heen en weer</span>
        </label>

        <label class="CheckboxLabel">
          <input name="ApplyCommuteCompensation" type="checkbox" value="1"<?= (int)$TripRegistration['ApplyCommuteCompensation'] === 1 ? ' checked' : '' ?>>
          <span>Woon-werkcompensatie (-72 km)</span>
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
    <?php

    return (string)ob_get_clean();
}
