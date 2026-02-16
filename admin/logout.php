<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Clear all admin session variables
unset($_SESSION[ADMIN_SESSION_KEY]);
unset($_SESSION['admin_email']);
unset($_SESSION['clerk_user']);

// Destroy entire session
session_destroy();

header('Location: /index.php');
exit;

