<?php
/**
 * Admin booking reports: counts + status breakdown grouped by day, week, or
 * month, based on when each booking was submitted (created_at) — not the
 * scheduled service date.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$period = $_GET['period'] ?? 'monthly';
if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
    $period = 'monthly';
}

$pdo = db();

if ($period === 'daily') {
    $sql = "SELECT DATE(created_at) AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= CURDATE() - INTERVAL 29 DAY
            GROUP BY DATE(created_at)
            ORDER BY period DESC";
} elseif ($period === 'weekly') {
    $sql = "SELECT DATE(created_at - INTERVAL WEEKDAY(created_at) DAY) AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= CURDATE() - INTERVAL 84 DAY
            GROUP BY period
            ORDER BY period DESC";
} else {
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
            GROUP BY period
            ORDER BY period DESC";
}

$rows = array_map(function ($r) {
    return [
        'period'    => $r['period'],
        'total'     => (int) $r['total'],
        'pending'   => (int) $r['pending'],
        'confirmed' => (int) $r['confirmed'],
        'declined'  => (int) $r['declined'],
        'cancelled' => (int) $r['cancelled'],
        'completed' => (int) $r['completed'],
    ];
}, $pdo->query($sql)->fetchAll());

json_out(['period' => $period, 'rows' => $rows]);
