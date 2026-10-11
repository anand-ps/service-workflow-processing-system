<?php
// Endpoint: User & Service Provider Registration
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$phone    = trim($input['phone'] ?? '');
$password = trim($input['password'] ?? '');
$role     = trim($input['role'] ?? 'user');

if (empty($name) || empty($email) || empty($phone) || empty($password)) {
    sendJsonResponse('error', 'All fields (Name, Email, Phone, Password) are required.', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse('error', 'Invalid email address format.', null, 400);
}

if (strlen($password) < 6) {
    sendJsonResponse('error', 'Password must be at least 6 characters.', null, 400);
}

if (!in_array($role, ['user', 'provider'])) {
    $role = 'user';
}

$pdo = getDBConnection();

// Check if email already registered
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendJsonResponse('error', 'An account with this email address already exists.', null, 409);
}

try {
    $pdo->beginTransaction();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmtUser->execute([$name, $email, $hashedPassword, $phone, $role]);
    $userId = (int)$pdo->lastInsertId();

    // If registering as service provider, create provider profile
    if ($role === 'provider') {
        $serviceId   = (int)($input['service_id'] ?? 1);
        $experience  = (int)($input['experience_years'] ?? 1);
        $hourlyRate  = (float)($input['hourly_rate'] ?? 250.00);
        $location    = trim($input['location'] ?? 'Local Area');

        $stmtProv = $pdo->prepare("
            INSERT INTO providers (user_id, service_id, experience_years, hourly_rate, location, is_approved)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmtProv->execute([$userId, $serviceId, $experience, $hourlyRate, $location]);
    }

    $pdo->commit();

    sendJsonResponse('success', 'Registration successful.' . ($role === 'provider' ? ' Your profile is submitted for admin approval.' : ' You can now log in.'), [
        'id'    => $userId,
        'name'  => $name,
        'email' => $email,
        'role'  => $role
    ], 201);

} catch (Exception $e) {
    $pdo->rollBack();
    sendJsonResponse('error', 'Registration failed: ' . $e->getMessage(), null, 500);
}
?>
