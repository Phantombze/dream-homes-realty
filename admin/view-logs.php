<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

require_once __DIR__ . '/../includes/logger.php';
$logger = DreamHomesLogger::getInstance();

// Handle log cleanup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clean_logs'])) {
    require_once __DIR__ . '/includes/bootstrap.php';
    require_csrf();
    $deleted = $logger->cleanOldLogs(30);
    $message = "Deleted $deleted old log files.";
}

// Get recent logs
$recentLogs = $logger->getRecentLogs(50);

// Get current log file path
$logDir = __DIR__ . '/../logs/';
$currentLogFile = $logDir . 'dreamhomes-' . date('Y-m-d') . '.log';
$logExists = file_exists($currentLogFile);
$logSize = $logExists ? filesize($currentLogFile) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-header">
    <h1 class="admin-title">System Logs</h1>
    <div>
        <?php if ($logExists): ?>
            <span class="log-size">Current log: <?php echo number_format($logSize / 1024, 2); ?> KB</span>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="admin-notice success">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<div class="admin-stack">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Log File Information</h2>
        </div>
        <div class="admin-card-body">
            <table class="log-info-table">
                <tr>
                    <th>Log Directory:</th>
                    <td><code><?php echo htmlspecialchars($logDir, ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
                <tr>
                    <th>Current Log File:</th>
                    <td><code><?php echo htmlspecialchars(basename($currentLogFile), ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
                <tr>
                    <th>File Size:</th>
                    <td><?php echo $logExists ? number_format($logSize / 1024, 2) . ' KB' : 'No logs yet'; ?></td>
                </tr>
                <tr>
                    <th>Available Log Files:</th>
                    <td>
                        <?php
                        $logFiles = glob($logDir . '*.log*');
                        echo count($logFiles) . ' file(s)';
                        ?>
                    </td>
                </tr>
            </table>

            <form method="post" style="margin-top: 16px;" onsubmit="return confirm('Delete logs older than 30 days?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="clean_logs" value="1">
                <button type="submit" class="admin-btn secondary small">Clean Old Logs (30+ days)</button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Recent Log Entries (Last 50)</h2>
        </div>
        <div class="admin-card-body">
            <?php if (empty($recentLogs)): ?>
                <p class="admin-hint">No log entries yet. The logger is active and will record errors as they occur.</p>
            <?php else: ?>
                <div class="log-viewer">
                    <?php foreach (array_reverse($recentLogs) as $entry): ?>
                        <?php
                        $entry = trim($entry);
                        if (empty($entry)) continue;

                        // Determine log level for styling
                        $class = 'log-entry';
                        if (strpos($entry, '[ERROR]') !== false) {
                            $class .= ' log-error';
                        } elseif (strpos($entry, '[CRITICAL]') !== false) {
                            $class .= ' log-critical';
                        } elseif (strpos($entry, '[WARNING]') !== false) {
                            $class .= ' log-warning';
                        } elseif (strpos($entry, '[INFO]') !== false) {
                            $class .= ' log-info';
                        } elseif (strpos($entry, '[DEBUG]') !== false) {
                            $class .= ' log-debug';
                        }
                        ?>
                        <div class="<?php echo $class; ?>">
                            <pre><?php echo htmlspecialchars($entry, ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.log-size {
    font-size: 0.9rem;
    color: #666;
    padding: 6px 12px;
    background: #f5f5f5;
    border-radius: 4px;
}

.admin-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.admin-card-header {
    padding: 16px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.admin-card-header h2 {
    margin: 0;
    font-size: 1.1rem;
    color: #0F2B46;
}

.admin-card-body {
    padding: 20px;
}

.log-info-table {
    width: 100%;
    border-collapse: collapse;
}

.log-info-table th {
    text-align: left;
    padding: 8px;
    width: 200px;
    font-weight: 600;
    color: #0F2B46;
}

.log-info-table td {
    padding: 8px;
    color: #333;
}

.log-info-table code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.log-viewer {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
}

.log-entry {
    border-bottom: 1px solid #e0e0e0;
    padding: 12px;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-entry pre {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.log-error {
    background: #fff5f5;
    border-left: 4px solid #dc3545;
}

.log-critical {
    background: #fff0f0;
    border-left: 4px solid #a00;
}

.log-warning {
    background: #fffbf0;
    border-left: 4px solid #ff9800;
}

.log-info {
    background: #f0f8ff;
    border-left: 4px solid #2196F3;
}

.log-debug {
    background: #f5f5f5;
    border-left: 4px solid #999;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
