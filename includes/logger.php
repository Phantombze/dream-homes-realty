<?php
/**
 * Dream Homes Realty - Centralized Logging System
 *
 * Comprehensive logging to track errors, warnings, and debug info
 * Logs stored in: /home/www.dreamhomes-realty.com/private/logs/
 */

class DreamHomesLogger {
    private static $instance = null;
    private $logDir;
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 5; // Keep 5 rotated log files

    // Log levels
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const CRITICAL = 'CRITICAL';

    private function __construct() {
        // Log directory: logs/ in root (public but protected with .htaccess)
        $this->logDir = __DIR__ . '/../logs/';

        // Create logs directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0777, true);
            @chmod($this->logDir, 0777); // Ensure writable
        }

        // Create .htaccess to block direct access
        $htaccessPath = $this->logDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccess = "# Deny all web access to log files\n";
            $htaccess .= "Order deny,allow\n";
            $htaccess .= "Deny from all\n";
            @file_put_contents($htaccessPath, $htaccess);
            @chmod($htaccessPath, 0644);
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log an error with full context
     */
    public function error(string $message, array $context = []): void {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log a critical error (system failure)
     */
    public function critical(string $message, array $context = []): void {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log a warning
     */
    public function warning(string $message, array $context = []): void {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log informational message
     */
    public function info(string $message, array $context = []): void {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug information
     */
    public function debug(string $message, array $context = []): void {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log an exception with full stack trace
     */
    public function exception(Throwable $e, string $additionalContext = ''): void {
        $context = [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'additional' => $additionalContext
        ];

        $this->log(self::ERROR, 'Exception caught: ' . $e->getMessage(), $context);
    }

    /**
     * Log database errors
     */
    public function dbError(string $query, string $error, array $params = []): void {
        $context = [
            'query' => $query,
            'error' => $error,
            'params' => $params,
            'db_host' => defined('DB_HOST') ? DB_HOST : 'unknown',
            'db_name' => defined('DB_NAME') ? DB_NAME : 'unknown'
        ];

        $this->log(self::ERROR, 'Database Error', $context);
    }

    /**
     * Log authentication failures
     */
    public function authFailure(string $reason, array $context = []): void {
        $context['auth_failure_reason'] = $reason;
        $this->log(self::WARNING, 'Authentication Failed', $context);
    }

    /**
     * Log file upload issues
     */
    public function uploadError(string $filename, string $error, array $fileInfo = []): void {
        $context = [
            'filename' => $filename,
            'error' => $error,
            'file_info' => $fileInfo
        ];

        $this->log(self::ERROR, 'File Upload Error', $context);
    }

    /**
     * Main logging method
     */
    private function log(string $level, string $message, array $context = []): void {
        try {
            $logFile = $this->getLogFilePath();

            // Rotate log if needed
            $this->rotateLogIfNeeded($logFile);

            // Build log entry
            $entry = $this->buildLogEntry($level, $message, $context);

            // Write to file
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

            // Also write to PHP error log for critical errors
            if ($level === self::CRITICAL || $level === self::ERROR) {
                error_log("[DreamHomes $level] $message");
            }
        } catch (Throwable $e) {
            // Fallback to PHP error log if logging fails
            error_log("Logger failed: " . $e->getMessage());
            error_log("Original message: [$level] $message");
        }
    }

    /**
     * Build formatted log entry
     */
    private function buildLogEntry(string $level, string $message, array $context): string {
        $timestamp = date('Y-m-d H:i:s');
        $requestId = $this->getRequestId();

        // Request context
        $requestContext = [
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
        ];

        // Merge with provided context
        $fullContext = array_merge($requestContext, $context);

        // Build entry
        $entry = str_repeat('=', 80) . "\n";
        $entry .= "[$timestamp] [$level] [Request: $requestId]\n";
        $entry .= "MESSAGE: $message\n";

        // Add context details
        if (!empty($fullContext)) {
            $entry .= "CONTEXT:\n";
            foreach ($fullContext as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $entry .= "  $key: " . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                } else {
                    $entry .= "  $key: $value\n";
                }
            }
        }

        // Add memory usage
        $entry .= "MEMORY: " . $this->formatBytes(memory_get_usage(true)) . "\n";

        $entry .= str_repeat('-', 80) . "\n\n";

        return $entry;
    }

    /**
     * Get current log file path
     */
    private function getLogFilePath(): string {
        $date = date('Y-m-d');
        return $this->logDir . "dreamhomes-{$date}.log";
    }

    /**
     * Rotate log file if it exceeds max size
     */
    private function rotateLogIfNeeded(string $logFile): void {
        if (!file_exists($logFile)) {
            return;
        }

        if (filesize($logFile) < $this->maxFileSize) {
            return;
        }

        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                if ($i + 1 > $this->maxFiles) {
                    unlink($oldFile); // Delete oldest
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Rotate current log
        rename($logFile, $logFile . '.1');
    }

    /**
     * Get unique request ID for tracking related logs
     */
    private function getRequestId(): string {
        static $requestId = null;

        if ($requestId === null) {
            $requestId = substr(md5(uniqid('', true)), 0, 8);
        }

        return $requestId;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get recent log entries (for admin viewing)
     */
    public function getRecentLogs(int $lines = 100): array {
        $logFile = $this->getLogFilePath();

        if (!file_exists($logFile)) {
            return [];
        }

        $content = file_get_contents($logFile);
        $entries = explode(str_repeat('=', 80), $content);

        // Get last N entries
        return array_slice(array_filter($entries), -$lines);
    }

    /**
     * Clear old log files (older than 30 days)
     */
    public function cleanOldLogs(int $daysToKeep = 30): int {
        $deleted = 0;
        $files = glob($this->logDir . '*.log*');
        $cutoffTime = time() - ($daysToKeep * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}

/**
 * Global helper functions for easy logging
 */
function dh_log_error(string $message, array $context = []): void {
    DreamHomesLogger::getInstance()->error($message, $context);
}

function dh_log_warning(string $message, array $context = []): void {
    DreamHomesLogger::getInstance()->warning($message, $context);
}

function dh_log_info(string $message, array $context = []): void {
    DreamHomesLogger::getInstance()->info($message, $context);
}

function dh_log_debug(string $message, array $context = []): void {
    DreamHomesLogger::getInstance()->debug($message, $context);
}

function dh_log_exception(Throwable $e, string $context = ''): void {
    DreamHomesLogger::getInstance()->exception($e, $context);
}

function dh_log_db_error(string $query, string $error, array $params = []): void {
    DreamHomesLogger::getInstance()->dbError($query, $error, $params);
}
