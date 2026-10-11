<?php
// Endpoint: Submit Rating and Feedback (Customer Only)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth(['user']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$bookingId = (int)($input['booking_id'] ?? 0);
$rating    = (int)($input['rating'] ?? 0);
$comments  = trim($input['comments'] ?? '');

if ($bookingId <= 0 || $rating < 1 || $rating > 5) {
    sendJsonResponse('error', 'Valid Booking ID and a rating between 1 and 5 stars are required.', null, 400);
}

$pdo = getDBConnection();

// Verify booking belongs to this user and is Completed
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    sendJsonResponse('error', 'Booking record not found.', null, 404);
}

if ((int)$booking['user_id'] !== (int)$currentUser['id']) {
    sendJsonResponse('error', 'Forbidden. You can only review your own bookings.', null, 403);
}

if ($booking['status'] !== 'Completed') {
    sendJsonResponse('error', 'Feedback can only be submitted for completed services.', null, 400);
}

// Check if feedback already exists for this booking
$stmtFb = $pdo->prepare("SELECT id FROM feedback WHERE booking_id = ?");
$stmtFb->execute([$bookingId]);
if ($stmtFb->fetch()) {
    sendJsonResponse('error', 'Feedback has already been submitted for this booking.', null, 409);
}

try {
    $stmtInsert = $pdo->prepare("
        INSERT INTO feedback (booking_id, user_id, provider_id, rating, comments)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $bookingId,
        $currentUser['id'],
        $booking['provider_id'],
        $rating,
        $comments
    ]);

    $feedbackId = (int)$pdo->lastInsertId();

    sendJsonResponse('success', 'Thank you! Your feedback and rating have been recorded.', [
        'feedback_id' => $feedbackId,
        'rating'      => $rating
    ], 201);
} catch (Exception $e) {
    sendJsonResponse('error', 'Failed to save feedback: ' . $e->getMessage(), null, 500);
}
?>
