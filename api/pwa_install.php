<?php
/**
 * PWA Install Tracking API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'db_config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Get user info
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

// Parse device info from user agent
$deviceType = 'Unknown';
$os = 'Unknown';
$browser = 'Unknown';

// Detect device type
if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
    if (preg_match('/iPad/i', $userAgent)) {
        $deviceType = 'Tablet';
    } else {
        $deviceType = 'Mobile';
    }
} else {
    $deviceType = 'Desktop';
}

// Detect OS
if (preg_match('/Windows/i', $userAgent)) {
    $os = 'Windows';
} elseif (preg_match('/Mac/i', $userAgent)) {
    $os = 'MacOS';
} elseif (preg_match('/Linux/i', $userAgent)) {
    $os = 'Linux';
} elseif (preg_match('/Android/i', $userAgent)) {
    $os = 'Android';
} elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
    $os = 'iOS';
}

// Detect browser
if (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edge/i', $userAgent)) {
    $browser = 'Chrome';
} elseif (preg_match('/Firefox/i', $userAgent)) {
    $browser = 'Firefox';
} elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
    $browser = 'Safari';
} elseif (preg_match('/Edge/i', $userAgent)) {
    $browser = 'Edge';
} elseif (preg_match('/Opera|OPR/i', $userAgent)) {
    $browser = 'Opera';
}

try {
    $conn = getDBConnection();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $stmt = $conn->prepare("
        INSERT INTO pwa_installs (user_agent, ip_address, device_type, os, browser, referrer)
        VALUES (:user_agent, :ip_address, :device_type, :os, :browser, :referrer)
    ");

    $stmt->execute([
        ':user_agent' => $userAgent,
        ':ip_address' => $ipAddress,
        ':device_type' => $deviceType,
        ':os' => $os,
        ':browser' => $browser,
        ':referrer' => $referrer
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Install tracked successfully'
    ]);

} catch (Exception $e) {
    error_log("PWA Install tracking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to track install'
    ]);
}
