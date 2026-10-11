<?php
// Endpoint: List Feedback & Reviews
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();

$providerId = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : 0;

$sql = "
    SELECT 
        f.id as feedback_id,
        f.rating,
        f.comments,
        f.created_at,
        u.name as customer_name,
        s.name as service_name
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN bookings b ON f.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    WHERE 1=1
";

$params = [];
if ($providerId > 0) {
    $sql .= " AND f.provider_id = ?";
    $params[] = $providerId;
}

$sql .= " ORDER BY f.id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedbackList = $stmt->fetchAll();

sendJsonResponse('success', 'Feedback retrieved successfully.', [
    'feedback' => $feedbackList
]);
?>
