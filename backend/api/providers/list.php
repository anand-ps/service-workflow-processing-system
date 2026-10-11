<?php
// Endpoint: List Approved Service Providers
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();

$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT 
        p.id as provider_id,
        p.user_id,
        p.service_id,
        p.experience_years,
        p.hourly_rate,
        p.location,
        u.name as provider_name,
        u.phone as provider_phone,
        u.email as provider_email,
        s.name as service_name,
        s.icon as service_icon,
        COALESCE(ROUND(AVG(f.rating), 1), 0.0) as avg_rating,
        COUNT(f.id) as review_count,
        (SELECT COUNT(*) FROM bookings b WHERE b.provider_id = p.id AND b.status = 'Completed') as completed_jobs
    FROM providers p
    JOIN users u ON p.user_id = u.id
    JOIN services s ON p.service_id = s.id
    LEFT JOIN feedback f ON p.id = f.provider_id
    WHERE p.is_approved = 1
";

$params = [];

if ($serviceId > 0) {
    $sql .= " AND p.service_id = ?";
    $params[] = $serviceId;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR p.location LIKE ? OR s.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY p.id ORDER BY avg_rating DESC, p.experience_years DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$providers = $stmt->fetchAll();

sendJsonResponse('success', 'Providers retrieved successfully.', [
    'providers' => $providers
]);
?>
