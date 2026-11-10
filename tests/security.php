<?php
// CLI integration checks against the local Apache instance, using disposable records.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/Security.php';

function Check(bool $Condition, string $Message): void
{
    if (!$Condition) {
        throw new RuntimeException($Message);
    }
}

$CookieFile = tempnam(sys_get_temp_dir(), 'km-security-');
$Curl = curl_init();
curl_setopt_array($Curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $CookieFile, CURLOPT_COOKIEFILE => $CookieFile, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
function Request(string $Path, ?array $Data = null): array
{
    global $Curl;
    curl_setopt($Curl, CURLOPT_URL, 'http://localhost' . $Path);
    curl_setopt($Curl, CURLOPT_POST, $Data !== null);
    if ($Data !== null) {
        curl_setopt($Curl, CURLOPT_POSTFIELDS, http_build_query($Data));
    }
    $Body = curl_exec($Curl);
    Check($Body !== false, 'HTTP request failed');
    return [curl_getinfo($Curl, CURLINFO_HTTP_CODE), $Body];
}

$Suffix = bin2hex(random_bytes(8));
$Action = 'security-test-' . $Suffix;
$AttemptFile = __DIR__ . '/../storage/auth-attempts/' . hash('sha256', $Action . ':fixture') . '.json';
$UserId = null;
$Database = GetDatabaseConnection();
try {
    Check(ConsumeAuthAttempt($Action, 'fixture', 2) === 0, 'First attempt rejected');
    Check(ConsumeAuthAttempt($Action, 'fixture', 2) === 0, 'Second attempt rejected');
    Check(ConsumeAuthAttempt($Action, 'fixture', 2) > 0, 'Attempt limit bypassed');
    file_put_contents($AttemptFile, json_encode(['Count' => 2, 'ExpiresAt' => time() - 1]));
    Check(ConsumeAuthAttempt($Action, 'fixture', 2) === 0, 'Expired block did not reset');

    foreach (['/database/Schema.sql', '/.git/config', '/database-test', '/config/Database.php', '/storage/auth-attempts/test.json', '/tests/security.php', '/updates/private.zip', '/backup.zip', '/config.php.old'] as $Path) {
        Check(Request($Path)[0] === 403, 'Private path accessible: ' . $Path);
    }
    curl_setopt($Curl, CURLOPT_HTTPHEADER, ['Host: security-check.example']);
    Check(Request('/login')[0] === 308, 'Public HTTP host was not redirected to HTTPS');
    curl_setopt($Curl, CURLOPT_HTTPHEADER, []);
    [$Status, $Body] = Request('/login');
    Check($Status === 200 && preg_match('/name="CsrfToken" value="([^"]+)"/', $Body, $Match) === 1, 'Missing form token');
    $Token = $Match[1];
    Check(Request('/api/index.php', ['Action' => 'Logout'])[0] === 403, 'Missing CSRF accepted');
    Check(Request('/api/index.php', ['Action' => 'Logout', 'CsrfToken' => 'invalid'])[0] === 403, 'Invalid CSRF accepted');
    Check(Request('/api/index.php', ['Action' => 'InvalidSecurityTest', 'CsrfToken' => $Token])[0] === 400, 'Valid CSRF rejected');

    $Email = 'security-test-' . $Suffix . '@example.invalid';
    $Password = bin2hex(random_bytes(20));
    $Insert = $Database->prepare('INSERT INTO users (FirstName, LastName, EmailAddress, PasswordHash, IsActive, CreatedAt) VALUES (?, ?, ?, ?, 1, NOW())');
    $Insert->execute(['Security', 'Test', $Email, password_hash($Password, PASSWORD_DEFAULT)]);
    $UserId = (int)$Database->lastInsertId();
    Check(Request('/api/index.php', ['Action' => 'Login', 'CsrfToken' => $Token, 'EmailAddress' => $Email, 'Password' => $Password])[0] === 302, 'Login failed');
    Check(Request('/dashboard')[0] === 200, 'Active session rejected');
    $Database->prepare('UPDATE users SET IsActive = 0 WHERE UserId = ?')->execute([$UserId]);
    Check(Request('/dashboard')[0] === 302, 'Deactivated session retained access');

    $Database->prepare('UPDATE users SET IsActive = 1 WHERE UserId = ?')->execute([$UserId]);
    [, $Body] = Request('/login');
    preg_match('/name="CsrfToken" value="([^"]+)"/', $Body, $Match);
    Request('/api/index.php', ['Action' => 'Login', 'CsrfToken' => $Match[1], 'EmailAddress' => $Email, 'Password' => $Password]);
    Check(Request('/dashboard')[0] === 200, 'Relogin failed');
    $Database->prepare('DELETE FROM users WHERE UserId = ?')->execute([$UserId]);
    Check(Request('/api/index.php', ['Action' => 'LoadTripRegistrations', 'CsrfToken' => $Match[1]])[0] === 302, 'Deleted session retained API access');
    echo "PASS: rate limits, expiry, private paths, CSRF, login, deactivation and deletion.\n";
} finally {
    if ($UserId !== null) {
        $Database->prepare('DELETE FROM users WHERE UserId = ?')->execute([$UserId]);
    }
    curl_close($Curl);
    @unlink($CookieFile);
    @unlink($AttemptFile);
    if (isset($Email)) {
        @unlink(__DIR__ . '/../storage/auth-attempts/' . hash('sha256', 'Login-email:' . strtolower($Email)) . '.json');
    }
}
