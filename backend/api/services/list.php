<?php
// Endpoint: List All Service Categories
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();

// Fetch services along with count of approved providers available
$query = "
    SELECT 
        s.id, 
        s.name, 
        s.description, 
        s.icon, 
        COUNT(p.id) as provider_count
    FROM services s
    LEFT JOIN providers p ON s.id = p.service_id AND p.is_approved = 1
    GROUP BY s.id, s.name, s.description, s.icon
    ORDER BY s.id ASC
";

$stmt = $pdo->query($query);
$services = $stmt->fetchAll();

sendJsonResponse('success', 'Services retrieved successfully.', [
    'services' => $services
]);
?>
