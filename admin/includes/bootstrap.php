<?php
define('DREAMHOMES_CONFIG', true);

// Load logger first for error tracking
if (file_exists(__DIR__ . '/../../includes/logger.php')) {
    require_once __DIR__ . '/../../includes/logger.php';
}

// Load global error handler to catch all errors
if (file_exists(__DIR__ . '/../../includes/error-handler.php')) {
    require_once __DIR__ . '/../../includes/error-handler.php';
}

// Log bootstrap start
if (function_exists('dh_log_debug')) {
    dh_log_debug('Bootstrap initiated', [
        'script' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

try {
    require_once __DIR__ . '/../../../private/dh-config.php';
} catch (Throwable $e) {
    if (function_exists('dh_log_exception')) {
        dh_log_exception($e, 'Failed to load dh-config.php');
    }
    throw $e;
}

try {
    require_once __DIR__ . '/../../includes/site-settings.php';
} catch (Throwable $e) {
    if (function_exists('dh_log_exception')) {
        dh_log_exception($e, 'Failed to load site-settings.php');
    }
    throw $e;
}

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$autoloadCandidates = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    $docRoot !== '' ? rtrim($docRoot, '/\\') . '/vendor/autoload.php' : '',
];

$autoloadFound = false;
foreach ($autoloadCandidates as $autoload) {
    if ($autoload !== '' && file_exists($autoload)) {
        require_once $autoload;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    $errorMsg = 'WARNING: No autoload.php found. Checked: ' . json_encode($autoloadCandidates);
    error_log($errorMsg);

    if (function_exists('dh_log_error')) {
        dh_log_error('Composer autoload not found', [
            'checked_paths' => $autoloadCandidates,
            'document_root' => $docRoot,
            'current_dir' => __DIR__
        ]);
    }
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$inactivityTimeout = 3600;
$absoluteTimeout = 86400;

if (!isset($_SESSION['admin_session_started'])) {
    $_SESSION['admin_session_started'] = time();
}

$now = time();
$lastActivity = $_SESSION['admin_last_activity'] ?? $now;

// Skip timeout checks on POST to allow form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (($now - $lastActivity) > $inactivityTimeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['admin_session_started'] = time();
    } elseif (($now - $_SESSION['admin_session_started']) > $absoluteTimeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['admin_session_started'] = time();
    }
}

$_SESSION['admin_last_activity'] = $now;

// Always ensure CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    $token = trim($_POST['csrf_token'] ?? '');
    $sessionToken = trim($_SESSION['csrf_token'] ?? '');
    
    // Log for debugging
    error_log('CSRF Check: POST has token: ' . ($token !== '' ? 'yes' : 'no') . ', Session has token: ' . ($sessionToken !== '' ? 'yes' : 'no'));
    error_log('Session ID: ' . session_id());
    error_log('Session data present keys: ' . implode(', ', array_keys($_SESSION)));
    
    if ($token === '' || $sessionToken === '') {
        http_response_code(403);
        error_log('CSRF FAIL: Token missing');
        die('Invalid CSRF token. Please try again.');
    }
    
    if (!hash_equals($sessionToken, $token)) {
        http_response_code(403);
        error_log('CSRF FAIL: Mismatch - Session: ' . substr($sessionToken, 0, 16) . ' vs POST: ' . substr($token, 0, 16));
        die('Invalid CSRF token. Please try again.');
    }
    
    error_log('CSRF OK: Validated');
}
