<?php
/** Tiny key/value settings store backed by the `settings` table. */

/** Read a setting, returning $default when it isn't set. */
function setting_get(string $key, ?string $default = null): ?string
{
    $st = db()->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $st->execute([$key]);
    $row = $st->fetch();
    return $row ? $row['svalue'] : $default;
}

/** Create or update a setting. */
function setting_set(string $key, ?string $value): void
{
    db()->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
    )->execute([$key, $value]);
}
