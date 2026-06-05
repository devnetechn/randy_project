<?php
/** Customer asks to speak with a team member. Body: { conversationId } */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
$u = require_login_api();

$payload = read_json();
$convo = find_conversation((int) ($payload['conversationId'] ?? 0));
if (!$convo) {
    json_error('Conversation not found', 404);
}
if ((string) $convo['customer_id'] !== (string) $u['id']) {
    json_error('Forbidden', 403);
}
if ($convo['status'] === 'closed') {
    json_error('This conversation is closed', 409);
}
if ($convo['status'] === 'human') {
    json_error('A team member is already helping you', 409);
}
if ($convo['status'] === 'waiting_human') {
    json_out(['ok' => true]); // already queued
}

escalate_to_human($convo);
json_out(['ok' => true]);
