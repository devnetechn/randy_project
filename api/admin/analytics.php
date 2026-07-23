<?php
/**
 * Admin dashboard chart: daily counts within one month.
 *
 * GET ?month=YYYY-MM — omit for the newest month that has data.
 * Also returns the list of months that have any data, so the client can build
 * its picker from a single request.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$pdo = db();

// Months with data in any of the three source tables, newest first.
$months = $pdo->query(
    "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS m FROM appointments
     UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM conversations
     UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM users WHERE role = 'customer'
     ORDER BY m DESC"
)->fetchAll(PDO::FETCH_COLUMN);

$month = trim((string) ($_GET['month'] ?? ''));
if ($month !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    json_error('Month must be in YYYY-MM form', 422);
}
if ($month === '') {
    // No data anywhere: fall back to the current month so the chart still draws.
    $month = $months[0] ?? date('Y-m');
}

$start = $month . '-01';
// Half-open upper bound. Computed here rather than as `:start + INTERVAL 1 MONTH`
// because PDO runs with ATTR_EMULATE_PREPARES off, where a named placeholder
// cannot be reused within one statement.
$end = date('Y-m-d', strtotime($start . ' +1 month'));

// The current month is truncated at today — days that have not happened yet
// would otherwise read as a collapse to zero rather than as absent data.
$lastDay = $month === date('Y-m')
    ? (int) date('j')
    : (int) date('t', strtotime($start));

$series = function (string $sql) use ($pdo, $start, $end, $lastDay): array {
    $counts = array_fill(1, $lastDay, 0);
    $st = $pdo->prepare($sql);
    $st->execute(['start' => $start, 'end' => $end]);
    foreach ($st->fetchAll() as $row) {
        $d = (int) $row['d'];
        if ($d >= 1 && $d <= $lastDay) {
            $counts[$d] = (int) $row['total'];
        }
    }
    return array_values($counts);
};

$bookings = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM appointments
      WHERE created_at >= :start AND created_at < :end
      GROUP BY d"
);
$leads = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM conversations
      WHERE created_at >= :start AND created_at < :end
      GROUP BY d"
);
$signups = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM users
      WHERE role = 'customer'
        AND created_at >= :start AND created_at < :end
      GROUP BY d"
);

json_out([
    'months'   => array_map(
        fn ($m) => ['value' => $m, 'label' => date('F Y', strtotime($m . '-01'))],
        $months
    ),
    'month'    => $month,
    'labels'   => array_map('strval', range(1, $lastDay)),
    'bookings' => $bookings,
    'leads'    => $leads,
    'signups'  => $signups,
]);
