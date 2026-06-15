<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';

header('Content-Type: text/html; charset=utf-8');

function EscapeDatabaseTestValue(string $Value): string
{
    return htmlspecialchars($Value, ENT_QUOTES, 'UTF-8');
}

function RenderDatabaseTestRow(string $Label, string $Value): void
{
    echo '<tr><th>' . EscapeDatabaseTestValue($Label) . '</th><td>' . EscapeDatabaseTestValue($Value) . '</td></tr>';
}

$Status = 'Error';
$StatusText = 'Databaseverbinding is niet gelukt.';
$Details = [];
$ErrorMessage = '';

try {
    if (!extension_loaded('pdo')) {
        throw new RuntimeException('De PHP PDO extensie is niet geladen.');
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('De PHP PDO MySQL extensie is niet geladen.');
    }

    $DatabaseConnection = GetDatabaseConnection();
    $ProbeStatement = $DatabaseConnection->query(
        'SELECT DATABASE() AS DatabaseName, VERSION() AS MysqlVersion, CURRENT_USER() AS CurrentUser'
    );
    $ProbeResult = $ProbeStatement ? $ProbeStatement->fetch() : null;

    $TableStatement = $DatabaseConnection->query('SHOW TABLES');
    $Tables = $TableStatement ? $TableStatement->fetchAll(PDO::FETCH_COLUMN) : [];

    $Status = 'Success';
    $StatusText = 'Databaseverbinding is gelukt.';
    $Details = [
        'Verbonden database' => (string)($ProbeResult['DatabaseName'] ?? ''),
        'MySQL versie' => (string)($ProbeResult['MysqlVersion'] ?? ''),
        'MySQL gebruiker' => (string)($ProbeResult['CurrentUser'] ?? ''),
        'Aantal tabellen' => (string)count($Tables),
        'Tabellen' => $Tables ? implode(', ', array_map('strval', $Tables)) : 'Geen tabellen gevonden',
    ];
} catch (Throwable $Exception) {
    $ErrorMessage = $Exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="Controleer of de databaseverbinding van KM2WORK werkt en bekijk technische database-instellingen.">
  <title>Database test | KM2WORK</title>
  <style>
    body {
      background: #0d0f14;
      color: #f8fafc;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 32px;
    }

    main {
      margin: 0 auto;
      max-width: 820px;
    }

    section {
      background: #151821;
      border: 1px solid #2a3040;
      border-radius: 10px;
      margin-top: 16px;
      padding: 18px;
    }

    h1 {
      font-size: 28px;
      margin: 0 0 10px;
    }

    .Status {
      border-color: #ef4444;
      color: #fecaca;
      font-weight: 700;
    }

    .Status.IsSuccess {
      border-color: #22c55e;
      color: #bbf7d0;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      border-top: 1px solid #2a3040;
      padding: 10px 0;
      text-align: left;
      vertical-align: top;
    }

    tr:first-child th,
    tr:first-child td {
      border-top: 0;
    }

    th {
      color: #94a3b8;
      width: 210px;
    }

    code {
      background: #0b0d12;
      border: 1px solid #2a3040;
      border-radius: 6px;
      color: #fbbf24;
      display: block;
      line-height: 1.45;
      overflow-x: auto;
      padding: 12px;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  <main>
    <h1>Database test</h1>

    <section class="Status <?= $Status === 'Success' ? 'IsSuccess' : '' ?>">
      <?= EscapeDatabaseTestValue($StatusText) ?>
    </section>

    <section>
      <h2>Gebruikte instellingen</h2>
      <table>
        <?php RenderDatabaseTestRow('Host', DatabaseHost); ?>
        <?php RenderDatabaseTestRow('Database', DatabaseName); ?>
        <?php RenderDatabaseTestRow('Gebruiker', DatabaseUser); ?>
        <?php RenderDatabaseTestRow('Charset', DatabaseCharset); ?>
        <?php RenderDatabaseTestRow('Wachtwoord', DatabasePassword === '' ? 'Leeg wachtwoord' : 'Ingevuld, niet getoond'); ?>
      </table>
    </section>

    <?php if ($Status === 'Success'): ?>
      <section>
        <h2>Resultaat</h2>
        <table>
          <?php foreach ($Details as $Label => $Value): ?>
            <?php RenderDatabaseTestRow((string)$Label, (string)$Value); ?>
          <?php endforeach; ?>
        </table>
      </section>
    <?php else: ?>
      <section>
        <h2>Foutmelding</h2>
        <code><?= EscapeDatabaseTestValue($ErrorMessage !== '' ? $ErrorMessage : 'Onbekende fout') ?></code>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
