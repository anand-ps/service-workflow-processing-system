<?php
// Endpoint: Check Current Session / User Profile
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = getAuthUser();

if (!$user) {
    sendJsonResponse('error', 'Not logged in.', null, 401);
}

sendJsonResponse('success', 'Authenticated user found.', [
    'user' => $user
]);
?>
