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
    return !empty($c['user']) && !empty($c['app_password']);
}

/**
 * Minimal SMTP-over-STARTTLS client for Gmail (smtp.gmail.com:587).
 * Throws RuntimeException on any protocol error; callers should catch.
 */
function smtp_send_mail(array $cfg, string $to, string $subject, string $body): void
{
    $host = 'smtp.gmail.com';
    $port = 587;
    $user = $cfg['user'];
    $pass = $cfg['app_password'];

    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
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
    $send('STARTTLS');       $expect($read(), '220');

    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($fp);
        throw new RuntimeException('SMTP TLS negotiation failed');
    }

    $send('EHLO localhost');           $expect($read(), '250');
    $send('AUTH LOGIN');               $expect($read(), '334');
    $send(base64_encode($user));       $expect($read(), '334');
    $send(base64_encode($pass));       $expect($read(), '235');
    $send('MAIL FROM:<' . $user . '>'); $expect($read(), '250');
    $send('RCPT TO:<' . $to . '>');     $expect($read(), '250');
    $send('DATA');                      $expect($read(), '354');

    $headers =
        'From: ' . business_info()['name'] . ' <' . $user . ">\r\n" .
        'To: <' . $to . ">\r\n" .
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
