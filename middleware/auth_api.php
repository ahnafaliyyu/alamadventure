<?php
// middleware/auth_api.php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!auth_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
