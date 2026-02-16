<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';

function dh_esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dh_digits(string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
}

function dh_tel_href(string $number): string {
    $number = trim($number);
    if ($number === '') {
        return '';
    }
    // Keep a leading + if present, otherwise strip to digits.
    if (substr($number, 0, 1) === '+') {
        $rest = dh_digits(substr($number, 1));
        return $rest !== '' ? 'tel:+' . $rest : '';
    }
    $digits = dh_digits($number);
    return $digits !== '' ? 'tel:+' . $digits : '';
}

function dh_nl2br_esc(string $value): string {
    return nl2br(dh_esc($value), false);
}

function dh_ensure_site_settings_table(): void {
    db()->exec(
        "CREATE TABLE IF NOT EXISTS site_settings (\n"
        . "  setting_key VARCHAR(190) NOT NULL,\n"
        . "  setting_value TEXT NULL,\n"
        . "  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
        . "  PRIMARY KEY (setting_key)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function dh_site_setting(string $key, ?string $default = null): string {
    // Initialize cache in global scope for persistent access
    if (!isset($GLOBALS['dh_site_settings_cache'])) {
        $GLOBALS['dh_site_settings_cache'] = [];
    }
    
    $cache = &$GLOBALS['dh_site_settings_cache'];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        $value = $row ? (string)($row['setting_value'] ?? '') : '';
        if ($value === '' && $default !== null) {
            $value = $default;
        }
        $cache[$key] = $value;
        return $value;
    } catch (Throwable $e) {
        $value = $default ?? '';
        $cache[$key] = $value;
        return $value;
    }
}

function dh_set_site_setting(string $key, ?string $value): void {
    $key = trim($key);
    if ($key === '') {
        return;
    }

    $value = $value === null ? null : trim($value);
    if ($value === '') {
        $value = null;
    }

    $stmt = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) '
        . 'ON DUPLICATE KEY UPDATE setting_value = :v2'
    );
    $stmt->execute([
        'k' => $key,
        'v' => $value,
        'v2' => $value,
    ]);
}
