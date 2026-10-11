<?php
// Endpoint: Approve or Reject Service Provider (Admin Only)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Method not allowed. Use POST.', null, 405);
}

$input = getJsonInput();

$providerId = (int)($input['provider_id'] ?? 0);
$action     = trim($input['action'] ?? 'approve'); // 'approve' or 'reject'

if ($providerId <= 0) {
    sendJsonResponse('error', 'Valid Provider ID is required.', null, 400);
}

$pdo = getDBConnection();

// Verify provider exists
$stmt = $pdo->prepare("SELECT id, user_id FROM providers WHERE id = ?");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    sendJsonResponse('error', 'Provider not found.', null, 404);
}

try {
    if ($action === 'approve') {
        $stmtUpdate = $pdo->prepare("UPDATE providers SET is_approved = 1 WHERE id = ?");
        $stmtUpdate->execute([$providerId]);
        sendJsonResponse('success', 'Provider has been approved successfully.');
    } else {
        // If rejected, remove from providers table or mark unapproved
        $stmtUpdate = $pdo->prepare("UPDATE providers SET is_approved = 0 WHERE id = ?");
        $stmtUpdate->execute([$providerId]);
        sendJsonResponse('success', 'Provider status set to rejected / unapproved.');
    }
} catch (Exception $e) {
    sendJsonResponse('error', 'Failed to update provider status: ' . $e->getMessage(), null, 500);
}
?>
