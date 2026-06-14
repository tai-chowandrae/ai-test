<?php

const DatabaseHost = 'localhost';
const DatabaseName = 'ai-test';
const DatabaseUser = 'root';
const DatabasePassword = '';
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
    ]);
}
