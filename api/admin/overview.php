<?php
/** Admin dashboard KPIs. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$pdo = db();
$scalar = function (string $sql) use ($pdo): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

$queueDepth      = $scalar("SELECT COUNT(*) FROM conversations WHERE status = 'waiting_human'");
$activeChats     = $scalar("SELECT COUNT(*) FROM conversations WHERE status = 'human'");
$pendingBookings = $scalar("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$upcomingToday   = $scalar("SELECT COUNT(*) FROM appointments WHERE status='confirmed' AND DATE(scheduled_at)=CURDATE()");
$newConversations = $scalar("SELECT COUNT(*) FROM conversations WHERE DATE(created_at)=CURDATE()");
$newBookings     = $scalar("SELECT COUNT(*) FROM appointments WHERE DATE(created_at)=CURDATE()");
$newSignups      = $scalar("SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at)=CURDATE()");

$row = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(
          CASE WHEN c.status IN ('waiting_human','human')
                 OR EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id=c.id AND m.sender_type='admin')
               THEN 1 ELSE 0 END
        ) AS escalated
      FROM conversations c
     WHERE DATE(c.created_at)=CURDATE()"
)->fetch();
$total = (int) ($row['total'] ?? 0);
$escalated = (int) ($row['escalated'] ?? 0);
$aiEscalationPct = $total === 0 ? 0 : (int) round($escalated * 100 / $total);

json_out([
    'live'  => compact('queueDepth', 'activeChats', 'pendingBookings', 'upcomingToday'),
    'today' => compact('newConversations', 'newBookings', 'newSignups', 'aiEscalationPct'),
]);
