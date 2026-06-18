<?php
/** Update a lead's pipeline stage and/or CRM notes. Body: { id, leadStage?, notes? } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
if ($id <= 0) {
    json_error('A valid appointment id is required', 422);
}

$st = db()->prepare('SELECT id FROM appointments WHERE id = ?');
$st->execute([$id]);
if (!$st->fetch()) {
    json_error('Lead not found', 404);
}

$sets = [];
$args = [];

if (array_key_exists('leadStage', $payload)) {
    $valid = ['new', 'contacted', 'quoted', 'won', 'lost'];
    if (!in_array($payload['leadStage'], $valid, true)) {
        json_error('Invalid lead stage', 422);
    }
    $sets[] = 'lead_stage = ?';
    $args[] = $payload['leadStage'];
}

if (array_key_exists('notes', $payload)) {
    $sets[] = 'crm_notes = ?';
    $args[] = trim((string) $payload['notes']) ?: null;
}

if (!$sets) {
    json_error('Nothing to update', 422);
}

$args[] = $id;
db()->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);

$st = db()->prepare(
    'SELECT a.*,
            COALESCE(u.full_name, a.guest_name) AS customer_name,
            COALESCE(u.email, a.guest_email)    AS customer_email
       FROM appointments a LEFT JOIN users u ON u.id = a.customer_id WHERE a.id = ?'
);
$st->execute([$id]);
json_out(['lead' => $st->fetch()]);
