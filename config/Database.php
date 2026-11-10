<?php

// Set to 'live' on hosting, or set KM_APP_ENV there. Never select by HTTP_HOST.
$DatabaseEnvironment = getenv('KM_APP_ENV') !== false ? getenv('KM_APP_ENV') : 'local';

// Keep local and hosting settings separate so switching does not overwrite either.
$DatabaseSettings = [
    'local' => [
        'Host' => 'localhost',
        'Name' => 'ai-test',
        'User' => 'root',
        'Password' => '',
    ],
    'live' => [
        'Host' => '',
        'Name' => '',
        'User' => '',
        'Password' => '',
    ],
];

if (!isset($DatabaseSettings[$DatabaseEnvironment])) {
    http_response_code(503);
    exit('Ongeldige databaseomgeving. Kies local of live in de configuratie.');
}

$ActiveDatabaseSettings = $DatabaseSettings[$DatabaseEnvironment];
// Environment variables still take priority over the selected settings.
foreach (['Host' => 'KM_DB_HOST', 'Name' => 'KM_DB_NAME', 'User' => 'KM_DB_USER', 'Password' => 'KM_DB_PASSWORD'] as $Setting => $Variable) {
    $EnvironmentValue = getenv($Variable);
    if ($EnvironmentValue !== false) {
        $ActiveDatabaseSettings[$Setting] = $EnvironmentValue;
    }
}

if ($DatabaseEnvironment === 'live' && in_array('', $ActiveDatabaseSettings, true)) {
    http_response_code(503);
    exit('De live databasegegevens zijn nog niet volledig ingesteld.');
}

define('DatabaseHost', $ActiveDatabaseSettings['Host']);
define('DatabaseName', $ActiveDatabaseSettings['Name']);
define('DatabaseUser', $ActiveDatabaseSettings['User']);
define('DatabasePassword', $ActiveDatabaseSettings['Password']);
const DatabaseCharset = 'utf8mb4';
unset($DatabaseSettings, $ActiveDatabaseSettings, $EnvironmentValue);

function GetDatabaseConnection(): PDO
{
    $DataSourceName = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DatabaseHost,
        DatabaseName,
        DatabaseCharset
    );

    return new PDO($DataSourceName, DatabaseUser, DatabasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]);
}
