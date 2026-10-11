<?php
// Endpoint: List Bookings by Role (User, Provider, Admin)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth();
$pdo = getDBConnection();

$role = $currentUser['role'];
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "
    SELECT 
        b.id as booking_id,
        b.booking_date,
        b.booking_time,
        b.address,
        b.problem_desc,
        b.status,
        b.created_at,
        b.user_id,
        b.provider_id,
        b.service_id,
        cu.name as customer_name,
        cu.phone as customer_phone,
        cu.email as customer_email,
        pu.name as provider_name,
        pu.phone as provider_phone,
        p.hourly_rate as provider_rate,
        p.location as provider_location,
        s.name as service_name,
        f.id as feedback_id,
        f.rating as feedback_rating,
        f.comments as feedback_comments
    FROM bookings b
    JOIN users cu ON b.user_id = cu.id
    JOIN providers p ON b.provider_id = p.id
    JOIN users pu ON p.user_id = pu.id
    JOIN services s ON b.service_id = s.id
    LEFT JOIN feedback f ON b.id = f.booking_id
    WHERE 1=1
";

$params = [];

if ($role === 'user') {
    $sql .= " AND b.user_id = ?";
    $params[] = $currentUser['id'];
} elseif ($role === 'provider') {
    // Look up provider table id for current user
    $stmtProv = $pdo->prepare("SELECT id FROM providers WHERE user_id = ?");
    $stmtProv->execute([$currentUser['id']]);
    $provRow = $stmtProv->fetch();
    $provId = $provRow ? (int)$provRow['id'] : 0;

    $sql .= " AND b.provider_id = ?";
    $params[] = $provId;
}
// Admin can see everything

if (!empty($statusFilter)) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

sendJsonResponse('success', 'Bookings retrieved successfully.', [
    'bookings' => $bookings
]);
?>
