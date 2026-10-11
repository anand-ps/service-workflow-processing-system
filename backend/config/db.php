<?php
// Database Configuration and Connection Helper

// Allow CORS for local frontend requests
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// Start session for authentication if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'service_booking_db';

function getDBConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Helper to send standard JSON response
function sendJsonResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Helper to parse JSON input from request body
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

// Helper to get authenticated user
function getAuthUser() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

// Helper to require authentication
function requireAuth($allowedRoles = []) {
    $user = getAuthUser();
    if (!$user) {
        sendJsonResponse('error', 'Unauthorized. Please login.', null, 401);
    }
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        sendJsonResponse('error', 'Forbidden. Insufficient permissions.', null, 403);
    }
    return $user;
}
?>
