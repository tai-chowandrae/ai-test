<?php

/**
 Database local setting 
 */
const DatabaseHost = 'localhost';
const DatabaseName = 'ai-test';
const DatabaseUser = 'root';
const DatabasePassword = '';
const DatabaseCharset = 'utf8mb4';


/**
 Database mijnkilometerregistratie.nl setting 


const DatabaseHost = 'mijnm4-versie1.db.transip.me';
const DatabaseName = 'mijnm4_versie1';
const DatabaseUser = 'mijnm4_housing';
const DatabasePassword = 'Mn4qYvtc%vd8bcI@';
const DatabaseCharset = 'utf8mb4';

 */

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
