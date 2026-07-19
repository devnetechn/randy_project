<?php
/** Update a lead's pipeline stage and/or CRM notes. Body: { id, leadStage?, notes? } */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/crm_agent.php';

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

$emailStage = null;
if (array_key_exists('leadStage', $payload)) {
    try {
        crm_set_lead_stage($id, $payload['leadStage']);
    } catch (InvalidArgumentException $e) {
        json_error('Invalid lead stage', 422);
    }
    if (in_array($payload['leadStage'], ['contacted', 'quoted'], true)) {
        $emailStage = $payload['leadStage'];
    }
}

if (array_key_exists('notes', $payload)) {
    db()->prepare('UPDATE appointments SET crm_notes = ? WHERE id = ?')
        ->execute([trim((string) $payload['notes']) ?: null, $id]);
}

$st = db()->prepare(
    'SELECT a.*,
            COALESCE(u.full_name, a.guest_name) AS customer_name,
            COALESCE(u.email, a.guest_email)    AS customer_email
       FROM appointments a LEFT JOIN users u ON u.id = a.customer_id WHERE a.id = ?'
);
$st->execute([$id]);
$lead = $st->fetch();

if ($emailStage && $lead) {
    require_once __DIR__ . '/../../includes/email.php';
    send_crm_stage_email($lead, $emailStage);
}

// Let the CRM agent react to this manual stage/notes change.
if ($lead) {
    crm_agent_review_lead($lead);
}

json_out(['lead' => $lead]);
