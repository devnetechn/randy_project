<?php
/** Chat data access + assistant orchestration (polling model, no websockets). */
require_once __DIR__ . '/business.php';
require_once __DIR__ . '/scripted-bot.php';
require_once __DIR__ . '/gemini.php';

/* ----------  Conversations  ---------- */

function find_conversation($id): ?array
{
    $st = db()->prepare('SELECT * FROM conversations WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function create_conversation(int $customerId): array
{
    db()->prepare('INSERT INTO conversations (customer_id) VALUES (?)')->execute([$customerId]);
    return find_conversation((int) db()->lastInsertId());
}

function list_conversations_for_customer(int $customerId): array
{
    $st = db()->prepare('SELECT * FROM conversations WHERE customer_id = ? ORDER BY last_message_at DESC');
    $st->execute([$customerId]);
    return $st->fetchAll();
}

function list_conversations(?string $status = null): array
{
    if ($status) {
        $st = db()->prepare('SELECT * FROM conversations WHERE status = ? ORDER BY last_message_at DESC');
        $st->execute([$status]);
        return $st->fetchAll();
    }
    return db()->query('SELECT * FROM conversations ORDER BY last_message_at DESC')->fetchAll();
}

/** Latest non-closed conversation for a customer, if any. */
function active_conversation_for_customer(int $customerId): ?array
{
    $st = db()->prepare(
        "SELECT * FROM conversations WHERE customer_id = ? AND status <> 'closed'
         ORDER BY last_message_at DESC LIMIT 1"
    );
    $st->execute([$customerId]);
    $row = $st->fetch();
    return $row ?: null;
}

function set_conversation_status($id, string $status): array
{
    db()->prepare('UPDATE conversations SET status = ? WHERE id = ?')->execute([$status, $id]);
    return find_conversation($id);
}

function assign_admin_and_human($id, int $adminId): array
{
    db()->prepare("UPDATE conversations SET status='human', assigned_admin_id=? WHERE id=?")
        ->execute([$adminId, $id]);
    return find_conversation($id);
}

function clear_admin_and_ai($id): array
{
    db()->prepare("UPDATE conversations SET status='ai', assigned_admin_id=NULL WHERE id=?")
        ->execute([$id]);
    return find_conversation($id);
}

function touch_last_message($id): void
{
    db()->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?')->execute([$id]);
}

function can_access_conversation(array $user, array $convo): bool
{
    return $user['role'] === 'admin' || (string) $convo['customer_id'] === (string) $user['id'];
}

/* ----------  Messages  ---------- */

function create_message($conversationId, string $senderType, ?int $senderId, string $body): array
{
    db()->prepare(
        'INSERT INTO messages (conversation_id, sender_type, sender_id, body) VALUES (?, ?, ?, ?)'
    )->execute([$conversationId, $senderType, $senderId, $body]);
    $st = db()->prepare('SELECT * FROM messages WHERE id = ?');
    $st->execute([(int) db()->lastInsertId()]);
    return $st->fetch();
}

/** Messages for a conversation, optionally only those newer than $afterId. */
function list_messages($conversationId, int $afterId = 0): array
{
    $st = db()->prepare(
        'SELECT * FROM messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC, id ASC'
    );
    $st->execute([$conversationId, $afterId]);
    return $st->fetchAll();
}

function get_recent_messages($conversationId, int $limit = 20): array
{
    $st = db()->prepare(
        'SELECT * FROM (
           SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at DESC, id DESC LIMIT ?
         ) recent ORDER BY created_at ASC, id ASC'
    );
    $st->bindValue(1, $conversationId);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/* ----------  Orchestration  ---------- */

/**
 * Store a customer message and, when the conversation is still with the AI,
 * generate and store an assistant reply. Returns the new messages created.
 */
function handle_customer_message(array $convo, array $user, string $body): array
{
    $created = [];
    $created[] = create_message($convo['id'], 'customer', (int) $user['id'], $body);
    touch_last_message($convo['id']);

    if ($convo['status'] === 'ai') {
        $recent = get_recent_messages($convo['id'], 20);
        $replyText = chat_ai_reply($recent);
        $created[] = create_message($convo['id'], 'ai', null, $replyText);
        touch_last_message($convo['id']);
    }
    return $created;
}

function handle_admin_message(array $convo, array $admin, string $body): array
{
    $msg = create_message($convo['id'], 'admin', (int) $admin['id'], $body);
    touch_last_message($convo['id']);
    return $msg;
}

function escalate_to_human(array $convo): array
{
    $updated = set_conversation_status($convo['id'], 'waiting_human');
    create_message($convo['id'], 'system', null, "You've asked to speak with a team member. Someone will be with you shortly.");
    return $updated;
}

function admin_takeover(array $convo, array $admin): array
{
    $updated = assign_admin_and_human($convo['id'], (int) $admin['id']);
    create_message($convo['id'], 'system', null, 'A team member has joined the chat and will help you now.');
    return $updated;
}

function return_to_ai(array $convo): array
{
    $updated = clear_admin_and_ai($convo['id']);
    create_message($convo['id'], 'system', null, "You're back with our virtual assistant.");
    return $updated;
}

function close_conversation(array $convo): array
{
    $updated = set_conversation_status($convo['id'], 'closed');
    create_message($convo['id'], 'system', null, 'This conversation has been closed. Thank you!');
    return $updated;
}
