<?php
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/GoogleMaps.php';

function RedirectToRegisterWithError(string $Message, array $OldInput = []): void
{
    $_SESSION['RegisterError'] = $Message;
    $_SESSION['RegisterOldInput'] = $OldInput;

    header('Location: /register', true, 302);
    exit;
}

function RedirectToLoginWithSuccess(string $Message): void
{
    $_SESSION['LoginSuccess'] = $Message;

    header('Location: /login', true, 302);
    exit;
}

function RedirectToLoginWithError(string $Message, string $EmailAddress = ''): void
{
    $_SESSION['LoginError'] = $Message;
    $_SESSION['LoginOldInput'] = [
        'EmailAddress' => $EmailAddress,
    ];

    header('Location: /login', true, 302);
    exit;
}

function RedirectToAdminWithMessage(string $Type, string $Message, string $AdminView = ''): void
{
    $_SESSION['AdminMessage'] = [
        'Type' => $Type,
        'Message' => $Message,
        'View' => $AdminView !== '' ? $AdminView : 'Welcome',
    ];

    $Location = $AdminView !== '' ? '/admin#Admin' . $AdminView : '/admin';

    header('Location: ' . $Location, true, 302);
    exit;
}

function RedirectToDashboardWithMessage(string $Type, string $Message): void
{
    $_SESSION['DashboardMessage'] = [
        'Type' => $Type,
        'Message' => $Message,
    ];

    header('Location: /dashboard', true, 302);
    exit;
}

function RedirectToTripsWithMessage(string $Type, string $Message): void
{
    $_SESSION['TripsMessage'] = [
        'Type' => $Type,
        'Message' => $Message,
    ];

    header('Location: /ritten', true, 302);
    exit;
}

function NormalizePostValue(string $Key): string
{
    return trim((string)($_POST[$Key] ?? ''));
}

function SendJsonResponse(array $Data, int $StatusCode = 200): void
{
    http_response_code($StatusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($Data);
    exit;
}

function RequireLogin(): void
{
    if (empty($_SESSION['UserId'])) {
        header('Location: /login', true, 302);
        exit;
    }
}

function RequireAdmin(): void
{
    RequireLogin();

    if (empty($_SESSION['IsAdmin'])) {
        header('Location: /dashboard', true, 302);
        exit;
    }
}

function HandleLoginRequest(): void
{
    $EmailAddress = NormalizePostValue('EmailAddress');
    $Password = (string)($_POST['Password'] ?? '');

    if ($EmailAddress === '' || trim($Password) === '') {
        RedirectToLoginWithError('Vul je e-mailadres en wachtwoord in.', $EmailAddress);
    }

    if (!filter_var($EmailAddress, FILTER_VALIDATE_EMAIL)) {
        RedirectToLoginWithError('Vul een geldig e-mailadres in.', $EmailAddress);
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $UserStatement = $DatabaseConnection->prepare(
            'SELECT UserId, FirstName, LastName, EmailAddress, PasswordHash, IsAdmin
             FROM Users
             WHERE EmailAddress = :EmailAddress
             LIMIT 1'
        );
        $UserStatement->execute(['EmailAddress' => $EmailAddress]);

        $User = $UserStatement->fetch();

        if (!$User || !password_verify($Password, $User['PasswordHash'])) {
            RedirectToLoginWithError('De combinatie van e-mailadres en wachtwoord is niet juist.', $EmailAddress);
        }

        session_regenerate_id(true);

        $_SESSION['UserId'] = (int)$User['UserId'];
        $_SESSION['FirstName'] = $User['FirstName'];
        $_SESSION['LastName'] = $User['LastName'];
        $_SESSION['EmailAddress'] = $User['EmailAddress'];
        $_SESSION['IsAdmin'] = (int)$User['IsAdmin'];

        header('Location: /dashboard', true, 302);
        exit;
    } catch (PDOException $Exception) {
        RedirectToLoginWithError('Inloggen is nu niet gelukt door een databasefout.', $EmailAddress);
    }
}

function HandleLogoutRequest(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $CookieParameters = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $CookieParameters['path'],
            $CookieParameters['domain'],
            $CookieParameters['secure'],
            $CookieParameters['httponly']
        );
    }

    session_destroy();

    header('Location: /login', true, 302);
    exit;
}

function HandleRegisterRequest(): void
{
    $FirstName = NormalizePostValue('FirstName');
    $LastName = NormalizePostValue('LastName');
    $EmailAddress = NormalizePostValue('EmailAddress');
    $Password = (string)($_POST['Password'] ?? '');

    $OldInput = [
        'FirstName' => $FirstName,
        'LastName' => $LastName,
        'EmailAddress' => $EmailAddress,
    ];

    if ($FirstName === '' || $LastName === '' || $EmailAddress === '' || trim($Password) === '') {
        RedirectToRegisterWithError('Alle velden zijn verplicht.', $OldInput);
    }

    if (!filter_var($EmailAddress, FILTER_VALIDATE_EMAIL)) {
        RedirectToRegisterWithError('Vul een geldig e-mailadres in.', $OldInput);
    }

    if (strlen($Password) < 8) {
        RedirectToRegisterWithError('Het wachtwoord moet minimaal 8 tekens bevatten.', $OldInput);
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();

        // Prevent duplicate accounts before inserting the new user.
        $ExistingUserStatement = $DatabaseConnection->prepare(
            'SELECT UserId FROM Users WHERE EmailAddress = :EmailAddress LIMIT 1'
        );
        $ExistingUserStatement->execute(['EmailAddress' => $EmailAddress]);

        if ($ExistingUserStatement->fetch()) {
            RedirectToRegisterWithError('Er bestaat al een account met dit e-mailadres.', $OldInput);
        }

        $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
        $CreatedAt = date('Y-m-d H:i:s');

        $CreateUserStatement = $DatabaseConnection->prepare(
            'INSERT INTO Users (FirstName, LastName, EmailAddress, PasswordHash, CreatedAt)
             VALUES (:FirstName, :LastName, :EmailAddress, :PasswordHash, :CreatedAt)'
        );
        $CreateUserStatement->execute([
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'EmailAddress' => $EmailAddress,
            'PasswordHash' => $PasswordHash,
            'CreatedAt' => $CreatedAt,
        ]);

        RedirectToLoginWithSuccess('Je account is aangemaakt. Je kunt nu inloggen.');
    } catch (PDOException $Exception) {
        RedirectToRegisterWithError('Het account kon niet worden aangemaakt door een databasefout.', $OldInput);
    }
}

function HandleCreateLocationRequest(): void
{
    RequireAdmin();

    $Name = NormalizePostValue('Name');
    $GooglePlaceId = NormalizePostValue('GooglePlaceId');
    $FormattedAddress = NormalizePostValue('FormattedAddress');
    $DefaultTripDescription = NormalizePostValue('DefaultTripDescription');
    $Latitude = NormalizePostValue('Latitude');
    $Longitude = NormalizePostValue('Longitude');

    if ($Name === '' || $GooglePlaceId === '' || $FormattedAddress === '') {
        RedirectToAdminWithMessage('Error', 'Vul een locatienaam in en selecteer een Google Maps locatie.', 'Locations');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $CreatedAt = date('Y-m-d H:i:s');

        $CreateLocationStatement = $DatabaseConnection->prepare(
            'INSERT INTO Locations (Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, Latitude, Longitude, CreatedAt)
             VALUES (:Name, :GooglePlaceId, :FormattedAddress, :DefaultTripDescription, :Latitude, :Longitude, :CreatedAt)'
        );
        $CreateLocationStatement->execute([
            'Name' => $Name,
            'GooglePlaceId' => $GooglePlaceId,
            'FormattedAddress' => $FormattedAddress,
            'DefaultTripDescription' => $DefaultTripDescription !== '' ? $DefaultTripDescription : null,
            'Latitude' => $Latitude !== '' ? $Latitude : null,
            'Longitude' => $Longitude !== '' ? $Longitude : null,
            'CreatedAt' => $CreatedAt,
        ]);

        RedirectToAdminWithMessage('Success', 'Locatie is opgeslagen.', 'Locations');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De locatie kon niet worden opgeslagen. Mogelijk bestaat deze al.', 'Locations');
    }
}

function HandleUpdateLocationNameRequest(): void
{
    RequireAdmin();

    $LocationId = (int)NormalizePostValue('LocationId');
    $Name = NormalizePostValue('Name');
    $DefaultTripDescription = NormalizePostValue('DefaultTripDescription');

    if ($LocationId <= 0 || $Name === '') {
        RedirectToAdminWithMessage('Error', 'Vul een geldige locatienaam in.', 'Locations');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $UpdateLocationStatement = $DatabaseConnection->prepare(
            'UPDATE Locations
             SET Name = :Name,
                 DefaultTripDescription = :DefaultTripDescription
             WHERE LocationId = :LocationId'
        );
        $UpdateLocationStatement->execute([
            'Name' => $Name,
            'DefaultTripDescription' => $DefaultTripDescription !== '' ? $DefaultTripDescription : null,
            'LocationId' => $LocationId,
        ]);

        RedirectToAdminWithMessage('Success', 'Locatie is bijgewerkt.', 'Locations');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De locatienaam kon niet worden bijgewerkt.', 'Locations');
    }
}

function HandleUpdateLocationVisibilityRequest(): void
{
    RequireAdmin();

    $ActiveLocationIds = $_POST['ActiveLocationIds'] ?? [];

    if (!is_array($ActiveLocationIds)) {
        $ActiveLocationIds = [];
    }

    $ActiveLocationIds = array_values(array_unique(array_filter(array_map('intval', $ActiveLocationIds), function (int $LocationId): bool {
        return $LocationId > 0;
    })));

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $DatabaseConnection->beginTransaction();

        $DatabaseConnection->exec('UPDATE Locations SET IsActive = 0');

        if ($ActiveLocationIds) {
            $Placeholders = implode(',', array_fill(0, count($ActiveLocationIds), '?'));
            $ActivateLocationsStatement = $DatabaseConnection->prepare(
                'UPDATE Locations
                 SET IsActive = 1
                 WHERE LocationId IN (' . $Placeholders . ')'
            );
            $ActivateLocationsStatement->execute($ActiveLocationIds);
        }

        $DatabaseConnection->commit();

        RedirectToAdminWithMessage('Success', 'Locatiezichtbaarheid is bijgewerkt.', 'Locations');
    } catch (PDOException $Exception) {
        if (isset($DatabaseConnection) && $DatabaseConnection->inTransaction()) {
            $DatabaseConnection->rollBack();
        }

        RedirectToAdminWithMessage('Error', 'De locatiezichtbaarheid kon niet worden bijgewerkt.', 'Locations');
    }
}

function HandleDeleteLocationRequest(): void
{
    RequireAdmin();

    $LocationId = (int)NormalizePostValue('LocationId');

    if ($LocationId <= 0) {
        RedirectToAdminWithMessage('Error', 'Kies een geldige locatie om te verwijderen.', 'Locations');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();

        // Keep trip history intact by preventing deletion of locations already used in trips.
        $UsageStatement = $DatabaseConnection->prepare(
            'SELECT COUNT(*) AS UsageCount
             FROM TripRegistrations
             WHERE StartLocationId = :StartLocationId
                OR EndLocationId = :EndLocationId'
        );
        $UsageStatement->execute([
            'StartLocationId' => $LocationId,
            'EndLocationId' => $LocationId,
        ]);
        $Usage = $UsageStatement->fetch();

        if ((int)$Usage['UsageCount'] > 0) {
            RedirectToAdminWithMessage('Error', 'Deze locatie wordt al gebruikt in ritten en kan niet worden verwijderd.', 'Locations');
        }

        $DeleteLocationStatement = $DatabaseConnection->prepare(
            'DELETE FROM Locations WHERE LocationId = :LocationId'
        );
        $DeleteLocationStatement->execute(['LocationId' => $LocationId]);

        RedirectToAdminWithMessage('Success', 'Locatie is verwijderd.', 'Locations');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De locatie kon niet worden verwijderd.', 'Locations');
    }
}

function GetLocationById(PDO $DatabaseConnection, int $LocationId): ?array
{
    $LocationStatement = $DatabaseConnection->prepare(
        'SELECT LocationId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, IsActive
         FROM Locations
         WHERE LocationId = :LocationId
         LIMIT 1'
    );
    $LocationStatement->execute(['LocationId' => $LocationId]);
    $Location = $LocationStatement->fetch();

    return $Location ?: null;
}

function IsLocationSelectableForTrip(?array $Location, ?int $ExistingLocationId = null): bool
{
    if (!$Location) {
        return false;
    }

    return (int)$Location['IsActive'] === 1 || ($ExistingLocationId !== null && (int)$Location['LocationId'] === $ExistingLocationId);
}

function BuildRoutesWaypoint(array $Location): array
{
    if (!empty($Location['GooglePlaceId'])) {
        return ['placeId' => $Location['GooglePlaceId']];
    }

    return ['address' => $Location['FormattedAddress']];
}

function ExecuteJsonPostRequest(string $Url, array $Headers, array $Payload): array
{
    $JsonPayload = json_encode($Payload);

    if (function_exists('curl_init')) {
        $CurlHandle = curl_init($Url);
        curl_setopt_array($CurlHandle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $JsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $Headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        $ResponseBody = curl_exec($CurlHandle);
        $ResponseCode = (int)curl_getinfo($CurlHandle, CURLINFO_HTTP_CODE);
        curl_close($CurlHandle);

        return [$ResponseCode, (string)$ResponseBody];
    }

    $Context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $Headers),
            'content' => $JsonPayload,
            'timeout' => 20,
        ],
    ]);

    $ResponseBody = file_get_contents($Url, false, $Context);
    $ResponseCode = 0;

    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $Matches)) {
        $ResponseCode = (int)$Matches[1];
    }

    return [$ResponseCode, (string)$ResponseBody];
}

function ExecuteJsonGetRequest(string $Url, array $Headers): array
{
    if (function_exists('curl_init')) {
        $CurlHandle = curl_init($Url);
        curl_setopt_array($CurlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $Headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        $ResponseBody = curl_exec($CurlHandle);
        $ResponseCode = (int)curl_getinfo($CurlHandle, CURLINFO_HTTP_CODE);
        curl_close($CurlHandle);

        return [$ResponseCode, (string)$ResponseBody];
    }

    $Context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $Headers),
            'timeout' => 20,
        ],
    ]);

    $ResponseBody = file_get_contents($Url, false, $Context);
    $ResponseCode = 0;

    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $Matches)) {
        $ResponseCode = (int)$Matches[1];
    }

    return [$ResponseCode, (string)$ResponseBody];
}

function HandleSearchLocationsRequest(): void
{
    RequireAdmin();

    $Query = NormalizePostValue('Query');

    if (strlen($Query) < 5) {
        SendJsonResponse(['Ok' => true, 'Suggestions' => []]);
    }

    if (GoogleMapsApiKey === '') {
        SendJsonResponse(['Ok' => false, 'Error' => 'Google Maps API key ontbreekt.'], 400);
    }

    [$ResponseCode, $ResponseBody] = ExecuteJsonPostRequest(
        'https://places.googleapis.com/v1/places:autocomplete',
        [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . GoogleMapsApiKey,
        ],
        [
            'input' => $Query,
            'includedRegionCodes' => ['nl'],
            'languageCode' => 'nl',
        ]
    );

    $ResponseData = json_decode($ResponseBody, true);

    if ($ResponseCode < 200 || $ResponseCode >= 300) {
        SendJsonResponse([
            'Ok' => false,
            'Error' => $ResponseData['error']['message'] ?? 'Google Places zoeken is niet gelukt.',
        ], 400);
    }

    $Suggestions = [];

    foreach (($ResponseData['suggestions'] ?? []) as $Suggestion) {
        if (empty($Suggestion['placePrediction']['placeId'])) {
            continue;
        }

        $Suggestions[] = [
            'PlaceId' => $Suggestion['placePrediction']['placeId'],
            'Description' => $Suggestion['placePrediction']['text']['text'] ?? '',
        ];
    }

    SendJsonResponse(['Ok' => true, 'Suggestions' => $Suggestions]);
}

function HandleGetLocationDetailsRequest(): void
{
    RequireAdmin();

    $GooglePlaceId = NormalizePostValue('GooglePlaceId');

    if ($GooglePlaceId === '') {
        SendJsonResponse(['Ok' => false, 'Error' => 'Geen Google Place ID ontvangen.'], 400);
    }

    if (GoogleMapsApiKey === '') {
        SendJsonResponse(['Ok' => false, 'Error' => 'Google Maps API key ontbreekt.'], 400);
    }

    [$ResponseCode, $ResponseBody] = ExecuteJsonGetRequest(
        'https://places.googleapis.com/v1/places/' . rawurlencode($GooglePlaceId),
        [
            'X-Goog-Api-Key: ' . GoogleMapsApiKey,
            'X-Goog-FieldMask: id,formattedAddress,location',
            'Accept: application/json',
        ]
    );

    $ResponseData = json_decode($ResponseBody, true);

    if ($ResponseCode < 200 || $ResponseCode >= 300) {
        SendJsonResponse([
            'Ok' => false,
            'Error' => $ResponseData['error']['message'] ?? 'Google locatie kon niet worden geladen.',
        ], 400);
    }

    SendJsonResponse([
        'Ok' => true,
        'Place' => [
            'PlaceId' => $ResponseData['id'] ?? $GooglePlaceId,
            'FormattedAddress' => $ResponseData['formattedAddress'] ?? '',
            'Latitude' => $ResponseData['location']['latitude'] ?? '',
            'Longitude' => $ResponseData['location']['longitude'] ?? '',
        ],
    ]);
}

function ComputeDrivingDistanceMeters(array $StartLocation, array $EndLocation): int
{
    if (GoogleMapsApiKey === '') {
        throw new RuntimeException('Google Maps API key ontbreekt.');
    }

    $Payload = [
        'origin' => BuildRoutesWaypoint($StartLocation),
        'destination' => BuildRoutesWaypoint($EndLocation),
        'travelMode' => 'DRIVE',
        'routingPreference' => 'TRAFFIC_AWARE',
        'computeAlternativeRoutes' => false,
        'units' => 'METRIC',
    ];

    [$ResponseCode, $ResponseBody] = ExecuteJsonPostRequest(
        'https://routes.googleapis.com/directions/v2:computeRoutes',
        [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . GoogleMapsApiKey,
            'X-Goog-FieldMask: routes.distanceMeters',
        ],
        $Payload
    );

    $ResponseData = json_decode($ResponseBody, true);

    if ($ResponseCode < 200 || $ResponseCode >= 300 || empty($ResponseData['routes'][0]['distanceMeters'])) {
        throw new RuntimeException('Google Maps kon geen autoroute berekenen.');
    }

    return (int)$ResponseData['routes'][0]['distanceMeters'];
}

const CommuteCompensationDeductionMeters = 72000;

function CalculateStoredTripDistanceMeters(array $StartLocation, array $EndLocation, int $IsRoundTrip, int $ApplyCommuteCompensation): int
{
    $DistanceMeters = ComputeDrivingDistanceMeters($StartLocation, $EndLocation);

    // Store the full travel distance when the trip is marked as return travel.
    if ($IsRoundTrip === 1) {
        $DistanceMeters *= 2;
    }

    if ($ApplyCommuteCompensation === 1) {
        $DistanceMeters = max(0, $DistanceMeters - CommuteCompensationDeductionMeters);
    }

    return $DistanceMeters;
}

function HandleCreateTripRegistrationRequest(): void
{
    RequireLogin();

    $TripDate = NormalizePostValue('TripDate');
    $StartLocationId = (int)NormalizePostValue('StartLocationId');
    $EndLocationId = (int)NormalizePostValue('EndLocationId');
    $IsRoundTrip = NormalizePostValue('IsRoundTrip') === '1' ? 1 : 0;
    $ApplyCommuteCompensation = NormalizePostValue('ApplyCommuteCompensation') === '1' ? 1 : 0;
    $TripDescription = NormalizePostValue('TripDescription');

    if ($TripDate === '' || $StartLocationId <= 0 || $EndLocationId <= 0) {
        RedirectToDashboardWithMessage('Error', 'Vul een datum, startlocatie en eindlocatie in.');
    }

    if ($StartLocationId === $EndLocationId) {
        RedirectToDashboardWithMessage('Error', 'Startlocatie en eindlocatie mogen niet hetzelfde zijn.');
    }

    $DateTime = DateTime::createFromFormat('Y-m-d', $TripDate);
    if (!$DateTime || $DateTime->format('Y-m-d') !== $TripDate) {
        RedirectToDashboardWithMessage('Error', 'Kies een geldige datum.');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId);

        if (!$StartLocation || !$EndLocation) {
            RedirectToDashboardWithMessage('Error', 'Een van de gekozen locaties bestaat niet.');
        }

        if (!IsLocationSelectableForTrip($StartLocation) || !IsLocationSelectableForTrip($EndLocation)) {
            RedirectToDashboardWithMessage('Error', 'Een van de gekozen locaties is niet actief.');
        }

        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);
        $CreatedAt = date('Y-m-d H:i:s');

        $CreateTripStatement = $DatabaseConnection->prepare(
            'INSERT INTO TripRegistrations (UserId, TripDate, StartLocationId, EndLocationId, IsRoundTrip, ApplyCommuteCompensation, TripDescription, DistanceMeters, DistanceKilometers, CreatedAt)
             VALUES (:UserId, :TripDate, :StartLocationId, :EndLocationId, :IsRoundTrip, :ApplyCommuteCompensation, :TripDescription, :DistanceMeters, :DistanceKilometers, :CreatedAt)'
        );
        $CreateTripStatement->execute([
            'UserId' => (int)$_SESSION['UserId'],
            'TripDate' => $TripDate,
            'StartLocationId' => $StartLocationId,
            'EndLocationId' => $EndLocationId,
            'IsRoundTrip' => $IsRoundTrip,
            'ApplyCommuteCompensation' => $ApplyCommuteCompensation,
            'TripDescription' => $TripDescription !== '' ? $TripDescription : null,
            'DistanceMeters' => $DistanceMeters,
            'DistanceKilometers' => $DistanceKilometers,
            'CreatedAt' => $CreatedAt,
        ]);

        RedirectToDashboardWithMessage('Success', 'Rit is opgeslagen met ' . number_format($DistanceKilometers, 2, ',', '.') . ' km.');
    } catch (Throwable $Exception) {
        RedirectToDashboardWithMessage('Error', 'De rit kon niet worden opgeslagen: ' . $Exception->getMessage());
    }
}

function HandleUpdateTripRegistrationRequest(): void
{
    RequireLogin();

    $TripRegistrationId = (int)NormalizePostValue('TripRegistrationId');
    $TripDate = NormalizePostValue('TripDate');
    $StartLocationId = (int)NormalizePostValue('StartLocationId');
    $EndLocationId = (int)NormalizePostValue('EndLocationId');
    $IsRoundTrip = NormalizePostValue('IsRoundTrip') === '1' ? 1 : 0;
    $ApplyCommuteCompensation = NormalizePostValue('ApplyCommuteCompensation') === '1' ? 1 : 0;
    $TripDescription = NormalizePostValue('TripDescription');

    if ($TripRegistrationId <= 0 || $TripDate === '' || $StartLocationId <= 0 || $EndLocationId <= 0) {
        RedirectToTripsWithMessage('Error', 'Vul een datum, startlocatie en eindlocatie in.');
    }

    if ($StartLocationId === $EndLocationId) {
        RedirectToTripsWithMessage('Error', 'Startlocatie en eindlocatie mogen niet hetzelfde zijn.');
    }

    $DateTime = DateTime::createFromFormat('Y-m-d', $TripDate);
    if (!$DateTime || $DateTime->format('Y-m-d') !== $TripDate) {
        RedirectToTripsWithMessage('Error', 'Kies een geldige datum.');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();

        $ExistingTripStatement = $DatabaseConnection->prepare(
            'SELECT TripRegistrationId, StartLocationId, EndLocationId
             FROM TripRegistrations
             WHERE TripRegistrationId = :TripRegistrationId
               AND UserId = :UserId
             LIMIT 1'
        );
        $ExistingTripStatement->execute([
            'TripRegistrationId' => $TripRegistrationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        $ExistingTrip = $ExistingTripStatement->fetch();

        if (!$ExistingTrip) {
            RedirectToTripsWithMessage('Error', 'Deze rit bestaat niet of hoort niet bij jouw account.');
        }

        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId);

        if (!$StartLocation || !$EndLocation) {
            RedirectToTripsWithMessage('Error', 'Een van de gekozen locaties bestaat niet.');
        }

        if (!IsLocationSelectableForTrip($StartLocation, (int)$ExistingTrip['StartLocationId']) || !IsLocationSelectableForTrip($EndLocation, (int)$ExistingTrip['EndLocationId'])) {
            RedirectToTripsWithMessage('Error', 'Een van de gekozen locaties is niet actief.');
        }

        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);

        $UpdateTripStatement = $DatabaseConnection->prepare(
            'UPDATE TripRegistrations
             SET TripDate = :TripDate,
                 StartLocationId = :StartLocationId,
                 EndLocationId = :EndLocationId,
                 IsRoundTrip = :IsRoundTrip,
                 ApplyCommuteCompensation = :ApplyCommuteCompensation,
                 TripDescription = :TripDescription,
                 DistanceMeters = :DistanceMeters,
                 DistanceKilometers = :DistanceKilometers
             WHERE TripRegistrationId = :TripRegistrationId
               AND UserId = :UserId'
        );
        $UpdateTripStatement->execute([
            'TripDate' => $TripDate,
            'StartLocationId' => $StartLocationId,
            'EndLocationId' => $EndLocationId,
            'IsRoundTrip' => $IsRoundTrip,
            'ApplyCommuteCompensation' => $ApplyCommuteCompensation,
            'TripDescription' => $TripDescription !== '' ? $TripDescription : null,
            'DistanceMeters' => $DistanceMeters,
            'DistanceKilometers' => $DistanceKilometers,
            'TripRegistrationId' => $TripRegistrationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        RedirectToTripsWithMessage('Success', 'Rit is bijgewerkt met ' . number_format($DistanceKilometers, 2, ',', '.') . ' km.');
    } catch (Throwable $Exception) {
        RedirectToTripsWithMessage('Error', 'De rit kon niet worden bijgewerkt: ' . $Exception->getMessage());
    }
}

function HandleDeleteTripRegistrationRequest(): void
{
    RequireLogin();

    $TripRegistrationId = (int)NormalizePostValue('TripRegistrationId');

    if ($TripRegistrationId <= 0) {
        RedirectToTripsWithMessage('Error', 'Kies een geldige rit om te verwijderen.');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $DeleteTripStatement = $DatabaseConnection->prepare(
            'DELETE FROM TripRegistrations
             WHERE TripRegistrationId = :TripRegistrationId
               AND UserId = :UserId'
        );
        $DeleteTripStatement->execute([
            'TripRegistrationId' => $TripRegistrationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        if ($DeleteTripStatement->rowCount() === 0) {
            RedirectToTripsWithMessage('Error', 'Deze rit bestaat niet of hoort niet bij jouw account.');
        }

        RedirectToTripsWithMessage('Success', 'Rit is verwijderd.');
    } catch (PDOException $Exception) {
        RedirectToTripsWithMessage('Error', 'De rit kon niet worden verwijderd.');
    }
}

function HandleUpdateAdminTripRegistrationRequest(): void
{
    RequireAdmin();

    $TripRegistrationId = (int)NormalizePostValue('TripRegistrationId');
    $TripDate = NormalizePostValue('TripDate');
    $StartLocationId = (int)NormalizePostValue('StartLocationId');
    $EndLocationId = (int)NormalizePostValue('EndLocationId');
    $IsRoundTrip = NormalizePostValue('IsRoundTrip') === '1' ? 1 : 0;
    $ApplyCommuteCompensation = NormalizePostValue('ApplyCommuteCompensation') === '1' ? 1 : 0;
    $TripDescription = NormalizePostValue('TripDescription');

    if ($TripRegistrationId <= 0 || $TripDate === '' || $StartLocationId <= 0 || $EndLocationId <= 0) {
        RedirectToAdminWithMessage('Error', 'Vul een datum, startlocatie en eindlocatie in.', 'Trips');
    }

    if ($StartLocationId === $EndLocationId) {
        RedirectToAdminWithMessage('Error', 'Startlocatie en eindlocatie mogen niet hetzelfde zijn.', 'Trips');
    }

    $DateTime = DateTime::createFromFormat('Y-m-d', $TripDate);
    if (!$DateTime || $DateTime->format('Y-m-d') !== $TripDate) {
        RedirectToAdminWithMessage('Error', 'Kies een geldige datum.', 'Trips');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();

        $ExistingTripStatement = $DatabaseConnection->prepare(
            'SELECT TripRegistrationId, StartLocationId, EndLocationId
             FROM TripRegistrations
             WHERE TripRegistrationId = :TripRegistrationId
             LIMIT 1'
        );
        $ExistingTripStatement->execute(['TripRegistrationId' => $TripRegistrationId]);

        $ExistingTrip = $ExistingTripStatement->fetch();

        if (!$ExistingTrip) {
            RedirectToAdminWithMessage('Error', 'Deze rit bestaat niet.', 'Trips');
        }

        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId);

        if (!$StartLocation || !$EndLocation) {
            RedirectToAdminWithMessage('Error', 'Een van de gekozen locaties bestaat niet.', 'Trips');
        }

        if (!IsLocationSelectableForTrip($StartLocation, (int)$ExistingTrip['StartLocationId']) || !IsLocationSelectableForTrip($EndLocation, (int)$ExistingTrip['EndLocationId'])) {
            RedirectToAdminWithMessage('Error', 'Een van de gekozen locaties is niet actief.', 'Trips');
        }

        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);

        $UpdateTripStatement = $DatabaseConnection->prepare(
            'UPDATE TripRegistrations
             SET TripDate = :TripDate,
                 StartLocationId = :StartLocationId,
                 EndLocationId = :EndLocationId,
                 IsRoundTrip = :IsRoundTrip,
                 ApplyCommuteCompensation = :ApplyCommuteCompensation,
                 TripDescription = :TripDescription,
                 DistanceMeters = :DistanceMeters,
                 DistanceKilometers = :DistanceKilometers
             WHERE TripRegistrationId = :TripRegistrationId'
        );
        $UpdateTripStatement->execute([
            'TripDate' => $TripDate,
            'StartLocationId' => $StartLocationId,
            'EndLocationId' => $EndLocationId,
            'IsRoundTrip' => $IsRoundTrip,
            'ApplyCommuteCompensation' => $ApplyCommuteCompensation,
            'TripDescription' => $TripDescription !== '' ? $TripDescription : null,
            'DistanceMeters' => $DistanceMeters,
            'DistanceKilometers' => $DistanceKilometers,
            'TripRegistrationId' => $TripRegistrationId,
        ]);

        RedirectToAdminWithMessage('Success', 'Rit is bijgewerkt met ' . number_format($DistanceKilometers, 2, ',', '.') . ' km.', 'Trips');
    } catch (Throwable $Exception) {
        RedirectToAdminWithMessage('Error', 'De rit kon niet worden bijgewerkt: ' . $Exception->getMessage(), 'Trips');
    }
}

function HandleDeleteAdminTripRegistrationRequest(): void
{
    RequireAdmin();

    $TripRegistrationId = (int)NormalizePostValue('TripRegistrationId');

    if ($TripRegistrationId <= 0) {
        RedirectToAdminWithMessage('Error', 'Kies een geldige rit om te verwijderen.', 'Trips');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $DeleteTripStatement = $DatabaseConnection->prepare(
            'DELETE FROM TripRegistrations
             WHERE TripRegistrationId = :TripRegistrationId'
        );
        $DeleteTripStatement->execute(['TripRegistrationId' => $TripRegistrationId]);

        if ($DeleteTripStatement->rowCount() === 0) {
            RedirectToAdminWithMessage('Error', 'Deze rit bestaat niet.', 'Trips');
        }

        RedirectToAdminWithMessage('Success', 'Rit is verwijderd.', 'Trips');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De rit kon niet worden verwijderd.', 'Trips');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$Action = NormalizePostValue('Action');

if ($Action === 'Login') {
    HandleLoginRequest();
}

if ($Action === 'Logout') {
    HandleLogoutRequest();
}

if ($Action === 'Register') {
    HandleRegisterRequest();
}

if ($Action === 'SearchLocations') {
    HandleSearchLocationsRequest();
}

if ($Action === 'GetLocationDetails') {
    HandleGetLocationDetailsRequest();
}

if ($Action === 'CreateLocation') {
    HandleCreateLocationRequest();
}

if ($Action === 'UpdateLocationName') {
    HandleUpdateLocationNameRequest();
}

if ($Action === 'UpdateLocationVisibility') {
    HandleUpdateLocationVisibilityRequest();
}

if ($Action === 'DeleteLocation') {
    HandleDeleteLocationRequest();
}

if ($Action === 'CreateTripRegistration') {
    HandleCreateTripRegistrationRequest();
}

if ($Action === 'UpdateTripRegistration') {
    HandleUpdateTripRegistrationRequest();
}

if ($Action === 'DeleteTripRegistration') {
    HandleDeleteTripRegistrationRequest();
}

if ($Action === 'UpdateAdminTripRegistration') {
    HandleUpdateAdminTripRegistrationRequest();
}

if ($Action === 'DeleteAdminTripRegistration') {
    HandleDeleteAdminTripRegistrationRequest();
}

http_response_code(400);
echo 'Invalid Action';
