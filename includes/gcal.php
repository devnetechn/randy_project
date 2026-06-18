<?php
/**
 * Google Calendar sync via a service account — dependency-free (cURL + openssl).
 * We sign a JWT ourselves and exchange it for an access token (no google/apiclient
 * library needed), then create/update/delete calendar events.
 *
 * Best-effort throughout: every public function logs and returns on failure and
 * NEVER throws, so calendar problems can never break a booking. With no calendar
 * configured (missing key file or blank calendar_id), everything no-ops.
 *
 * Setup (one time): create a service account in Google Cloud, enable the Calendar
 * API, download its JSON key to config['google_calendar']['credentials_file'],
 * then share Randy's calendar with the service-account email.
 */
require_once __DIR__ . '/business.php';

function gcal_is_configured(): bool
{
    $c = config('google_calendar');
    return !empty($c['calendar_id'])
        && !empty($c['credentials_file'])
        && is_readable($c['credentials_file']);
}

/** Base64-URL encode (RFC 4648 §5) — no padding, URL-safe alphabet. */
function _gcal_b64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Obtain an OAuth2 access token for the service account. Cached for the request.
 * Returns null on any failure. Throws nothing.
 */
function _gcal_access_token(): ?string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }
    $cached = false; // mark attempted

    try {
        $cfg = config('google_calendar');
        $key = json_decode((string) file_get_contents($cfg['credentials_file']), true);
        if (!is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
            throw new RuntimeException('service-account JSON missing client_email/private_key');
        }
        $tokenUri = $key['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        $now = time();
        $header = _gcal_b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = _gcal_b64url(json_encode([
            'iss'   => $key['client_email'],
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'aud'   => $tokenUri,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));
        $signingInput = $header . '.' . $claims;

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $key['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('JWT signing failed');
        }
        $jwt = $signingInput . '.' . _gcal_b64url($signature);

        $ch = curl_init($tokenUri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code < 200 || $code >= 300) {
            throw new RuntimeException('token exchange HTTP ' . $code . ': ' . $res);
        }
        $data = json_decode($res, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('no access_token in response');
        }
        $cached = $data['access_token'];
        return $cached;
    } catch (Throwable $e) {
        error_log('[gcal] auth failed: ' . $e->getMessage());
        return null;
    }
}

/** Generic Calendar API request. Returns [httpCode, decodedBody]. */
function _gcal_request(string $method, string $url, string $token, ?array $body = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $res === false ? null : json_decode($res, true)];
}

/** Build the event payload from an appointment row. */
function _gcal_event_body(array $appt): array
{
    $cfg = config('google_calendar');
    $tz  = $cfg['timezone'] ?: 'America/New_York';
    $dur = (int) ($cfg['duration_minutes'] ?: 60);

    // Resolve a display name for the title.
    $who = $appt['guest_name'] ?? '';
    if (!empty($appt['customer_id'])) {
        $st = db()->prepare('SELECT full_name FROM users WHERE id = ?');
        $st->execute([$appt['customer_id']]);
        if ($u = $st->fetch()) {
            $who = $u['full_name'];
        }
    }
    $phone = $appt['phone'] ?: ($appt['guest_phone'] ?? '');
    $email = $appt['guest_email'] ?? '';

    // Prefer the confirmed time; fall back to the customer's preferred time.
    $when = $appt['scheduled_at'] ?: $appt['preferred_at'];
    $start = new DateTime($when, new DateTimeZone($tz));
    $end   = (clone $start)->modify('+' . $dur . ' minutes');

    $desc = array_filter([
        $who ? 'Customer: ' . $who : '',
        $phone ? 'Phone: ' . $phone : '',
        $email ? 'Email: ' . $email : '',
        'Status: ' . $appt['status'],
        !empty($appt['notes']) ? 'Notes: ' . $appt['notes'] : '',
        'Booking #' . $appt['id'],
    ]);

    $event = [
        'summary'     => $appt['service_type'] . ($who ? ' — ' . $who : ''),
        'location'    => $appt['address'],
        'description' => implode("\n", $desc),
        'start'       => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
        'end'         => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
    ];

    // Add Randy as an attendee so he can RSVP (Yes/No) from his calendar — the
    // cron sync reads that response back into the CRM. Best-effort: if the service
    // account isn't allowed to add attendees, the caller retries without them.
    $owner = $cfg['owner_email'] ?: $cfg['calendar_id'];
    if ($owner) {
        $event['attendees'] = [['email' => $owner, 'responseStatus' => 'needsAction']];
    }
    return $event;
}

/** The email we read RSVP responses for (Randy). */
function gcal_owner_email(): string
{
    $cfg = config('google_calendar');
    return $cfg['owner_email'] ?: $cfg['calendar_id'];
}

/**
 * Write an event (POST/PATCH). If the service account can't add attendees
 * (no domain-wide delegation → HTTP 403 forbiddenForServiceAccounts), retry
 * once without the attendees so the event is still created/updated.
 * Returns [httpCode, decodedBody].
 */
function _gcal_write(string $method, string $url, string $token, array $body): array
{
    [$code, $data] = _gcal_request($method, $url, $token, $body);
    if ($code === 403 && isset($body['attendees'])) {
        $reason = $data['error']['errors'][0]['reason'] ?? '';
        if ($reason === 'forbiddenForServiceAccounts' || stripos(json_encode($data), 'attendee') !== false) {
            error_log('[gcal] attendees not permitted for service account — retrying without RSVP');
            unset($body['attendees']);
            [$code, $data] = _gcal_request($method, $url, $token, $body);
        }
    }
    return [$code, $data];
}

/**
 * Fetch a single event. Returns the decoded event, a synthetic
 * ['status' => 'cancelled', '_deleted' => true] for 404/410 (deleted), or null
 * on any other error (so callers leave the appointment untouched).
 */
function gcal_get_event(string $eventId): ?array
{
    if (!gcal_is_configured()) {
        return null;
    }
    $token = _gcal_access_token();
    if (!$token) {
        return null;
    }
    $cfg = config('google_calendar');
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($cfg['calendar_id'])
        . '/events/' . rawurlencode($eventId);
    [$code, $data] = _gcal_request('GET', $url, $token);
    if ($code === 404 || $code === 410) {
        return ['status' => 'cancelled', '_deleted' => true];
    }
    if ($code >= 200 && $code < 300 && is_array($data)) {
        return $data;
    }
    return null;
}

/**
 * Create or update the calendar event for an appointment, and persist its event
 * id. Best-effort — logs and returns on any problem.
 */
function gcal_sync_for_appointment(array $appt): void
{
    if (!gcal_is_configured()) {
        return;
    }
    try {
        $token = _gcal_access_token();
        if (!$token) {
            return;
        }
        $cfg  = config('google_calendar');
        $cal  = rawurlencode($cfg['calendar_id']);
        $base = 'https://www.googleapis.com/calendar/v3/calendars/' . $cal . '/events';
        $body = _gcal_event_body($appt);

        if (!empty($appt['gcal_event_id'])) {
            [$code] = _gcal_write('PATCH', $base . '/' . rawurlencode($appt['gcal_event_id']), $token, $body);
            if ($code >= 200 && $code < 300) {
                error_log('[gcal] updated event for #' . $appt['id']);
                return;
            }
            // Event missing/stale (e.g. 404/410) — fall through to recreate.
            error_log('[gcal] update HTTP ' . $code . ' for #' . $appt['id'] . ' — recreating');
        }

        [$code, $data] = _gcal_write('POST', $base, $token, $body);
        if ($code >= 200 && $code < 300 && !empty($data['id'])) {
            db()->prepare('UPDATE appointments SET gcal_event_id = ? WHERE id = ?')
                ->execute([$data['id'], $appt['id']]);
            error_log('[gcal] created event for #' . $appt['id']);
        } else {
            error_log('[gcal] create HTTP ' . $code . ' for #' . $appt['id']);
        }
    } catch (Throwable $e) {
        error_log('[gcal] sync failed for #' . ($appt['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/** Delete the calendar event for an appointment, if any. Best-effort. */
function gcal_delete_for_appointment(array $appt): void
{
    if (!gcal_is_configured() || empty($appt['gcal_event_id'])) {
        return;
    }
    try {
        $token = _gcal_access_token();
        if (!$token) {
            return;
        }
        $cfg = config('google_calendar');
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($cfg['calendar_id'])
            . '/events/' . rawurlencode($appt['gcal_event_id']);
        [$code] = _gcal_request('DELETE', $url, $token);
        // 200/204 = deleted, 404/410 = already gone — all fine.
        db()->prepare('UPDATE appointments SET gcal_event_id = NULL WHERE id = ?')->execute([$appt['id']]);
        error_log('[gcal] deleted event for #' . $appt['id'] . ' (HTTP ' . $code . ')');
    } catch (Throwable $e) {
        error_log('[gcal] delete failed for #' . ($appt['id'] ?? '?') . ': ' . $e->getMessage());
    }
}
