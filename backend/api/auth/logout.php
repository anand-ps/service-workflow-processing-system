<?php
// Endpoint: User Logout
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}

sendJsonResponse('success', 'Logged out successfully.');
?>
