<?php
/** Admin chat queue: waiting + active conversations, with the customer's name. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/chat.php';

require_admin_api();

$st = db()->query(
    "SELECT c.*, u.full_name AS customer_name, u.email AS customer_email
       FROM conversations c
       JOIN users u ON u.id = c.customer_id
      WHERE c.status IN ('waiting_human', 'human')
      ORDER BY FIELD(c.status,'waiting_human','human'), c.last_message_at ASC"
);
json_out(['conversations' => $st->fetchAll()]);
