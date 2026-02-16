<?php
require_once __DIR__ . '/bootstrap.php';

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

function require_admin(): void {
    if (!isset($_SESSION[ADMIN_SESSION_KEY])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_id(): ?string {
    return $_SESSION[ADMIN_SESSION_KEY] ?? null;
}

function clerk_sign_in_url(): string {
    return rtrim(CLERK_ISSUER, '/') . '/sign-in';
}

function clerk_hosted_sign_in_url(): string {
    if (defined('CLERK_SIGN_IN_URL') && is_string(CLERK_SIGN_IN_URL) && CLERK_SIGN_IN_URL !== '') {
        return CLERK_SIGN_IN_URL;
    }
    return clerk_sign_in_url();
}

function clerk_sign_out_url(): string {
    return rtrim(CLERK_ISSUER, '/') . '/sign-out';
}

function clerk_allowed_emails(): array {
    $emails = CLERK_ALLOWED_EMAILS ?? [];
    if (!is_array($emails)) {
        return [];
    }
    return array_values(array_filter(array_map('strtolower', array_map('trim', $emails))));
}

function clerk_fetch_jwks(): array {
    static $jwks = null;
    if (is_array($jwks)) {
        return $jwks;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
        ],
    ]);

    $json = @file_get_contents(CLERK_JWKS_URL, false, $context);
    if ($json === false) {
        throw new RuntimeException('Unable to fetch Clerk JWKS.');
    }

    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['keys'])) {
        throw new RuntimeException('Invalid Clerk JWKS payload.');
    }

    $jwks = $data;
    return $jwks;
}

function clerk_verify_session_token(string $token): ?string {
    if ($token === '') {
        return null;
    }

    $jwks = clerk_fetch_jwks();
    $decoded = JWT::decode($token, JWK::parseKeySet($jwks));
    $issuer = $decoded->iss ?? '';
    $userId = $decoded->sub ?? '';

    if ($issuer !== CLERK_ISSUER || !is_string($userId) || $userId === '') {
        return null;
    }

    return $userId;
}

function clerk_fetch_user_email(string $userId): ?string {
    $url = 'https://api.clerk.com/v1/users/' . rawurlencode($userId);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "Authorization: Bearer " . CLERK_SECRET_KEY . "\r\nContent-Type: application/json\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }

    $primaryId = $data['primary_email_address_id'] ?? null;
    $emails = $data['email_addresses'] ?? [];
    if (!is_array($emails)) {
        return null;
    }

    foreach ($emails as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $id = $entry['id'] ?? null;
        $email = $entry['email_address'] ?? null;
        if ($primaryId && $id === $primaryId && is_string($email)) {
            return $email;
        }
    }

    foreach ($emails as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $email = $entry['email_address'] ?? null;
        if (is_string($email)) {
            return $email;
        }
    }

    return null;
}

function clerk_email_allowed(string $email): bool {
    $allowed = clerk_allowed_emails();
    if ($allowed === []) {
        return false;
    }
    return in_array(strtolower($email), $allowed, true);
}
