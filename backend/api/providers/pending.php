<?php
// Endpoint: List Providers Pending Approval (Admin Only)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['admin']);

$pdo = getDBConnection();

$sql = "
    SELECT 
        p.id as provider_id,
        p.user_id,
        p.service_id,
        p.experience_years,
        p.hourly_rate,
        p.location,
        p.is_approved,
        p.created_at,
        u.name as provider_name,
        u.email as provider_email,
        u.phone as provider_phone,
        s.name as service_name
    FROM providers p
    JOIN users u ON p.user_id = u.id
    JOIN services s ON p.service_id = s.id
    WHERE p.is_approved = 0
    ORDER BY p.created_at DESC
";

$stmt = $pdo->query($sql);
$pendingProviders = $stmt->fetchAll();

sendJsonResponse('success', 'Pending providers retrieved successfully.', [
    'providers' => $pendingProviders
]);
?>
