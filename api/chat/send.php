<?php
/** Send a message. Body: { conversationId, body }. Customer messages may trigger an AI reply. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
$u = require_login_api();

$payload = read_json();
$conversationId = (int) ($payload['conversationId'] ?? 0);
$text = trim((string) ($payload['body'] ?? ''));

if ($text === '') {
    json_error('Message cannot be empty', 422);
}
$convo = find_conversation($conversationId);
if (!$convo) {
    json_error('Conversation not found', 404);
}
if (!can_access_conversation($u, $convo)) {
    json_error('Forbidden', 403);
}
if ($convo['status'] === 'closed') {
    json_error('This conversation is closed', 409);
}

if ($u['role'] === 'admin') {
    $created = [handle_admin_message($convo, $u, $text)];
} else {
    $created = handle_customer_message($convo, $u, $text);
}

json_out(['messages' => $created]);
