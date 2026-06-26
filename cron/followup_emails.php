<?php
/**
 * CRM follow-up emails. Run every hour (or every 30 min) via cron:
 *
 *   0 * * * *  php /home/USER/public_html/cron/followup_emails.php
 *
 * Sends one follow-up email to any lead that:
 *   - is in stage 'contacted' or 'quoted'
 *   - was emailed 24+ hours ago (crm_last_email_at IS NOT NULL)
 * After sending, sets crm_last_email_at = NULL so it only fires once per stage.
 */
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/email.php';

if (!email_is_configured()) {
    fwrite(STDOUT, "[followup] Email not configured — nothing to do.\n");
    return;
}

$rows = db()->query(
    "SELECT a.*,
            COALESCE(u.full_name, a.guest_name) AS customer_name,
            COALESCE(u.email, a.guest_email)    AS customer_email
       FROM appointments a LEFT JOIN users u ON u.id = a.customer_id
      WHERE a.lead_stage IN ('contacted', 'quoted')
        AND a.crm_last_email_at IS NOT NULL
        AND a.crm_last_email_at <= NOW() - INTERVAL 24 HOUR"
)->fetchAll();

$sent = 0;
foreach ($rows as $appt) {
    send_crm_stage_email($appt, $appt['lead_stage'], true);
    // Clear so we don't keep re-sending every hour.
    db()->prepare('UPDATE appointments SET crm_last_email_at = NULL WHERE id = ?')
        ->execute([$appt['id']]);
    fwrite(STDOUT, "[followup] #{$appt['id']} follow-up sent → " . ($appt['customer_email'] ?? '?') . "\n");
    $sent++;
}

fwrite(STDOUT, "[followup] done — {$sent} follow-up(s) sent.\n");
