<?php
/** Admin chat controls. Body: { conversationId, action: takeover|return|close } */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
$admin = require_admin_api();

$payload = read_json();
$convo = find_conversation((int) ($payload['conversationId'] ?? 0));
$action = $payload['action'] ?? '';
if (!$convo) {
    json_error('Conversation not found', 404);
}

switch ($action) {
    case 'takeover':
        $updated = admin_takeover($convo, $admin);
        break;
    case 'return':
        $updated = return_to_ai($convo);
        break;
    case 'close':
        $updated = close_conversation($convo);
        break;
    default:
        json_error('Unknown action', 422);
}

json_out(['conversation' => $updated]);
