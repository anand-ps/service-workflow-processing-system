<?php
// Endpoint: Create Service Booking Request (Customer Only)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth(['user']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$providerId  = (int)($input['provider_id'] ?? 0);
$serviceId   = (int)($input['service_id'] ?? 0);
$bookingDate = trim($input['booking_date'] ?? '');
$bookingTime = trim($input['booking_time'] ?? '');
$address     = trim($input['address'] ?? '');
$problemDesc = trim($input['problem_desc'] ?? '');

if ($providerId <= 0 || empty($bookingDate) || empty($bookingTime) || empty($address) || empty($problemDesc)) {
    sendJsonResponse('error', 'All booking details (provider, date, time, address, issue description) are required.', null, 400);
}

$pdo = getDBConnection();

// Verify provider exists and is approved
$stmtProv = $pdo->prepare("SELECT id, service_id, is_approved FROM providers WHERE id = ?");
$stmtProv->execute([$providerId]);
$prov = $stmtProv->fetch();

if (!$prov || (int)$prov['is_approved'] !== 1) {
    sendJsonResponse('error', 'Selected service provider is unavailable or not verified.', null, 400);
}

// Fallback service ID if not provided explicitly
if ($serviceId <= 0) {
    $serviceId = (int)$prov['service_id'];
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO bookings (user_id, provider_id, service_id, booking_date, booking_time, address, problem_desc, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([
        $currentUser['id'],
        $providerId,
        $serviceId,
        $bookingDate,
        $bookingTime,
        $address,
        $problemDesc
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    sendJsonResponse('success', 'Booking request placed successfully. The provider will review your request.', [
        'booking_id' => $bookingId,
        'status'     => 'Pending'
    ], 201);
} catch (Exception $e) {
    sendJsonResponse('error', 'Failed to submit booking: ' . $e->getMessage(), null, 500);
}
?>
