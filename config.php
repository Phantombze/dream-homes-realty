<?php
/**
 * Dream Homes Realty - Main Configuration
 *
 * This file loads sensitive credentials from private/dh-config.php
 * and provides environment-based asset management.
 */

// Load private configuration (database, API keys, etc.)
// This file is NOT tracked in Git for security
$privateConfigPath = __DIR__ . '/private/dh-config.php';
if (file_exists($privateConfigPath)) {
    require_once $privateConfigPath;
} else {
    // Fallback for development/cloned repos without private config
    // Create private/dh-config.php with your actual credentials
    if (!defined('DREAMHOMES_CONFIG')) {
        trigger_error(
            'Missing private/dh-config.php - Please create this file with your database and API credentials. ' .
            'See private/dh-config.example.php for template.',
            E_USER_WARNING
        );

        // Define placeholder constants to prevent errors
        if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
        if (!defined('DB_NAME')) define('DB_NAME', 'dreamhomes_db');
        if (!defined('DB_USER')) define('DB_USER', 'root');
        if (!defined('DB_PASS')) define('DB_PASS', '');
        if (!defined('ADMIN_SESSION_KEY')) define('ADMIN_SESSION_KEY', 'change-this-in-production');
        if (!defined('CLERK_PUBLISHABLE_KEY')) define('CLERK_PUBLISHABLE_KEY', '');
        if (!defined('CLERK_SECRET_KEY')) define('CLERK_SECRET_KEY', '');
        if (!defined('CLERK_ISSUER')) define('CLERK_ISSUER', '');
        if (!defined('CLERK_JWKS_URL')) define('CLERK_JWKS_URL', '');
        if (!defined('CLERK_SIGN_IN_URL')) define('CLERK_SIGN_IN_URL', '');
        if (!defined('CLERK_ALLOWED_EMAILS')) define('CLERK_ALLOWED_EMAILS', []);
        if (!defined('TURNSTILE_SECRET_KEY')) define('TURNSTILE_SECRET_KEY', '');
    }
}

// Environment Detection
// Set to 'production' when deployed to live server, 'development' for local work
// Can be set via .htaccess: SetEnv ENVIRONMENT production
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');
}

// Asset Configuration
// In production, use minified assets; in development, use readable source files
if (!defined('USE_MINIFIED_ASSETS')) {
    define('USE_MINIFIED_ASSETS', ENVIRONMENT === 'production');
}

/**
 * Get the appropriate CSS file path based on environment
 * @param string $cssFile The base CSS filename (e.g., 'site.css' or 'assets/css/site.css')
 * @return string The CSS file path to use (site.min.css in production, site.css in development)
 */
function get_css_path(string $cssFile): string {
    if (USE_MINIFIED_ASSETS) {
        // In production: use site.min.css
        return str_replace('.css', '.min.css', $cssFile);
    }
    // In development: use site.css
    return $cssFile;
}

/**
 * Get the appropriate JS file path based on environment
 * @param string $jsFile The base JS filename (e.g., 'script.js' or 'assets/js/script.js')
 * @return string The JS file path to use (script.min.js in production, script.js in development)
 */
function get_js_path(string $jsFile): string {
    if (USE_MINIFIED_ASSETS) {
        // In production: use script.min.js
        return str_replace('.js', '.min.js', $jsFile);
    }
    // In development: use script.js
    return $jsFile;
}

/**
 * Get database connection
 * @return PDO Database connection
 * @throws PDOException if connection fails
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Check your credentials in private/dh-config.php");
        }
    }

    return $pdo;
}

// Additional Configuration
define('SITE_NAME', 'Dream Homes Realty');
define('SITE_URL', ENVIRONMENT === 'production' ? 'https://yourdomain.com' : 'http://localhost');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
