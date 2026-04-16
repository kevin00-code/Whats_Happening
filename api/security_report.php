<?php
/**
 * What's Happening — Security Event Reporter
 * Receives client-side security events (devtools detection, etc.)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if ($data && isset($data['event'])) {
    $ip    = getClientIP();
    $event = 'CLIENT_' . strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $data['event']));
    logSecurityEvent($ip, $event);
}

echo json_encode(['ok' => true]);
