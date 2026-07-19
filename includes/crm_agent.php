<?php
/** The CRM autonomous agent — decides & executes the next action per lead. */

const CRM_AGENT_VALID_STAGES = ['new', 'contacted', 'quoted', 'won', 'lost'];

/** Shared stage-update logic used by both the admin API (api/crm/update.php) and the agent. */
function crm_set_lead_stage(int $id, string $stage): void
{
    if (!in_array($stage, CRM_AGENT_VALID_STAGES, true)) {
        throw new InvalidArgumentException('Invalid lead stage: ' . $stage);
    }
    $sets = ['lead_stage = ?'];
    $args = [$stage];
    if (in_array($stage, ['contacted', 'quoted'], true)) {
        $sets[] = 'crm_last_email_at = NOW()';
    }
    $args[] = $id;
    db()->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);
}

function crm_agent_enabled(): bool
{
    return setting_get('crm_agent_enabled', '1') === '1';
}

function crm_agent_max_per_run(): int
{
    return (int) setting_get('crm_agent_max_per_run', '20');
}

/** Write one audit row. Never throws — a logging failure shouldn't break the agent. */
function crm_agent_log(int $appointmentId, string $action, ?string $reasoning): void
{
    try {
        db()->prepare(
            'INSERT INTO crm_agent_log (appointment_id, action, reasoning) VALUES (?, ?, ?)'
        )->execute([$appointmentId, $action, $reasoning]);
    } catch (Throwable $e) {
        error_log('[crm_agent] failed to write log for #' . $appointmentId . ': ' . $e->getMessage());
    }
}

/** Deterministic pre-filter: leads plausibly needing a review. */
function crm_agent_leads_needing_review(int $limit = 20): array
{
    $st = db()->prepare(
        "SELECT a.*,
                COALESCE(u.full_name, a.guest_name) AS customer_name,
                COALESCE(u.email, a.guest_email)    AS customer_email
           FROM appointments a LEFT JOIN users u ON u.id = a.customer_id
          WHERE a.lead_stage = 'new'
             OR (a.lead_stage IN ('contacted', 'quoted')
                 AND a.crm_last_email_at IS NOT NULL
                 AND a.crm_last_email_at <= NOW() - INTERVAL 24 HOUR)
          ORDER BY a.updated_at ASC
          LIMIT ?"
    );
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/**
 * Review one lead: ask Claude, execute the decision, log the outcome.
 * Best-effort — never throws, matching this codebase's existing notification pattern.
 */
function crm_agent_review_lead(array $lead): void
{
    if (!crm_agent_enabled()) {
        return;
    }
    $id = (int) $lead['id'];

    try {
        require_once __DIR__ . '/claude.php';
        $decision = claude_decide_action($lead);
    } catch (Throwable $e) {
        error_log('[crm_agent] decision failed for #' . $id . ': ' . $e->getMessage());
        crm_agent_log($id, 'error', $e->getMessage());
        return;
    }

    $action = $decision['action'];
    try {
        switch ($action) {
            case 'update_stage':
                crm_set_lead_stage($id, $decision['lead_stage']);
                if (in_array($decision['lead_stage'], ['contacted', 'quoted'], true)) {
                    require_once __DIR__ . '/email.php';
                    send_crm_stage_email($lead, $decision['lead_stage']);
                }
                break;
            case 'send_email':
                require_once __DIR__ . '/email.php';
                send_crm_stage_email($lead, $lead['lead_stage'] ?? 'contacted', true);
                break;
            case 'alert_owner_sms':
                require_once __DIR__ . '/sms.php';
                if (sms_is_configured()) {
                    $cfg = config('twilio');
                    twilio_send_sms($cfg, $cfg['owner_phone'], 'CRM alert — lead #' . $id . ': ' . $decision['reasoning']);
                }
                break;
            case 'sync_calendar':
                require_once __DIR__ . '/gcal.php';
                gcal_sync_for_appointment($lead);
                break;
            case 'no_action':
                break;
        }
        crm_agent_log($id, $action, $decision['reasoning']);
    } catch (Throwable $e) {
        error_log('[crm_agent] action failed for #' . $id . ' (' . $action . '): ' . $e->getMessage());
        crm_agent_log($id, 'error', $e->getMessage());
    }
}
