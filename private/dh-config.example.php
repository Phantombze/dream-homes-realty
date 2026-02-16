<?php
/**
 * Dream Homes Realty - Private Configuration Template
 *
 * INSTRUCTIONS:
 * 1. Copy this file to: private/dh-config.php
 * 2. Fill in your actual credentials below
 * 3. NEVER commit dh-config.php to Git (it's in .gitignore)
 *
 * This template is safe to commit - it contains no real credentials
 */

// Database Configuration
const DB_HOST = 'localhost';
const DB_NAME = 'your_database_name';
const DB_USER = 'your_database_user';
const DB_PASS = 'your_database_password';

// Admin Session Security
// Generate a secure random key: https://randomkeygen.com/
const ADMIN_SESSION_KEY = 'your-secure-random-key-here';

// Clerk Authentication (if using Clerk)
// Get these from: https://dashboard.clerk.com
const CLERK_PUBLISHABLE_KEY = 'pk_live_your_publishable_key';
const CLERK_SECRET_KEY = 'sk_live_your_secret_key';
const CLERK_ISSUER = 'https://your-clerk-domain.clerk.accounts.dev';
const CLERK_JWKS_URL = 'https://your-clerk-domain.clerk.accounts.dev/.well-known/jwks.json';
const CLERK_SIGN_IN_URL = 'https://your-clerk-domain.clerk.accounts.dev/sign-in';
const CLERK_ALLOWED_EMAILS = ['admin@example.com']; // Add authorized admin emails

// Cloudflare Turnstile (anti-bot protection)
// Get your key from: https://dash.cloudflare.com/turnstile
const TURNSTILE_SECRET_KEY = 'your_turnstile_secret_key';

// Configuration loaded flag
define('DREAMHOMES_CONFIG', true);
