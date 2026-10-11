<?php
// Endpoint: User, Provider & Admin Login
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($email) || empty($password)) {
    sendJsonResponse('error', 'Email and password are required.', null, 400);
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT id, name, email, password, phone, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    sendJsonResponse('error', 'Invalid email or password credentials.', null, 401);
}

$providerData = null;
if ($user['role'] === 'provider') {
    $stmtProv = $pdo->prepare("
        SELECT p.id as provider_id, p.service_id, p.experience_years, p.hourly_rate, p.location, p.is_approved, s.name as service_name
        FROM providers p
        LEFT JOIN services s ON p.service_id = s.id
        WHERE p.user_id = ?
    ");
    $stmtProv->execute([$user['id']]);
    $providerData = $stmtProv->fetch();

    if ($providerData && (int)$providerData['is_approved'] !== 1) {
        sendJsonResponse('error', 'Your provider account is pending approval by administrator.', null, 403);
    }
}

// Set session
$_SESSION['user'] = [
    'id'          => (int)$user['id'],
    'name'        => $user['name'],
    'email'       => $user['email'],
    'phone'       => $user['phone'],
    'role'        => $user['role'],
    'provider_id' => $providerData ? (int)$providerData['provider_id'] : null
];

// Return clean payload without password
unset($user['password']);
if ($providerData) {
    $user['provider'] = $providerData;
}

sendJsonResponse('success', 'Login successful.', [
    'user' => $user
]);
?>
