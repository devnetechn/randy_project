<?php

require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/sms.php';

$cfg = config('twilio');

echo '<pre>';

try {

    twilio_send_sms(
        $cfg,
        $cfg['owner_phone'],
        'TEST SMS from Randy Painting website ' . date('H:i:s')
    );

    echo "SUCCESS - SMS sent!\n";

} catch (Throwable $e) {

    echo "ERROR:\n";
    echo $e->getMessage() . "\n";

}

echo '</pre>';