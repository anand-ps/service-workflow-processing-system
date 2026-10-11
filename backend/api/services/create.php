<?php
// Endpoint: Create New Service Category (Admin Only)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$name        = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$icon        = trim($input['icon'] ?? 'wrench');

if (empty($name)) {
    sendJsonResponse('error', 'Service category name is required.', null, 400);
}

$pdo = getDBConnection();

$stmtCheck = $pdo->prepare("SELECT id FROM services WHERE name = ?");
$stmtCheck->execute([$name]);
if ($stmtCheck->fetch()) {
    sendJsonResponse('error', 'Service category with this name already exists.', null, 409);
}

try {
    $stmt = $pdo->prepare("INSERT INTO services (name, description, icon) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $icon]);
    $newId = $pdo->lastInsertId();

    sendJsonResponse('success', 'Service category added successfully.', [
        'service' => [
            'id' => (int)$newId,
            'name' => $name,
            'description' => $description,
            'icon' => $icon
        ]
    ], 201);
} catch (Exception $e) {
    sendJsonResponse('error', 'Failed to create service: ' . $e->getMessage(), null, 500);
}
?>
