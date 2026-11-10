<?php

// Local defaults; configure hosting credentials here or through environment variables.
define('DatabaseHost', getenv('KM_DB_HOST') !== false ? getenv('KM_DB_HOST') : 'localhost');
define('DatabaseName', getenv('KM_DB_NAME') !== false ? getenv('KM_DB_NAME') : 'ai-test');
define('DatabaseUser', getenv('KM_DB_USER') !== false ? getenv('KM_DB_USER') : 'root');
define('DatabasePassword', getenv('KM_DB_PASSWORD') !== false ? getenv('KM_DB_PASSWORD') : '');
const DatabaseCharset = 'utf8mb4';

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
