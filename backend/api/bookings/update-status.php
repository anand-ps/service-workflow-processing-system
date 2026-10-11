<?php
// Endpoint: Update Booking Status (Provider & Admin)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth(['provider', 'admin', 'user']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$bookingId = (int)($input['booking_id'] ?? 0);
$newStatus = trim($input['status'] ?? '');

$allowedStatuses = ['Pending', 'Accepted', 'On the Way', 'Completed', 'Rejected'];

if ($bookingId <= 0 || !in_array($newStatus, $allowedStatuses)) {
    sendJsonResponse('error', 'Valid Booking ID and allowed status (Pending, Accepted, On the Way, Completed, Rejected) required.', null, 400);
}

$pdo = getDBConnection();

// Fetch booking
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    sendJsonResponse('error', 'Booking record not found.', null, 404);
}

// Check authorization based on role
if ($currentUser['role'] === 'provider') {
    // Check if booking belongs to current provider
    $stmtProv = $pdo->prepare("SELECT id FROM providers WHERE user_id = ?");
    $stmtProv->execute([$currentUser['id']]);
    $prov = $stmtProv->fetch();

    if (!$prov || (int)$booking['provider_id'] !== (int)$prov['id']) {
        sendJsonResponse('error', 'Forbidden. You can only update bookings assigned to you.', null, 403);
    }
} elseif ($currentUser['role'] === 'user') {
    // Customer can only cancel/reject if currently Pending
    if ((int)$booking['user_id'] !== (int)$currentUser['id']) {
        sendJsonResponse('error', 'Forbidden. This is not your booking.', null, 403);
    }
    if ($newStatus !== 'Rejected' || $booking['status'] !== 'Pending') {
        sendJsonResponse('error', 'Customers can only cancel pending requests.', null, 400);
    }
}

try {
    $stmtUpdate = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmtUpdate->execute([$newStatus, $bookingId]);

    sendJsonResponse('success', "Booking status updated to '{$newStatus}'.", [
        'booking_id' => $bookingId,
        'status'     => $newStatus
    ]);
} catch (Exception $e) {
    sendJsonResponse('error', 'Failed to update status: ' . $e->getMessage(), null, 500);
}
?>
