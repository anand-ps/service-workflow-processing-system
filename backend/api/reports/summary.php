<?php
// Endpoint: Reports & System Analytics (Admin Only)
// Generates: Service Booking Reports, User Activity Reports, Provider Performance Reports
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = requireAuth(['admin']);
$pdo = getDBConnection();

// 1. Overall System Metrics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalProviders = (int)$pdo->query("SELECT COUNT(*) FROM providers WHERE is_approved = 1")->fetchColumn();
$pendingProviders = (int)$pdo->query("SELECT COUNT(*) FROM providers WHERE is_approved = 0")->fetchColumn();
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// 2. Service Booking Reports (Status breakdown)
$statusStmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
");
$bookingsByStatus = $statusStmt->fetchAll();

// 3. Service Category Distribution
$catStmt = $pdo->query("
    SELECT s.name, COUNT(b.id) as booking_count
    FROM services s
    LEFT JOIN bookings b ON s.id = b.service_id
    GROUP BY s.id, s.name
    ORDER BY booking_count DESC
");
$bookingsByCategory = $catStmt->fetchAll();

// 4. Provider Performance Reports (Top providers by rating and completed jobs)
$perfStmt = $pdo->query("
    SELECT 
        p.id as provider_id,
        u.name as provider_name,
        s.name as service_name,
        p.location,
        COUNT(DISTINCT b.id) as total_jobs,
        SUM(CASE WHEN b.status = 'Completed' THEN 1 ELSE 0 END) as completed_jobs,
        COALESCE(ROUND(AVG(f.rating), 1), 0.0) as average_rating,
        COUNT(f.id) as total_reviews
    FROM providers p
    JOIN users u ON p.user_id = u.id
    JOIN services s ON p.service_id = s.id
    LEFT JOIN bookings b ON p.id = b.provider_id
    LEFT JOIN feedback f ON p.id = f.provider_id
    WHERE p.is_approved = 1
    GROUP BY p.id, u.name, s.name, p.location
    ORDER BY completed_jobs DESC, average_rating DESC
    LIMIT 10
");
$providerPerformance = $perfStmt->fetchAll();

// 5. Recent System Activity
$recentStmt = $pdo->query("
    SELECT 
        b.id as booking_id,
        b.booking_date,
        b.status,
        b.created_at,
        cu.name as customer_name,
        pu.name as provider_name,
        s.name as service_name
    FROM bookings b
    JOIN users cu ON b.user_id = cu.id
    JOIN providers p ON b.provider_id = p.id
    JOIN users pu ON p.user_id = pu.id
    JOIN services s ON b.service_id = s.id
    ORDER BY b.id DESC
    LIMIT 10
");
$recentActivity = $recentStmt->fetchAll();

sendJsonResponse('success', 'System reports generated successfully.', [
    'overview' => [
        'total_customers'   => $totalUsers,
        'active_providers'  => $totalProviders,
        'pending_approvals' => $pendingProviders,
        'total_bookings'    => $totalBookings
    ],
    'booking_status_report'     => $bookingsByStatus,
    'category_report'           => $bookingsByCategory,
    'provider_performance'      => $providerPerformance,
    'recent_activity'           => $recentActivity
]);
?>
