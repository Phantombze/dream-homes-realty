<?php
require_once __DIR__ . '/includes/auth.php';

$logFile = __DIR__ . '/../../private/clerk-auth.log';
function clerk_log(string $message): void {
    global $logFile;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $ip . ' ' . $message . "\n";
    $written = file_put_contents($logFile, $line, FILE_APPEND);
    if ($written === false) {
        error_log('Failed to write to log: ' . $logFile);
    }
}

// Initial debug log
clerk_log('clerk-session.php loaded. Checking paths...');
clerk_log('__DIR__ = ' . __DIR__);
clerk_log('Autoload test: ' . (class_exists('Firebase\JWT\JWT') ? 'Firebase JWT class FOUND' : 'Firebase JWT class NOT FOUND'));
clerk_log('private dir exists: ' . (is_dir(__DIR__ . '/../../private') ? 'YES' : 'NO'));
clerk_log('Log file: ' . $logFile);

// Check vendor paths
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$vendorCandidates = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    $docRoot !== '' ? rtrim($docRoot, '/\\') . '/vendor/autoload.php' : '',
];
clerk_log('DOCUMENT_ROOT: ' . ($docRoot ?: 'empty'));
foreach ($vendorCandidates as $i => $path) {
    $exists = ($path !== '' && file_exists($path)) ? 'YES' : 'NO';
    clerk_log("  Vendor[{$i}]: {$path} (exists: {$exists})");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    clerk_log('Invalid method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrfHeader) || $csrfHeader === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    clerk_log('Invalid CSRF token.');
    http_response_code(403);
    echo 'Invalid CSRF token.';
    exit;
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true);
$token = is_array($payload) ? ($payload['token'] ?? '') : '';
$token = is_string($token) ? $token : '';

if ($token === '') {
    clerk_log('Missing session token.');
    http_response_code(400);
    echo 'Missing token.';
    exit;
}

try {
    $userId = clerk_verify_session_token($token);
    if ($userId === null) {
        clerk_log('Invalid session token.');
        http_response_code(401);
        echo 'Invalid session token.';
        exit;
    }

    $email = clerk_fetch_user_email($userId);
    if ($email === null || !clerk_email_allowed($email)) {
        clerk_log('Not authorized for user: ' . ($email ?? 'unknown'));
        http_response_code(403);
        echo 'Not authorized.';
        exit;
    }

    session_regenerate_id(true);
    $_SESSION[ADMIN_SESSION_KEY] = $userId;
    $_SESSION['admin_email'] = $email;

    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    clerk_log('Server error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Server error.';
}
