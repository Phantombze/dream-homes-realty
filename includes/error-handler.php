<?php
/**
 * Global Error Handler for Dream Homes Realty
 *
 * Catches and logs all PHP errors, warnings, and exceptions
 */

// Load logger
require_once __DIR__ . '/logger.php';

/**
 * Custom error handler - catches E_WARNING, E_NOTICE, etc.
 */
function dreamhomes_error_handler($errno, $errstr, $errfile, $errline) {
    $logger = DreamHomesLogger::getInstance();

    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED'
    ];

    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';

    $logger->error("PHP $errorType: $errstr", [
        'errno' => $errno,
        'file' => $errfile,
        'line' => $errline,
        'error_type' => $errorType
    ]);

    // Don't execute PHP's internal error handler
    return false;
}

/**
 * Custom exception handler - catches uncaught exceptions
 */
function dreamhomes_exception_handler($exception) {
    $logger = DreamHomesLogger::getInstance();
    $logger->exception($exception, 'Uncaught exception');

    // Show user-friendly error in production
    if (ENVIRONMENT === 'production') {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - Dream Homes Realty</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f5f5f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .error-box {
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 500px;
                    text-align: center;
                }
                h1 {
                    color: #dc3545;
                    margin: 0 0 20px 0;
                }
                p {
                    color: #666;
                    line-height: 1.6;
                }
                .error-id {
                    margin-top: 20px;
                    padding: 10px;
                    background: #f8f9fa;
                    border-radius: 4px;
                    font-size: 0.85em;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>Something went wrong</h1>
                <p>We're sorry, but something unexpected happened. Our team has been notified and is working to fix the issue.</p>
                <p>Please try again in a few moments.</p>
                <div class="error-id">
                    Error Reference: <?php echo date('YmdHis'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // In development, show the actual error
        echo '<pre>';
        echo 'Uncaught Exception: ' . $exception->getMessage() . "\n";
        echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n";
        echo 'Stack trace:' . "\n" . $exception->getTraceAsString();
        echo '</pre>';
        exit;
    }
}

/**
 * Shutdown handler - catches fatal errors
 */
function dreamhomes_shutdown_handler() {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logger = DreamHomesLogger::getInstance();

        $logger->critical('Fatal Error Occurred', [
            'type' => $error['type'],
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
}

// Register error handlers
set_error_handler('dreamhomes_error_handler');
set_exception_handler('dreamhomes_exception_handler');
register_shutdown_function('dreamhomes_shutdown_handler');
