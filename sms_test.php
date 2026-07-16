<?php
// DELETE THIS FILE after testing — do not leave on production.
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/sms.php';

if (!sms_is_configured()) {
    die('SMS not configured — check twilio block in config.php');
}

$cfg = config('twilio');
echo '<pre>';
echo 'SID:   ' . substr($cfg['sid'], 0, 8) . '...' . "\n";
echo 'FROM:  ' . $cfg['from'] . "\n";
echo 'TO:    ' . $cfg['owner_phone'] . "\n\n";

try {
    twilio_send_sms($cfg, $cfg['owner_phone'], 'Test SMS from Randy site ' . date('H:i:s'));
    echo "SUCCESS — SMS sent!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo '</pre>';
