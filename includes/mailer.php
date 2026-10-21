<?php
declare(strict_types=1);

/**
 * Send a transactional email via the Brevo API.
 *
 * Requires BREVO_API_KEY defined in private/dh-config.php.
 * Sender name/email are read from site settings:
 *   brevo.from_email  – must match a verified Brevo sender
 *   brevo.from_name   – display name shown to recipients
 *
 * @param string $toEmail      Recipient email address
 * @param string $toName       Recipient display name
 * @param string $subject      Email subject line
 * @param string $textBody     Plain-text email body
 * @param string $replyToEmail Optional reply-to email (e.g. the form submitter)
 * @param string $replyToName  Optional reply-to display name
 *
 * @return bool  true on HTTP 2xx from Brevo, false on any failure
 */
function dh_brevo_send(
    string $toEmail,
    string $toName,
    string $subject,
    string $textBody,
    string $replyToEmail = '',
    string $replyToName  = ''
): bool {
    $apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
    if ($apiKey === '' || $apiKey === 'YOUR-BREVO-API-KEY-HERE') {
        return false;
    }

    $fromEmail = dh_site_setting('brevo.from_email', '');
    $fromName  = dh_site_setting('brevo.from_name', 'Dream Homes Realty');

    if ($fromEmail === '') {
        return false;
    }

    $payload = [
        'sender'      => ['name' => $fromName, 'email' => $fromEmail],
        'to'          => [['email' => $toEmail, 'name' => $toName]],
        'subject'     => $subject,
        'textContent' => $textBody,
    ];

    if ($replyToEmail !== '') {
        $payload['replyTo'] = ['email' => $replyToEmail, 'name' => $replyToName];
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        $logFile = __DIR__ . '/../private/brevo-error.log';
        $entry   = '[' . date('Y-m-d H:i:s') . '] HTTP ' . $httpCode
            . ($curlError !== '' ? ' | cURL: ' . $curlError : '')
            . ($response    !== false ? ' | Body: ' . $response : '')
            . "\n";
        @file_put_contents($logFile, $entry, FILE_APPEND);
        return false;
    }

    return true;
}
