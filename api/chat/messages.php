<?php
/** Poll for a conversation's messages. GET ?conversation_id=&after=<lastId> */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

$u = require_login_api();
$conversationId = (int) ($_GET['conversation_id'] ?? 0);
$after = (int) ($_GET['after'] ?? 0);

$convo = find_conversation($conversationId);
if (!$convo) {
    json_error('Conversation not found', 404);
}
if (!can_access_conversation($u, $convo)) {
    json_error('Forbidden', 403);
}

json_out([
    'conversation' => ['id' => (int) $convo['id'], 'status' => $convo['status'], 'assigned_admin_id' => $convo['assigned_admin_id']],
    'messages'     => list_messages($conversationId, $after),
]);
