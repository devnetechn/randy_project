<?php
/**
 * SMS alerts to the owner via Twilio's REST API — dependency-free (cURL only).
 * Best-effort, exactly like the email notifications: this NEVER throws, so an
 * SMS failure can never break a booking. With no Twilio config, everything
 * no-ops cleanly.
 */
require_once __DIR__ . '/business.php';

function sms_is_configured(): bool
{
    $c = config('twilio');
    return !empty($c['sid']) && !empty($c['auth_token'])
        && !empty($c['from']) && !empty($c['owner_phone']);
}

/**
 * Low-level send via Twilio. Throws RuntimeException on any error; callers catch.
 */
function twilio_send_sms(array $cfg, string $to, string $body): void
{
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($cfg['sid']) . '/Messages.json';
    $post = http_build_query(['From' => $cfg['from'], 'To' => $to, 'Body' => $body]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_USERPWD        => $cfg['sid'] . ':' . $cfg['auth_token'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        throw new RuntimeException('Twilio request failed: ' . $err);
    }
    if ($code < 200 || $code >= 300) {
        throw new RuntimeException('Twilio HTTP ' . $code . ': ' . $res);
    }
}

/**
 * Text the owner about a new appointment. Best-effort: logs and returns on any
 * problem, never throws, so the booking flow is unaffected.
 */
function notify_owner_sms(array $appointment): void
{
    if (!sms_is_configured()) {
        error_log('[sms] not configured — skipping owner alert for #' . ($appointment['id'] ?? '?'));
        return;
    }

    $cfg = config('twilio');
    try {
        // Contact line: registered customer name, else guest name.
        $who = $appointment['guest_name'] ?? '';
        if (!empty($appointment['customer_id'])) {
            $st = db()->prepare('SELECT full_name FROM users WHERE id = ?');
            $st->execute([$appointment['customer_id']]);
            if ($u = $st->fetch()) {
                $who = $u['full_name'];
            }
        }
        $phone = $appointment['phone'] ?: ($appointment['guest_phone'] ?? '');

        $body = 'New booking: ' . $appointment['service_type']
            . ($who ? ' · ' . $who : '')
            . ($phone ? ' · ' . $phone : '')
            . ' · ' . $appointment['preferred_at']
            . ' · ' . $appointment['address'];

        twilio_send_sms($cfg, $cfg['owner_phone'], $body);
        error_log('[sms] owner alert sent for #' . $appointment['id']);
    } catch (Throwable $e) {
        error_log('[sms] owner alert failed for #' . ($appointment['id'] ?? '?') . ': ' . $e->getMessage());
    }
}
