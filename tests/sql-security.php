<?php
// Run production handlers against an isolated in-memory database, without web traffic.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

class TestRedirect extends RuntimeException {}
function NormalizePostValue(string $Key): string { return trim((string)($_POST[$Key] ?? '')); }
function RequireLogin(): void { if (empty($_SESSION['UserId'])) { throw new TestRedirect('login'); } }
function RedirectToRegisterWithError(string $Message, array $OldInput = []): void { throw new TestRedirect($Message); }
function RedirectToAdminWithMessage(string $Type, string $Message, string $View = ''): void { throw new TestRedirect($Message); }
function GetDatabaseConnection(): PDO { global $Database; return $Database; }
function Check(bool $Condition, string $Message): void { if (!$Condition) { throw new RuntimeException($Message); } }

$Source = file_get_contents(__DIR__ . '/../api/index.php');
foreach (['HandleRegisterRequest' => 'HandleCreateAdminUserRequest', 'HandleDeleteLocationRequest' => 'GetLocationById'] as $Start => $End) {
    $StartOffset = strpos($Source, 'function ' . $Start . '(');
    $EndOffset = strpos($Source, 'function ' . $End . '(', $StartOffset);
    Check($StartOffset !== false && $EndOffset !== false, 'Production handler not found');
    eval(substr($Source, $StartOffset, $EndOffset - $StartOffset));
}

$Database = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$Database->exec('CREATE TABLE users (UserId INTEGER, EmailAddress TEXT)');
$Database->exec('CREATE TABLE locations (LocationId INTEGER, UserId INTEGER)');
$Database->exec('CREATE TABLE tripregistrations (UserId INTEGER, StartLocationId INTEGER, EndLocationId INTEGER)');
$Database->exec('INSERT INTO locations VALUES (1, 1), (2, 2)');
$Database->exec('INSERT INTO tripregistrations VALUES (2, 2, 2)');
$_SESSION = ['UserId' => 1];

foreach (['test@example.org', 'test@sub.vonknh.nl', 'test@vonknh.nl.example.org', "test@vonknh.nl' OR '1'='1"] as $Email) {
    $_POST = ['FirstName' => 'Test', 'LastName' => 'Test', 'EmailAddress' => $Email, 'Password' => 'Test-password-123'];
    try { HandleRegisterRequest(); throw new RuntimeException('Invalid domain accepted'); }
    catch (TestRedirect $Redirect) {
        Check(in_array($Redirect->getMessage(), ['Vul een geldig e-mailadres in.', 'Je kunt je alleen met een bedrijfsaccount aanmelden.'], true), 'Unexpected registration error');
    }
}

// The valid domain reaches the duplicate check; an apostrophe remains literal data.
$Email = "o'connor@VONKNH.NL";
$Database->prepare('INSERT INTO users VALUES (1, ?)')->execute([$Email]);
$_POST['EmailAddress'] = $Email;
try { HandleRegisterRequest(); throw new RuntimeException('Duplicate not detected'); }
catch (TestRedirect $Redirect) { Check($Redirect->getMessage() === 'Er bestaat al een account met dit e-mailadres.', 'Valid company email rejected'); }

// An ID belonging to another user cannot reveal whether that location has trips.
foreach (['2', '2 OR 1=1', '2; DROP TABLE locations'] as $LocationId) {
    $_POST = ['LocationId' => $LocationId];
    try { HandleDeleteLocationRequest(); }
    catch (TestRedirect $Redirect) { Check($Redirect->getMessage() === 'Locatie is verwijderd.', 'Foreign trip usage leaked'); }
    Check((int)$Database->query('SELECT COUNT(*) FROM locations')->fetchColumn() === 2, 'Foreign location modified');
}
$_POST = ['LocationId' => '1'];
try { HandleDeleteLocationRequest(); } catch (TestRedirect $Redirect) {}
Check((int)$Database->query('SELECT COUNT(*) FROM locations WHERE UserId = 1')->fetchColumn() === 0, 'Own unused location cannot be deleted');
echo "PASS: registration domains, bound apostrophe, injection IDs and location isolation.\n";
