<?php
/**
 * Email notifications via Gmail SMTP — dependency-free (native PHP sockets).
 * Booking notifications are best-effort: they NEVER throw, so a mail failure
 * can never break a booking. With no App Password configured, everything no-ops.
 */
require_once __DIR__ . '/business.php';

function email_is_configured(): bool
{
    $c = config('email');
    return !empty($c['user']) && !empty($c['password']);
}

/**
 * Minimal SMTP client (SSL or STARTTLS, per $cfg['smtp_secure']).
 * $to may be a single address or an array — every address gets the same
 * message in one send so recipients land together, not as separate emails.
 * Throws RuntimeException on any protocol error; callers should catch.
 */
function smtp_send_mail(array $cfg, $to, string $subject, string $body): void
{
    $host   = $cfg['smtp_host'] ?? 'smtp.gmail.com';
    $port   = (int) ($cfg['smtp_port'] ?? 587);
    $secure = strtolower($cfg['smtp_secure'] ?? 'tls'); // 'ssl' (implicit TLS) or 'tls' (STARTTLS)
    $user   = $cfg['user'];
    $pass   = $cfg['password'];

    $recipients = array_values(array_filter(is_array($to) ? $to : [$to]));
    if (!$recipients) {
        throw new RuntimeException('SMTP send: no recipients');
    }

    $transport = $secure === 'ssl' ? 'ssl://' : 'tcp://';
    $fp = @stream_socket_client("{$transport}{$host}:{$port}", $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
    }
    stream_set_timeout($fp, 15);

    // Read a full (possibly multi-line) SMTP reply.
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 600)) !== false) {
            $data .= $line;
            // Last line of a reply has a space as the 4th char ("250 ok"),
            // continuation lines use a hyphen ("250-...").
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $send = function (string $cmd) use ($fp): void { fwrite($fp, $cmd . "\r\n"); };
    $expect = function (string $reply, string $code): void {
        if (strncmp($reply, $code, strlen($code)) !== 0) {
            throw new RuntimeException('SMTP error, expected ' . $code . ' got: ' . trim($reply));
        }
    };

    $expect($read(), '220');
    $send('EHLO localhost'); $expect($read(), '250');

    if ($secure === 'tls') {
        $send('STARTTLS'); $expect($read(), '220');
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            throw new RuntimeException('SMTP TLS negotiation failed');
        }
        $send('EHLO localhost'); $expect($read(), '250');
    }

    $send('AUTH LOGIN');               $expect($read(), '334');
    $send(base64_encode($user));       $expect($read(), '334');
    $send(base64_encode($pass));       $expect($read(), '235');
    $send('MAIL FROM:<' . $user . '>'); $expect($read(), '250');
    foreach ($recipients as $rcpt) {
        $send('RCPT TO:<' . $rcpt . '>'); $expect($read(), '250');
    }
    $send('DATA');                      $expect($read(), '354');

    $headers =
        'From: ' . business_info()['name'] . ' <' . $user . ">\r\n" .
        'To: <' . implode('>, <', $recipients) . ">\r\n" .
        'Subject: ' . $subject . "\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n";

    // RFC 5321 dot-stuffing: a line starting with "." must be doubled.
    $bodyOut = preg_replace('/^\./m', '..', $body);
    $send($headers . "\r\n" . $bodyOut . "\r\n.");
    $expect($read(), '250');

    $send('QUIT');
    fclose($fp);
}

/**
 * Send a "new booking" alert. Best-effort: logs and returns on any problem,
 * never throws, so the booking flow is unaffected.
 */
function send_booking_notification(array $appointment): void
{
    if (!email_is_configured()) {
        error_log('[email] not configured — skipping booking notification for #' . ($appointment['id'] ?? '?'));
        return;
    }

    $cfg = config('email');
    $to = $cfg['to'] ?: $cfg['user'];

    try {
        // Best-effort contact lookup: registered customer, else guest fields.
        $name = $appointment['guest_name'] ?? '';
        $email = $appointment['guest_email'] ?? '';
        if (!empty($appointment['customer_id'])) {
            $st = db()->prepare('SELECT full_name, email FROM users WHERE id = ?');
            $st->execute([$appointment['customer_id']]);
            if ($u = $st->fetch()) {
                $name = $u['full_name'];
                $email = $u['email'];
            }
        }

        $b = business_info();
        $subject = 'New booking request — ' . $appointment['service_type'];
        $body = implode("\r\n", [
            'A new appointment request was submitted on ' . $b['name'] . '.',
            '',
            'Customer:  ' . trim($name . ' <' . $email . '>'),
            'Phone:     ' . ($appointment['phone'] ?: '—'),
            'Service:   ' . $appointment['service_type'],
            'Preferred: ' . $appointment['preferred_at'],
            'Address:   ' . $appointment['address'],
            'Notes:     ' . ($appointment['notes'] ?: '—'),
            '',
            'Booking #' . $appointment['id'] . ' · status: ' . $appointment['status'],
        ]);

        smtp_send_mail($cfg, $to, $subject, $body);
        error_log('[email] booking notification sent for #' . $appointment['id']);
    } catch (Throwable $e) {
        error_log('[email] booking notification failed for #' . ($appointment['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/**
 * Send appointment confirmed/declined email to the customer. Best-effort.
 */
function send_appointment_status_email(array $appt, string $action): void
{
    if (!email_is_configured()) {
        return;
    }

    $name  = $appt['customer_name'] ?? $appt['guest_name'] ?? '';
    $email = $appt['customer_email'] ?? $appt['guest_email'] ?? '';
    if (!empty($appt['customer_id']) && (!$name || !$email)) {
        $st = db()->prepare('SELECT full_name, email FROM users WHERE id = ?');
        $st->execute([$appt['customer_id']]);
        if ($u = $st->fetch()) { $name = $u['full_name']; $email = $u['email']; }
    }
    if (!$email) {
        return;
    }

    $cfg  = config('email');
    $b    = business_info();
    $hi   = $name ? 'Hi ' . $name . ',' : 'Hello,';
    $svc  = $appt['service_type'] ?? 'your service';
    $addr = $appt['address'] ?? '';
    $sig  = implode("\r\n", ['', $b['owner'], $b['name'], $b['phone'], $b['website']]);

    try {
        if ($action === 'confirm') {
            $when = $appt['scheduled_at'] ?: $appt['preferred_at'];
            $subject = 'Your appointment is confirmed — ' . $b['name'];
            $body = implode("\r\n", [
                $hi,
                '',
                'Great news — your ' . $svc . ' appointment' . ($addr ? ' at ' . $addr : '') . ' is confirmed for:',
                '',
                '  ' . date('l, F j, Y \a\t g:i A', strtotime($when)),
                '',
                'If you need to reschedule or have any questions, call or text me at ' . $b['phone'] . ' or reply to this email.',
                '',
                'Looking forward to it!',
                $sig,
            ]);
        } else {
            // decline
            $subject = 'About your estimate request — ' . $b['name'];
            $reason  = !empty($appt['decline_reason']) ? "\r\n\r\nNote: " . $appt['decline_reason'] : '';
            $body = implode("\r\n", [
                $hi,
                '',
                "Unfortunately we're unable to accommodate your " . $svc . ' request at this time.' . $reason,
                '',
                'Please don\'t hesitate to reach out again — call or text ' . $b['phone'] . ' and we\'ll do our best to find a time that works.',
                $sig,
            ]);
        }

        smtp_send_mail($cfg, $email, $subject, $body);
        error_log('[email] appointment ' . $action . ' email sent for #' . $appt['id'] . ' → ' . $email);
    } catch (Throwable $e) {
        error_log('[email] appointment status email failed for #' . ($appt['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/**
 * Thank-you + review request email sent when a job is marked complete.
 * Links the customer to review.php (pre-filled with their name + service).
 * Best-effort — never throws.
 */
function send_completion_email(array $appt): void
{
    if (!email_is_configured()) {
        return;
    }

    $name  = $appt['customer_name'] ?? $appt['guest_name'] ?? '';
    $email = $appt['customer_email'] ?? $appt['guest_email'] ?? '';
    if (!empty($appt['customer_id']) && (!$name || !$email)) {
        $st = db()->prepare('SELECT full_name, email FROM users WHERE id = ?');
        $st->execute([$appt['customer_id']]);
        if ($u = $st->fetch()) { $name = $u['full_name']; $email = $u['email']; }
    }
    if (!$email) {
        return;
    }

    $cfg  = config('email');
    $b    = business_info();
    $hi   = $name ? 'Hi ' . $name . ',' : 'Hello,';
    $svc  = $appt['service_type'] ?? '';
    $sig  = implode("\r\n", ['', $b['owner'], $b['name'], $b['phone'], $b['website']]);

    // Build the review URL — pre-fill name + service for a frictionless experience.
    $reviewPath = 'review.php?' . http_build_query(array_filter([
        'name'    => $name,
        'service' => $svc,
    ]));
    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'randyspaintdrywall.com';
    $siteBase  = $scheme . '://' . $host . app_base_path();
    $reviewUrl = rtrim($siteBase, '/') . '/' . $reviewPath;

    // Also pull the Google review URL if the admin has set one.
    $googleUrl = setting_get('google_reviews_review_url', '');

    try {
        $subject = 'Thank you, ' . ($name ?: 'valued customer') . '! — ' . $b['name'];
        $lines = [
            $hi,
            '',
            'Thank you for choosing ' . $b['name'] . '! It was a pleasure working on your ' .
                ($svc ? $svc . ' project' : 'project') .
                ($appt['address'] ? ' at ' . $appt['address'] : '') . '.',
            '',
            'If you have a moment, we\'d really appreciate a quick review — it helps us a lot:',
            '',
            '  ' . $reviewUrl,
        ];
        if ($googleUrl) {
            $lines[] = '';
            $lines[] = 'Or leave us a Google review here:';
            $lines[] = '  ' . $googleUrl;
        }
        $lines = array_merge($lines, [
            '',
            'Thank you again — we hope to work with you again in the future!',
            $sig,
        ]);

        smtp_send_mail($cfg, $email, $subject, implode("\r\n", $lines));
        error_log('[email] completion email sent for #' . $appt['id'] . ' → ' . $email);
    } catch (Throwable $e) {
        error_log('[email] completion email failed for #' . ($appt['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/**
 * Email the customer when the admin moves a lead to 'contacted' or 'quoted',
 * and again 24 hours later if they haven't responded (follow-up).
 * Best-effort — never throws.
 */
function send_crm_stage_email(array $appt, string $stage, bool $isFollowup = false): void
{
    if (!email_is_configured()) {
        return;
    }

    // Resolve customer name + email from registered user or guest fields.
    $name  = $appt['customer_name'] ?? $appt['guest_name'] ?? '';
    $email = $appt['customer_email'] ?? $appt['guest_email'] ?? '';
    if (!empty($appt['customer_id']) && (!$name || !$email)) {
        $st = db()->prepare('SELECT full_name, email FROM users WHERE id = ?');
        $st->execute([$appt['customer_id']]);
        if ($u = $st->fetch()) { $name = $u['full_name']; $email = $u['email']; }
    }
    if (!$email) {
        error_log('[email] crm stage email skipped #' . ($appt['id'] ?? '?') . ' — no customer email');
        return;
    }

    $cfg  = config('email');
    $b    = business_info();
    $hi   = $name ? 'Hi ' . $name . ',' : 'Hello,';
    $svc  = $appt['service_type'] ?? 'your service';
    $addr = $appt['address'] ?? '';
    $sig  = implode("\r\n", [
        '',
        $b['owner'],
        $b['name'],
        $b['phone'],
        $b['website'],
    ]);

    try {
        if ($isFollowup) {
            $subject = 'Following up — your ' . $svc . ' estimate';
            $body = implode("\r\n", [
                $hi,
                '',
                'Just following up on your ' . $svc . ' estimate' . ($addr ? ' for ' . $addr : '') . '.',
                'I want to make sure you have everything you need to move forward.',
                '',
                'Feel free to reply here or call/text me at ' . $b['phone'] . ' — no pressure at all!',
                $sig,
            ]);
        } elseif ($stage === 'quoted') {
            $subject = 'Your estimate is ready — ' . $b['name'];
            $body = implode("\r\n", [
                $hi,
                '',
                "I've put together your estimate for " . $svc . ($addr ? ' at ' . $addr : '') . '.',
                'Please reply to this email or call/text me at ' . $b['phone'] . ' to go over the details.',
                '',
                'Looking forward to working with you!',
                $sig,
            ]);
        } else {
            // 'contacted'
            $subject = 'Re: Your estimate request — ' . $svc;
            $body = implode("\r\n", [
                $hi,
                '',
                'This is Randy from ' . $b['name'] . '. I saw your request for ' . $svc .
                    ($addr ? ' at ' . $addr : '') . ' and will be reaching out shortly to schedule your free estimate.',
                '',
                'If you\'d like to talk sooner, call or text me at ' . $b['phone'] . ' or just reply to this email.',
                $sig,
            ]);
        }

        smtp_send_mail($cfg, $email, $subject, $body);
        error_log('[email] crm stage email (' . ($isFollowup ? 'followup' : $stage) . ') sent for #' . $appt['id'] . ' → ' . $email);
    } catch (Throwable $e) {
        error_log('[email] crm stage email failed for #' . ($appt['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/**
 * Send a "new job application" alert. Best-effort: logs and returns on any
 * problem, never throws, so the application flow is unaffected.
 */
function send_job_application_notification(array $application): void
{
    if (!email_is_configured()) {
        error_log('[email] not configured — skipping job application notification for #' . ($application['id'] ?? '?'));
        return;
    }

    $cfg = config('email');
    $to = $cfg['to'] ?: $cfg['user'];

    try {
        $b = business_info();
        $subject = 'New job application — ' . $application['position_title_snapshot'];
        $body = implode("\r\n", [
            'A new job application was submitted on ' . $b['name'] . '.',
            '',
            'Applicant:  ' . $application['name'],
            'Email:      ' . $application['email'],
            'Phone:      ' . $application['phone'],
            'Position:   ' . $application['position_title_snapshot'],
            'Experience: ' . ($application['years_experience'] ?: '—'),
            'Available:  ' . ($application['availability'] ?: '—'),
            'Message:    ' . ($application['message'] ?: '—'),
            '',
            'Application #' . $application['id'] . ' — view and download the resume in the admin dashboard (Careers tab).',
        ]);

        smtp_send_mail($cfg, $to, $subject, $body);
        error_log('[email] job application notification sent for #' . $application['id']);
    } catch (Throwable $e) {
        error_log('[email] job application notification failed for #' . ($application['id'] ?? '?') . ': ' . $e->getMessage());
    }
}

/**
 * Email the applicant when the admin changes their application status.
 * Best-effort — never throws.
 */
function send_job_application_status_email(array $application, string $status): void
{
    if (!email_is_configured()) {
        return;
    }
    $email = $application['email'] ?? '';
    if (!$email) {
        error_log('[email] application status email skipped #' . ($application['id'] ?? '?') . ' — no applicant email');
        return;
    }

    $cfg  = config('email');
    $b    = business_info();
    $name = $application['name'] ?? '';
    $hi   = $name ? 'Hi ' . $name . ',' : 'Hello,';
    $position = $application['position_title'] ?? $application['position_title_snapshot'] ?? 'the position';
    $sig = implode("\r\n", ['', $b['owner'], $b['name'], $b['phone'], $b['website']]);

    try {
        switch ($status) {
            case 'hired':
                $subject = 'Congratulations — ' . $position . ' at ' . $b['name'];
                $body = implode("\r\n", [
                    $hi, '',
                    "Congratulations! We'd like to offer you the " . $position . ' role.',
                    "We'll be in touch shortly with next steps. Feel free to reply to this email or call/text us at " . $b['phone'] . ' with any questions.',
                    $sig,
                ]);
                break;
            case 'rejected':
                $subject = 'Update on your application — ' . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    'Thank you for your interest in ' . $position . '.',
                    "After careful review, we've decided to move forward with other candidates at this time. We'll keep your application on file for future openings.",
                    $sig,
                ]);
                break;
            case 'reviewed':
                $subject = 'Update on your application — ' . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    "We've reviewed your application for " . $position . " and are still considering candidates — we'll follow up soon.",
                    $sig,
                ]);
                break;
            case 'new':
                $subject = "We've received your application — " . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    'Thanks for applying' . ($name ? ', ' . $name : '') . "! We've received your application for " . $position . ' and will review it shortly.',
                    $sig,
                ]);
                break;
            default:
                return;
        }

        smtp_send_mail($cfg, $email, $subject, $body);
        error_log('[email] application status email (' . $status . ') sent for #' . $application['id'] . ' → ' . $email);
    } catch (Throwable $e) {
        error_log('[email] application status email failed for #' . ($application['id'] ?? '?') . ': ' . $e->getMessage());
    }
}
