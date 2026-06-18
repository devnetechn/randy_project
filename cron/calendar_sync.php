<?php
/**
 * Calendar → Website sync. Pulls changes Randy makes in Google Calendar back
 * into the CRM. Run on a schedule (e.g. every 5 minutes) via cron:
 *
 *   * /5 * * * *  php /home/USER/public_html/cron/calendar_sync.php
 *
 * Mapping (Google Calendar → CRM):
 *   - event deleted        → appointment status = cancelled
 *   - Randy RSVP "No"      → appointment status = declined
 *   - Randy RSVP "Yes"     → appointment status = confirmed
 *   - event time moved     → scheduled_at updated (+ confirmed)
 *
 * Idempotent and best-effort: an API hiccup just skips that appointment this run.
 * Safe to run from the CLI or via a cron HTTP hit.
 */
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/gcal.php';

if (!gcal_is_configured()) {
    fwrite(STDOUT, "[calendar_sync] Google Calendar not configured — nothing to do.\n");
    return;
}

$cfg   = config('google_calendar');
$tz    = new DateTimeZone($cfg['timezone'] ?: 'America/New_York');
$owner = strtolower(gcal_owner_email());

/** Normalise any datetime (with/without offset) to MySQL DATETIME in our TZ. */
$norm = function (?string $dt, ?string $srcTz = null) use ($tz): ?string {
    if (!$dt) {
        return null;
    }
    try {
        $d = new DateTime($dt, new DateTimeZone($srcTz ?: $tz->getName()));
        $d->setTimezone($tz);
        return $d->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return null;
    }
};

// Only active appointments that we actually synced to the calendar.
$rows = db()->query(
    "SELECT * FROM appointments
      WHERE gcal_event_id IS NOT NULL AND status IN ('pending','confirmed')"
)->fetchAll();

$changed = 0;
foreach ($rows as $a) {
    $ev = gcal_get_event($a['gcal_event_id']);
    if ($ev === null) {
        continue; // API error — leave this one alone this run
    }

    // Deleted / cancelled on the calendar.
    if (!empty($ev['_deleted']) || ($ev['status'] ?? '') === 'cancelled') {
        db()->prepare("UPDATE appointments SET status='cancelled', gcal_event_id=NULL WHERE id=?")
            ->execute([$a['id']]);
        fwrite(STDOUT, "[calendar_sync] #{$a['id']} → cancelled (event removed)\n");
        $changed++;
        continue;
    }

    $status    = $a['status'];
    $scheduled = $a['scheduled_at'];

    // Randy's RSVP, if present.
    $resp = null;
    foreach (($ev['attendees'] ?? []) as $att) {
        if (strtolower($att['email'] ?? '') === $owner) {
            $resp = $att['responseStatus'] ?? null;
            break;
        }
    }

    // What time does the event currently reflect vs. what we last stored?
    $startNorm = $norm($ev['start']['dateTime'] ?? null, $ev['start']['timeZone'] ?? null);
    $refNorm   = $norm($a['scheduled_at'] ?: $a['preferred_at']);
    $moved     = $startNorm && $refNorm && $startNorm !== $refNorm;

    if ($resp === 'declined') {
        $status = 'declined';
    } elseif ($resp === 'accepted') {
        $status    = 'confirmed';
        $scheduled = $startNorm ?: $scheduled;
    }
    if ($moved && $status !== 'declined') {
        $status    = 'confirmed';
        $scheduled = $startNorm;
    }

    if ($status !== $a['status'] || $scheduled !== $a['scheduled_at']) {
        db()->prepare('UPDATE appointments SET status=?, scheduled_at=? WHERE id=?')
            ->execute([$status, $scheduled, $a['id']]);
        fwrite(STDOUT, "[calendar_sync] #{$a['id']} → status={$status} scheduled={$scheduled}\n");
        $changed++;
    }
}

fwrite(STDOUT, "[calendar_sync] done — {$changed} appointment(s) updated.\n");
