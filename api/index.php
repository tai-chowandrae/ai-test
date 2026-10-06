<?php
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/GoogleMaps.php';
require_once __DIR__ . '/../includes/TripOverviewRenderer.php';

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

function RedirectToAdminWithMessage(string $Type, string $Message, string $AdminView = '', string $UserMode = ''): void
{
    $_SESSION['AdminMessage'] = [
        'Type' => $Type,
        'Message' => $Message,
        'View' => $AdminView !== '' ? $AdminView : 'Welcome',
        'UserMode' => $UserMode,
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

function RedirectToSettingsWithMessage(string $Type, string $Message): void
{
    $_SESSION['SettingsMessage'] = [
        'Type' => $Type,
        'Message' => $Message,
    ];

    header('Location: /settings', true, 302);
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
            'SELECT UserId, FirstName, LastName, EmailAddress, PasswordHash, IsAdmin, IsActive
             FROM users
             WHERE EmailAddress = :EmailAddress
             LIMIT 1'
        );
        $UserStatement->execute(['EmailAddress' => $EmailAddress]);

        $User = $UserStatement->fetch();

        if (!$User || (int)$User['IsActive'] !== 1 || !password_verify($Password, $User['PasswordHash'])) {
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
            'SELECT UserId FROM users WHERE EmailAddress = :EmailAddress LIMIT 1'
        );
        $ExistingUserStatement->execute(['EmailAddress' => $EmailAddress]);

        if ($ExistingUserStatement->fetch()) {
            RedirectToRegisterWithError('Er bestaat al een account met dit e-mailadres.', $OldInput);
        }

        $PasswordHash = password_hash($Password, PASSWORD_DEFAULT);
        $CreatedAt = date('Y-m-d H:i:s');

        $CreateUserStatement = $DatabaseConnection->prepare(
            'INSERT INTO users (FirstName, LastName, EmailAddress, PasswordHash, CreatedAt)
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

function HandleCreateAdminUserRequest(): void
{
    RequireAdmin();

    $FirstName = NormalizePostValue('FirstName');
    $LastName = NormalizePostValue('LastName');
    $EmailAddress = NormalizePostValue('EmailAddress');
    $CopiedLocationIds = $_POST['CopiedLocationIds'] ?? [];

    if ($FirstName === '' || $LastName === '' || $EmailAddress === '') {
        RedirectToAdminWithMessage('Error', 'Voornaam, achternaam en e-mailadres zijn verplicht.', 'Users');
    }

    if (!filter_var($EmailAddress, FILTER_VALIDATE_EMAIL)) {
        RedirectToAdminWithMessage('Error', 'Vul een geldig e-mailadres in.', 'Users');
    }

    if (!is_array($CopiedLocationIds)) {
        $CopiedLocationIds = [];
    }

    $CopiedLocationIds = array_values(array_unique(array_filter(array_map('intval', $CopiedLocationIds), function (int $LocationId): bool {
        return $LocationId > 0;
    })));

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $ExistingUserStatement = $DatabaseConnection->prepare(
            'SELECT UserId FROM users WHERE EmailAddress = :EmailAddress LIMIT 1'
        );
        $ExistingUserStatement->execute(['EmailAddress' => $EmailAddress]);

        if ($ExistingUserStatement->fetch()) {
            RedirectToAdminWithMessage('Error', 'Er bestaat al een gebruiker met dit e-mailadres.', 'Users');
        }

        $TemporaryPassword = bin2hex(random_bytes(6));
        $DatabaseConnection->beginTransaction();

        $CreateUserStatement = $DatabaseConnection->prepare(
            'INSERT INTO users (FirstName, LastName, EmailAddress, PasswordHash, IsAdmin, IsActive, CreatedAt)
             VALUES (:FirstName, :LastName, :EmailAddress, :PasswordHash, 0, 1, :CreatedAt)'
        );
        $CreateUserStatement->execute([
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'EmailAddress' => $EmailAddress,
            'PasswordHash' => password_hash($TemporaryPassword, PASSWORD_DEFAULT),
            'CreatedAt' => date('Y-m-d H:i:s'),
        ]);
        $NewUserId = (int)$DatabaseConnection->lastInsertId();

        if ($CopiedLocationIds) {
            $LocationPlaceholders = [];
            $CopyLocationParameters = [
                'NewUserId' => $NewUserId,
                'SourceUserId' => (int)$_SESSION['UserId'],
                'CreatedAt' => date('Y-m-d H:i:s'),
            ];

            foreach ($CopiedLocationIds as $Index => $LocationId) {
                $ParameterName = 'LocationId' . $Index;
                $LocationPlaceholders[] = ':' . $ParameterName;
                $CopyLocationParameters[$ParameterName] = $LocationId;
            }

            $CopyLocationsStatement = $DatabaseConnection->prepare(
                'INSERT INTO locations (UserId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, IsActive, Latitude, Longitude, CreatedAt)
                 SELECT :NewUserId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, IsActive, Latitude, Longitude, :CreatedAt
                 FROM locations
                 WHERE UserId = :SourceUserId
                   AND IsActive = 1
                   AND LocationId IN (' . implode(',', $LocationPlaceholders) . ')'
            );
            $CopyLocationsStatement->execute($CopyLocationParameters);
        }

        $DatabaseConnection->commit();

        RedirectToAdminWithMessage(
            'Success',
            'Gebruiker is aangemaakt. Tijdelijk wachtwoord: ' . $TemporaryPassword,
            'Users'
        );
    } catch (Throwable $Exception) {
        if (isset($DatabaseConnection) && $DatabaseConnection->inTransaction()) {
            $DatabaseConnection->rollBack();
        }

        RedirectToAdminWithMessage('Error', 'De gebruiker kon niet worden aangemaakt.', 'Users');
    }
}

function HandleUpdateAdminUserRequest(): void
{
    RequireAdmin();

    $UserId = (int)NormalizePostValue('UserId');
    $FirstName = NormalizePostValue('FirstName');
    $LastName = NormalizePostValue('LastName');
    $EmailAddress = NormalizePostValue('EmailAddress');
    $IsActive = NormalizePostValue('IsActive') === '1' ? 1 : 0;
    $UserMode = 'edit-' . $UserId;

    if ($UserId <= 0 || $FirstName === '' || $LastName === '' || $EmailAddress === '') {
        RedirectToAdminWithMessage('Error', 'Voornaam, achternaam en e-mailadres zijn verplicht.', 'Users', $UserMode);
    }

    if (!filter_var($EmailAddress, FILTER_VALIDATE_EMAIL)) {
        RedirectToAdminWithMessage('Error', 'Vul een geldig e-mailadres in.', 'Users', $UserMode);
    }

    if ($UserId === (int)$_SESSION['UserId'] && $IsActive !== 1) {
        RedirectToAdminWithMessage('Error', 'Je kunt je eigen account niet deactiveren.', 'Users', $UserMode);
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $ExistingEmailStatement = $DatabaseConnection->prepare(
            'SELECT UserId
             FROM users
             WHERE EmailAddress = :EmailAddress
               AND UserId <> :UserId
             LIMIT 1'
        );
        $ExistingEmailStatement->execute([
            'EmailAddress' => $EmailAddress,
            'UserId' => $UserId,
        ]);

        if ($ExistingEmailStatement->fetch()) {
            RedirectToAdminWithMessage('Error', 'Er bestaat al een gebruiker met dit e-mailadres.', 'Users', $UserMode);
        }

        $UpdateUserStatement = $DatabaseConnection->prepare(
            'UPDATE users
             SET FirstName = :FirstName,
                 LastName = :LastName,
                 EmailAddress = :EmailAddress,
                 IsActive = :IsActive
             WHERE UserId = :UserId'
        );
        $UpdateUserStatement->execute([
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'EmailAddress' => $EmailAddress,
            'IsActive' => $IsActive,
            'UserId' => $UserId,
        ]);

        if ($UpdateUserStatement->rowCount() === 0) {
            $UserStatement = $DatabaseConnection->prepare('SELECT UserId FROM users WHERE UserId = :UserId LIMIT 1');
            $UserStatement->execute(['UserId' => $UserId]);

            if (!$UserStatement->fetch()) {
                RedirectToAdminWithMessage('Error', 'Deze gebruiker bestaat niet.', 'Users', 'list');
            }
        }

        if ($UserId === (int)$_SESSION['UserId']) {
            $_SESSION['FirstName'] = $FirstName;
            $_SESSION['LastName'] = $LastName;
            $_SESSION['EmailAddress'] = $EmailAddress;
        }

        RedirectToAdminWithMessage('Success', 'Gebruiker is bijgewerkt.', 'Users', 'list');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De gebruiker kon niet worden bijgewerkt.', 'Users', $UserMode);
    }
}

function HandleUpdateOwnUserRequest(): void
{
    RequireLogin();

    $FirstName = NormalizePostValue('FirstName');
    $LastName = NormalizePostValue('LastName');
    $EmailAddress = NormalizePostValue('EmailAddress');
    $IsCommuteCompensationEnabled = NormalizePostValue('IsCommuteCompensationEnabled') === '1' ? 1 : 0;
    $CommuteCompensationKilometersValue = str_replace(',', '.', NormalizePostValue('CommuteCompensationKilometers'));

    if ($FirstName === '' || $LastName === '' || $EmailAddress === '') {
        RedirectToSettingsWithMessage('Error', 'Voornaam, achternaam en e-mailadres zijn verplicht.');
    }

    if (!filter_var($EmailAddress, FILTER_VALIDATE_EMAIL)) {
        RedirectToSettingsWithMessage('Error', 'Vul een geldig e-mailadres in.');
    }

    if (!is_numeric($CommuteCompensationKilometersValue)) {
        RedirectToSettingsWithMessage('Error', 'Vul een geldig aantal kilometers voor de woon-werkcompensatie in.');
    }

    $CommuteCompensationKilometers = round((float)$CommuteCompensationKilometersValue, 2);

    if ($CommuteCompensationKilometers < 0 || $CommuteCompensationKilometers > 1000) {
        RedirectToSettingsWithMessage('Error', 'De woon-werkcompensatie moet tussen 0 en 1000 kilometer liggen.');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $ExistingEmailStatement = $DatabaseConnection->prepare(
            'SELECT UserId
             FROM users
             WHERE EmailAddress = :EmailAddress
               AND UserId <> :UserId
             LIMIT 1'
        );
        $ExistingEmailStatement->execute([
            'EmailAddress' => $EmailAddress,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        if ($ExistingEmailStatement->fetch()) {
            RedirectToSettingsWithMessage('Error', 'Er bestaat al een gebruiker met dit e-mailadres.');
        }

        $UpdateUserStatement = $DatabaseConnection->prepare(
            'UPDATE users
             SET FirstName = :FirstName,
                 LastName = :LastName,
                 EmailAddress = :EmailAddress,
                 IsCommuteCompensationEnabled = :IsCommuteCompensationEnabled,
                 CommuteCompensationKilometers = :CommuteCompensationKilometers
             WHERE UserId = :UserId'
        );
        $UpdateUserStatement->execute([
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'EmailAddress' => $EmailAddress,
            'IsCommuteCompensationEnabled' => $IsCommuteCompensationEnabled,
            'CommuteCompensationKilometers' => $CommuteCompensationKilometers,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        $_SESSION['FirstName'] = $FirstName;
        $_SESSION['LastName'] = $LastName;
        $_SESSION['EmailAddress'] = $EmailAddress;

        RedirectToSettingsWithMessage('Success', 'Je gegevens zijn bijgewerkt.');
    } catch (PDOException $Exception) {
        RedirectToSettingsWithMessage('Error', 'Je gegevens konden niet worden bijgewerkt.');
    }
}

function HandleCreateLocationRequest(): void
{
    RequireLogin();

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
            'INSERT INTO locations (UserId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, Latitude, Longitude, CreatedAt)
             VALUES (:UserId, :Name, :GooglePlaceId, :FormattedAddress, :DefaultTripDescription, :Latitude, :Longitude, :CreatedAt)'
        );
        $CreateLocationStatement->execute([
            'UserId' => (int)$_SESSION['UserId'],
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
        RedirectToAdminWithMessage('Error', 'De locatie kon niet worden opgeslagen.', 'Locations');
    }
}

function HandleUpdateLocationNameRequest(): void
{
    RequireLogin();

    $LocationId = (int)NormalizePostValue('LocationId');
    $Name = NormalizePostValue('Name');
    $DefaultTripDescription = NormalizePostValue('DefaultTripDescription');

    if ($LocationId <= 0 || $Name === '') {
        RedirectToAdminWithMessage('Error', 'Vul een geldige locatienaam in.', 'Locations');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $UpdateLocationStatement = $DatabaseConnection->prepare(
            'UPDATE locations
             SET Name = :Name,
                 DefaultTripDescription = :DefaultTripDescription
             WHERE LocationId = :LocationId
               AND UserId = :UserId'
        );
        $UpdateLocationStatement->execute([
            'Name' => $Name,
            'DefaultTripDescription' => $DefaultTripDescription !== '' ? $DefaultTripDescription : null,
            'LocationId' => $LocationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        RedirectToAdminWithMessage('Success', 'Locatie is bijgewerkt.', 'Locations');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De locatienaam kon niet worden bijgewerkt.', 'Locations');
    }
}

function HandleUpdateLocationVisibilityRequest(): void
{
    RequireLogin();

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

        $DeactivateLocationsStatement = $DatabaseConnection->prepare(
            'UPDATE locations SET IsActive = 0 WHERE UserId = :UserId'
        );
        $DeactivateLocationsStatement->execute(['UserId' => (int)$_SESSION['UserId']]);

        if ($ActiveLocationIds) {
            $Placeholders = implode(',', array_fill(0, count($ActiveLocationIds), '?'));
            $ActivateLocationsStatement = $DatabaseConnection->prepare(
                'UPDATE locations
                 SET IsActive = 1
                 WHERE LocationId IN (' . $Placeholders . ')
                   AND UserId = ?'
            );
            $ActivateLocationsStatement->execute(array_merge($ActiveLocationIds, [(int)$_SESSION['UserId']]));
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
    RequireLogin();

    $LocationId = (int)NormalizePostValue('LocationId');

    if ($LocationId <= 0) {
        RedirectToAdminWithMessage('Error', 'Kies een geldige locatie om te verwijderen.', 'Locations');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();

        // Keep trip history intact by preventing deletion of locations already used in trips.
        $UsageStatement = $DatabaseConnection->prepare(
            'SELECT COUNT(*) AS UsageCount
             FROM tripregistrations TripRegistrations
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
            'DELETE FROM locations
             WHERE LocationId = :LocationId
               AND UserId = :UserId'
        );
        $DeleteLocationStatement->execute([
            'LocationId' => $LocationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        RedirectToAdminWithMessage('Success', 'Locatie is verwijderd.', 'Locations');
    } catch (PDOException $Exception) {
        RedirectToAdminWithMessage('Error', 'De locatie kon niet worden verwijderd.', 'Locations');
    }
}

function GetLocationById(PDO $DatabaseConnection, int $LocationId, int $UserId): ?array
{
    $LocationStatement = $DatabaseConnection->prepare(
        'SELECT LocationId, Name, GooglePlaceId, FormattedAddress, DefaultTripDescription, IsActive
         FROM locations
         WHERE LocationId = :LocationId
           AND UserId = :UserId
         LIMIT 1'
    );
    $LocationStatement->execute([
        'LocationId' => $LocationId,
        'UserId' => $UserId,
    ]);
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

function GetUserCommuteCompensationSettings(PDO $DatabaseConnection, int $UserId): array
{
    $SettingsStatement = $DatabaseConnection->prepare(
        'SELECT IsCommuteCompensationEnabled, CommuteCompensationKilometers
         FROM users
         WHERE UserId = :UserId
         LIMIT 1'
    );
    $SettingsStatement->execute(['UserId' => $UserId]);
    $Settings = $SettingsStatement->fetch();

    return [
        'Enabled' => $Settings && (int)$Settings['IsCommuteCompensationEnabled'] === 1,
        'Kilometers' => $Settings ? max(0.0, (float)$Settings['CommuteCompensationKilometers']) : 20.0,
    ];
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
    RequireLogin();

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
    RequireLogin();

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

function CalculateStoredTripDistanceMeters(array $StartLocation, array $EndLocation, int $IsRoundTrip, int $ApplyCommuteCompensation, int $CommuteCompensationMeters): int
{
    $DistanceMeters = ComputeDrivingDistanceMeters($StartLocation, $EndLocation);

    // Store the full travel distance when the trip is marked as return travel.
    if ($IsRoundTrip === 1) {
        $DistanceMeters *= 2;
    }

    if ($ApplyCommuteCompensation === 1) {
        $DistanceMeters = max(0, $DistanceMeters - $CommuteCompensationMeters);
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
        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId, (int)$_SESSION['UserId']);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId, (int)$_SESSION['UserId']);

        if (!$StartLocation || !$EndLocation) {
            RedirectToDashboardWithMessage('Error', 'Een van de gekozen locaties bestaat niet.');
        }

        if (!IsLocationSelectableForTrip($StartLocation) || !IsLocationSelectableForTrip($EndLocation)) {
            RedirectToDashboardWithMessage('Error', 'Een van de gekozen locaties is niet actief.');
        }

        $CommuteSettings = GetUserCommuteCompensationSettings($DatabaseConnection, (int)$_SESSION['UserId']);
        $ApplyCommuteCompensation = $CommuteSettings['Enabled'] ? $ApplyCommuteCompensation : 0;
        $CommuteCompensationMeters = (int)round($CommuteSettings['Kilometers'] * 1000);
        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation, $CommuteCompensationMeters);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);
        $CreatedAt = date('Y-m-d H:i:s');

        $CreateTripStatement = $DatabaseConnection->prepare(
            'INSERT INTO tripregistrations (UserId, TripDate, StartLocationId, EndLocationId, IsRoundTrip, ApplyCommuteCompensation, TripDescription, DistanceMeters, DistanceKilometers, CreatedAt)
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
             FROM tripregistrations TripRegistrations
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

        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId, (int)$_SESSION['UserId']);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId, (int)$_SESSION['UserId']);

        if (!$StartLocation || !$EndLocation) {
            RedirectToTripsWithMessage('Error', 'Een van de gekozen locaties bestaat niet.');
        }

        if (!IsLocationSelectableForTrip($StartLocation, (int)$ExistingTrip['StartLocationId']) || !IsLocationSelectableForTrip($EndLocation, (int)$ExistingTrip['EndLocationId'])) {
            RedirectToTripsWithMessage('Error', 'Een van de gekozen locaties is niet actief.');
        }

        $CommuteSettings = GetUserCommuteCompensationSettings($DatabaseConnection, (int)$_SESSION['UserId']);
        $ApplyCommuteCompensation = $CommuteSettings['Enabled'] ? $ApplyCommuteCompensation : 0;
        $CommuteCompensationMeters = (int)round($CommuteSettings['Kilometers'] * 1000);
        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation, $CommuteCompensationMeters);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);

        $UpdateTripStatement = $DatabaseConnection->prepare(
            'UPDATE tripregistrations
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
            'DELETE FROM tripregistrations
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

function HandleLoadTripRegistrationsRequest(): void
{
    RequireLogin();

    $Offset = max(0, (int)NormalizePostValue('Offset'));
    $Limit = (int)NormalizePostValue('Limit');

    if ($Limit <= 0) {
        $Limit = 20;
    }

    $Limit = min($Limit, 50);

    try {
        $DatabaseConnection = GetDatabaseConnection();

        $LocationsStatement = $DatabaseConnection->prepare(
            'SELECT LocationId, Name, DefaultTripDescription, IsActive
             FROM locations
             WHERE UserId = :UserId
             ORDER BY Name ASC'
        );
        $LocationsStatement->execute(['UserId' => (int)$_SESSION['UserId']]);
        $Locations = $LocationsStatement->fetchAll();

        $TripsStatement = $DatabaseConnection->prepare(
            'SELECT TripRegistrations.TripRegistrationId, TripRegistrations.TripDate,
                    TripRegistrations.StartLocationId, TripRegistrations.EndLocationId,
                    TripRegistrations.IsRoundTrip, TripRegistrations.ApplyCommuteCompensation,
                    TripRegistrations.TripDescription, TripRegistrations.DistanceKilometers,
                    StartLocations.Name AS StartLocationName,
                    EndLocations.Name AS EndLocationName
             FROM tripregistrations TripRegistrations
             INNER JOIN locations StartLocations ON StartLocations.LocationId = TripRegistrations.StartLocationId
             INNER JOIN locations EndLocations ON EndLocations.LocationId = TripRegistrations.EndLocationId
             WHERE TripRegistrations.UserId = :UserId
             ORDER BY TripRegistrations.TripDate DESC, TripRegistrations.TripRegistrationId DESC
             LIMIT :Limit OFFSET :Offset'
        );
        $TripsStatement->bindValue('UserId', (int)$_SESSION['UserId'], PDO::PARAM_INT);
        $TripsStatement->bindValue('Limit', $Limit + 1, PDO::PARAM_INT);
        $TripsStatement->bindValue('Offset', $Offset, PDO::PARAM_INT);
        $TripsStatement->execute();
        $TripRegistrations = $TripsStatement->fetchAll();
        $HasMoreTrips = count($TripRegistrations) > $Limit;
        $TripRegistrations = array_slice($TripRegistrations, 0, $Limit);
        $CommuteSettings = GetUserCommuteCompensationSettings($DatabaseConnection, (int)$_SESSION['UserId']);

        SendJsonResponse([
            'Ok' => true,
            'Html' => RenderTripOverviewGroups($TripRegistrations, $Locations, $CommuteSettings['Enabled'], $CommuteSettings['Kilometers']),
            'Count' => count($TripRegistrations),
            'NextOffset' => $Offset + count($TripRegistrations),
            'HasMore' => $HasMoreTrips,
        ]);
    } catch (PDOException $Exception) {
        SendJsonResponse([
            'Ok' => false,
            'Error' => 'Ritten konden niet worden geladen.',
        ], 500);
    }
}

function HandleUpdateAdminTripRegistrationRequest(): void
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
             FROM tripregistrations TripRegistrations
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
            RedirectToAdminWithMessage('Error', 'Deze rit bestaat niet of hoort niet bij jouw account.', 'Trips');
        }

        $StartLocation = GetLocationById($DatabaseConnection, $StartLocationId, (int)$_SESSION['UserId']);
        $EndLocation = GetLocationById($DatabaseConnection, $EndLocationId, (int)$_SESSION['UserId']);

        if (!$StartLocation || !$EndLocation) {
            RedirectToAdminWithMessage('Error', 'Een van de gekozen locaties bestaat niet.', 'Trips');
        }

        if (!IsLocationSelectableForTrip($StartLocation, (int)$ExistingTrip['StartLocationId']) || !IsLocationSelectableForTrip($EndLocation, (int)$ExistingTrip['EndLocationId'])) {
            RedirectToAdminWithMessage('Error', 'Een van de gekozen locaties is niet actief.', 'Trips');
        }

        $CommuteSettings = GetUserCommuteCompensationSettings($DatabaseConnection, (int)$_SESSION['UserId']);
        $ApplyCommuteCompensation = $CommuteSettings['Enabled'] ? $ApplyCommuteCompensation : 0;
        $CommuteCompensationMeters = (int)round($CommuteSettings['Kilometers'] * 1000);
        $DistanceMeters = CalculateStoredTripDistanceMeters($StartLocation, $EndLocation, $IsRoundTrip, $ApplyCommuteCompensation, $CommuteCompensationMeters);
        $DistanceKilometers = round($DistanceMeters / 1000, 2);

        $UpdateTripStatement = $DatabaseConnection->prepare(
            'UPDATE tripregistrations
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

        RedirectToAdminWithMessage('Success', 'Rit is bijgewerkt met ' . number_format($DistanceKilometers, 2, ',', '.') . ' km.', 'Trips');
    } catch (Throwable $Exception) {
        RedirectToAdminWithMessage('Error', 'De rit kon niet worden bijgewerkt: ' . $Exception->getMessage(), 'Trips');
    }
}

function HandleDeleteAdminTripRegistrationRequest(): void
{
    RequireLogin();

    $TripRegistrationId = (int)NormalizePostValue('TripRegistrationId');

    if ($TripRegistrationId <= 0) {
        RedirectToAdminWithMessage('Error', 'Kies een geldige rit om te verwijderen.', 'Trips');
    }

    try {
        $DatabaseConnection = GetDatabaseConnection();
        $DeleteTripStatement = $DatabaseConnection->prepare(
            'DELETE FROM tripregistrations
             WHERE TripRegistrationId = :TripRegistrationId
               AND UserId = :UserId'
        );
        $DeleteTripStatement->execute([
            'TripRegistrationId' => $TripRegistrationId,
            'UserId' => (int)$_SESSION['UserId'],
        ]);

        if ($DeleteTripStatement->rowCount() === 0) {
            RedirectToAdminWithMessage('Error', 'Deze rit bestaat niet of hoort niet bij jouw account.', 'Trips');
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

if ($Action === 'CreateAdminUser') {
    HandleCreateAdminUserRequest();
}

if ($Action === 'UpdateAdminUser') {
    HandleUpdateAdminUserRequest();
}

if ($Action === 'UpdateOwnUser') {
    HandleUpdateOwnUserRequest();
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

if ($Action === 'LoadTripRegistrations') {
    HandleLoadTripRegistrationsRequest();
}

if ($Action === 'UpdateAdminTripRegistration') {
    HandleUpdateAdminTripRegistrationRequest();
}

if ($Action === 'DeleteAdminTripRegistration') {
    HandleDeleteAdminTripRegistrationRequest();
}

http_response_code(400);
echo 'Invalid Action';
