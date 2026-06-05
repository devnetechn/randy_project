<?php
/** Guest (not-logged-in) assistant reply. No DB conversation — stateless. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body = read_json();
$messages = is_array($body['messages'] ?? null) ? $body['messages'] : [];

json_out(['reply' => chat_ai_reply($messages)]);
