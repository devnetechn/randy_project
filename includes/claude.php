<?php
/** Anthropic Claude integration via cURL — powers the CRM autonomous agent. */

const CLAUDE_CRM_ACTIONS = ['update_stage', 'send_email', 'alert_owner_sms', 'sync_calendar', 'no_action'];
const CLAUDE_CRM_STAGES  = ['new', 'contacted', 'quoted', 'won', 'lost'];

function claude_is_configured(): bool
{
    $c = config('anthropic');
    return !empty($c['api_key']);
}

/**
 * Validate + normalize Claude's raw text response into a decision array.
 * Pure function — no network/DB access — so it's directly testable.
 * Throws InvalidArgumentException on any malformed/out-of-contract response.
 */
function claude_parse_decision(string $raw): array
{
    $data = json_decode(trim($raw), true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('Response is not valid JSON: ' . $raw);
    }
    $action = $data['action'] ?? null;
    if (!in_array($action, CLAUDE_CRM_ACTIONS, true)) {
        throw new InvalidArgumentException('Unknown action: ' . json_encode($action));
    }
    $decision = [
        'action'    => $action,
        'reasoning' => is_string($data['reasoning'] ?? null) ? $data['reasoning'] : '',
    ];
    if ($action === 'update_stage') {
        $stage = $data['lead_stage'] ?? null;
        if (!in_array($stage, CLAUDE_CRM_STAGES, true)) {
            throw new InvalidArgumentException('update_stage requires a valid lead_stage: ' . json_encode($stage));
        }
        $decision['lead_stage'] = $stage;
    }
    return $decision;
}

/** System prompt describing the CRM's stages and the fixed action contract. */
function claude_crm_system_prompt(): string
{
    return "You are a CRM assistant for a painting & drywall business. Given a lead's " .
        "current state, decide the single best next action. Respond with ONLY a JSON " .
        "object, no other text, in exactly this shape:\n" .
        '{"action": "update_stage|send_email|alert_owner_sms|sync_calendar|no_action", ' .
        '"lead_stage": "new|contacted|quoted|won|lost" (only if action is update_stage), ' .
        '"reasoning": "one sentence why"}' . "\n\n" .
        "Rules: move a lead from 'new' to 'contacted' once it's been reviewed for the " .
        "first time. Use 'send_email' for a stage-appropriate nudge if the lead has gone " .
        "quiet. Use 'alert_owner_sms' only when a lead needs a human's urgent attention " .
        "(e.g. gone cold after a quote, or an unusual situation). Use 'sync_calendar' " .
        "only if the lead was just confirmed/rescheduled/completed and might be out of " .
        "sync. Prefer 'no_action' when nothing productive can be done yet.";
}

/** Call Claude's Messages API for one lead. Throws on any failure. */
function claude_decide_action(array $lead): array
{
    $c = config('anthropic');
    if (empty($c['api_key'])) {
        throw new RuntimeException('Anthropic is not configured');
    }
    $model = $c['model'] ?: 'claude-sonnet-5';

    $snapshot = [
        'lead_stage'        => $lead['lead_stage'] ?? 'new',
        'status'            => $lead['status'] ?? null,
        'service_type'      => $lead['service_type'] ?? null,
        'created_at'        => $lead['created_at'] ?? null,
        'crm_notes'         => $lead['crm_notes'] ?? null,
        'crm_last_email_at' => $lead['crm_last_email_at'] ?? null,
    ];

    $payload = [
        'model'      => $model,
        'max_tokens' => 300,
        'system'     => claude_crm_system_prompt(),
        'messages'   => [
            ['role' => 'user', 'content' => json_encode($snapshot)],
        ],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $c['api_key'],
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 20,
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false || $err) {
        throw new RuntimeException('Claude request failed: ' . $err);
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Claude returned HTTP {$status}: {$body}");
    }
    $data = json_decode($body, true);
    $text = $data['content'][0]['text'] ?? null;
    if (!is_string($text) || $text === '') {
        throw new RuntimeException('Claude returned no text');
    }
    return claude_parse_decision($text);
}
