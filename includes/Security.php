<?php
// Keep SQL errors, paths and stack traces out of HTTP responses.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../config/Database.php';

// Only trust TLS reported by the web server, not client-supplied proxy headers.
$IsHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'secure' => $IsHttps,
    'httponly' => true,
    'samesite' => 'Lax',
    'path' => '/',
]);
if (!session_start()) {
    http_response_code(503);
    exit('Aanmelden is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
if ($IsHttps) {
    header('Strict-Transport-Security: max-age=31536000');
}

function CsrfField(): string
{
    return '<input type="hidden" name="CsrfToken" value="' . htmlspecialchars($_SESSION['CsrfToken'], ENT_QUOTES, 'UTF-8') . '">';
}

// Recheck permissions on every request so old sessions cannot retain access.
if (!empty($_SESSION['UserId'])) {
    try {
        $SecurityStatement = GetDatabaseConnection()->prepare('SELECT IsActive, IsAdmin FROM users WHERE UserId = :UserId');
        $SecurityStatement->execute(['UserId' => (int)$_SESSION['UserId']]);
        $SecurityUser = $SecurityStatement->fetch();
        if (!$SecurityUser || (int)$SecurityUser['IsActive'] !== 1) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['LoginError'] = 'Je account is niet meer actief. Log opnieuw in of neem contact op met de beheerder.';
            header('Location: /login', true, 302);
            exit;
        }
        $_SESSION['IsAdmin'] = (int)$SecurityUser['IsAdmin'];
    } catch (PDOException $Exception) {
        http_response_code(503);
        exit('De toegang kan nu niet worden gecontroleerd. Probeer het later opnieuw.');
    }
}

if (empty($_SESSION['CsrfToken'])) {
    $_SESSION['CsrfToken'] = bin2hex(random_bytes(32));
}

function ValidateCsrfToken(): void
{
    $Token = $_POST['CsrfToken'] ?? '';
    if (!is_string($Token) || !hash_equals($_SESSION['CsrfToken'], $Token)) {
        http_response_code(403);
        exit('Ongeldige of verlopen aanvraag. Vernieuw de pagina en probeer opnieuw.');
    }
}

// Shared across sessions, with a file lock to prevent concurrent request bypasses.
function ConsumeAuthAttempt(string $Action, string $Identity, int $Limit): int
{
    $Directory = __DIR__ . '/../storage/auth-attempts';
    if (!is_dir($Directory) && !@mkdir($Directory, 0700, true) && !is_dir($Directory)) {
        throw new RuntimeException('Attempt storage unavailable');
    }
    $File = $Directory . '/' . hash('sha256', $Action . ':' . $Identity) . '.json';
    $Handle = @fopen($File, 'c+');
    if (!$Handle) {
        throw new RuntimeException('Attempt storage unavailable');
    }
    try {
        if (!flock($Handle, LOCK_EX)) {
            throw new RuntimeException('Attempt lock unavailable');
        }
        $Now = time();
        $State = json_decode(stream_get_contents($Handle), true);
        if (!is_array($State) || ($State['ExpiresAt'] ?? 0) <= $Now) {
            $State = ['Count' => 0, 'ExpiresAt' => $Now + 900];
        }
        if ($State['Count'] >= $Limit) {
            return max(1, $State['ExpiresAt'] - $Now);
        }
        $State['Count']++;
        rewind($Handle);
        if (!ftruncate($Handle, 0) || fwrite($Handle, json_encode($State)) === false || !fflush($Handle)) {
            throw new RuntimeException('Attempt storage write failed');
        }
        return 0;
    } finally {
        flock($Handle, LOCK_UN);
        fclose($Handle);
    }
}

function LimitAuthAttempts(string $Action): void
{
    try {
        $IpWait = ConsumeAuthAttempt($Action . '-ip', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $Action === 'Register' ? 5 : 20);
        $EmailAddress = $_POST['EmailAddress'] ?? '';
        $EmailWait = $IpWait === 0 && is_string($EmailAddress)
            ? ConsumeAuthAttempt($Action . '-email', strtolower(trim($EmailAddress)), $Action === 'Register' ? 3 : 10)
            : $IpWait;
        $Wait = max($IpWait, $EmailWait);
    } catch (RuntimeException $Exception) {
        http_response_code(503);
        exit('Aanmelden is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
    }
    if ($Wait > 0) {
        header('Retry-After: ' . $Wait);
        $Message = 'Te veel pogingen. Probeer het over ' . (int)ceil($Wait / 60) . ' minuten opnieuw.';
        if ($Action === 'Register') {
            RedirectToRegisterWithError($Message);
        }
        RedirectToLoginWithError($Message);
    }
}
