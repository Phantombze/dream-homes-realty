<?php
/**
 * Dream Homes Realty - Configuration
 */

// Environment Detection
// Set to 'production' when deployed to live server, 'development' for local work
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');

// Asset Configuration
// In production, use minified assets; in development, use readable source files
define('USE_MINIFIED_ASSETS', ENVIRONMENT === 'production');

/**
 * Get the appropriate CSS file path based on environment
 * @param string $cssFile The base CSS filename (e.g., 'site.css')
 * @return string The CSS file path to use
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
 * @param string $jsFile The base JS filename (e.g., 'script.js')
 * @return string The JS file path to use
 */
function get_js_path(string $jsFile): string {
    if (USE_MINIFIED_ASSETS) {
        // In production: use script.min.js
        return str_replace('.js', '.min.js', $jsFile);
    }
    // In development: use script.js
    return $jsFile;
}

// Database Configuration (add your DB settings here later if needed)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'dreamhomes');
// define('DB_USER', 'root');
// define('DB_PASS', '');
