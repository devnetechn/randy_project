<?php
/** Admin dashboard trend chart: monthly counts for the last 6 months. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$pdo = db();
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m-01', strtotime("first day of -$i month"));
}
$start = $months[0];

$series = function (string $sql) use ($pdo, $months, $start): array {
    $counts = array_fill_keys($months, 0);
    $st = $pdo->prepare($sql);
    $st->execute(['start' => $start]);
    foreach ($st->fetchAll() as $row) {
        $counts[$row['period']] = (int) $row['total'];
    }
    return array_values($counts);
};

$bookings = $series(
    "SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period, COUNT(*) AS total
       FROM appointments WHERE created_at >= :start GROUP BY period"
);
$leads = $series(
    "SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period, COUNT(*) AS total
       FROM conversations WHERE created_at >= :start GROUP BY period"
);
$signups = $series(
    "SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period, COUNT(*) AS total
       FROM users WHERE role = 'customer' AND created_at >= :start GROUP BY period"
);

json_out([
    'months'   => array_map(fn ($m) => date('M Y', strtotime($m)), $months),
    'bookings' => $bookings,
    'leads'    => $leads,
    'signups'  => $signups,
]);
