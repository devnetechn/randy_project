<?php
/**
 * Start (or resume) the logged-in customer's chat.
 * Body (optional): { seed: [{sender_type, body}], requestHuman: bool }
 *  - With a seed -> create a fresh conversation, store the seed, optionally escalate.
 *  - Without    -> return the active conversation, or create one.
 * Returns { conversation, messages }.
 */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
$u = require_login_api();
if ($u['role'] !== 'customer') {
    json_error('Only customers can start a chat', 403);
}

$body = read_json();
$seed = is_array($body['seed'] ?? null) ? $body['seed'] : [];
$requestHuman = !empty($body['requestHuman']);

if ($seed) {
    $convo = create_conversation((int) $u['id']);
    foreach ($seed as $m) {
        $type = $m['sender_type'] ?? '';
        if (!in_array($type, ['customer', 'ai', 'system'], true)) {
            continue;
        }
        $text = trim((string) ($m['body'] ?? ''));
        if ($text === '') {
            continue;
        }
        create_message($convo['id'], $type, $type === 'customer' ? (int) $u['id'] : null, $text);
    }
    touch_last_message($convo['id']);
    if ($requestHuman) {
        $convo = escalate_to_human($convo);
    } else {
        $convo = find_conversation($convo['id']);
    }
} else {
    $convo = active_conversation_for_customer((int) $u['id']) ?? create_conversation((int) $u['id']);
}

json_out([
    'conversation' => $convo,
    'messages'     => list_messages($convo['id'], 0),
]);
