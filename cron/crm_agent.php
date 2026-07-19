<?php
// CRM autonomous agent sweep. Run periodically via cron, e.g. every 30 minutes:
//
//   */30 * * * *  php /home/USER/public_html/cron/crm_agent.php
//
// Reviews leads needing attention and lets Claude decide + execute the next
// action for each (see includes/crm_agent.php). Safe to re-run — capped per
// run and gated by the crm_agent_enabled kill switch.
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/crm_agent.php';

if (!crm_agent_enabled()) {
    fwrite(STDOUT, "[crm_agent] disabled via kill switch — nothing to do.\n");
    return;
}

$leads = crm_agent_leads_needing_review(crm_agent_max_per_run());
fwrite(STDOUT, '[crm_agent] reviewing ' . count($leads) . " lead(s).\n");

foreach ($leads as $lead) {
    crm_agent_review_lead($lead);
    fwrite(STDOUT, '[crm_agent] #' . $lead['id'] . " reviewed.\n");
}

fwrite(STDOUT, "[crm_agent] done.\n");
