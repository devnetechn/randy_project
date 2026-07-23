<?php
/**
 * Admin booking contact list: one row per booking with the customer's contact
 * details, for a date range based on when the booking was submitted
 * (created_at) — not the scheduled service date, matching reports.php.
 *
 * GET ?from=YYYY-MM-DD&to=YYYY-MM-DD — omit both for all time.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

/** True only for a real calendar date in YYYY-MM-DD form. */
function contact_report_valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

$from = trim((string) ($_GET['from'] ?? ''));
$to   = trim((string) ($_GET['to'] ?? ''));

if ($from !== '' && !contact_report_valid_date($from)) {
    json_error('Start date must be a valid date (YYYY-MM-DD)', 422);
}
if ($to !== '' && !contact_report_valid_date($to)) {
    json_error('End date must be a valid date (YYYY-MM-DD)', 422);
}

// One side given, the other blank: fill the blank with a sensible bound so the
// range is never half-open.
if ($from !== '' && $to === '') {
    $to = date('Y-m-d');
} elseif ($from === '' && $to !== '') {
    $from = date('Y-m-01');
}

if ($from !== '' && $from > $to) {
    json_error('Start date must be on or before the end date', 422);
}

$select =
    'SELECT a.id, a.created_at, a.status, a.service_type,
            COALESCE(u.full_name, a.guest_name)  AS customer_name,
            COALESCE(u.email,     a.guest_email) AS customer_email,
            COALESCE(a.phone,     a.guest_phone) AS customer_phone
       FROM appointments a
       LEFT JOIN users u ON u.id = a.customer_id';

if ($from !== '') {
    // `< to + 1 day` rather than `<= to` so a booking made at 14:30 on the end
    // date is still inside the range.
    $sql  = $select . ' WHERE a.created_at >= ? AND a.created_at < ? + INTERVAL 1 DAY
                        ORDER BY a.created_at DESC LIMIT 2000';
    $args = [$from, $to];
} else {
    $sql  = $select . ' ORDER BY a.created_at DESC LIMIT 2000';
    $args = [];
}

$st = db()->prepare($sql);
$st->execute($args);

$rows = array_map(function ($r) {
    return [
        'id'          => (int) $r['id'],
        'createdAt'   => $r['created_at'],
        'name'        => $r['customer_name'] ?: 'Guest',
        'phone'       => $r['customer_phone'] ?: '',
        'email'       => $r['customer_email'] ?: '',
        'serviceType' => $r['service_type'],
        'status'      => $r['status'],
    ];
}, $st->fetchAll());

json_out([
    'rows' => $rows,
    'from' => $from !== '' ? $from : null,
    'to'   => $to   !== '' ? $to   : null,
]);
