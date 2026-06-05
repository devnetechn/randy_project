<?php
/** Google Gemini integration via cURL, with a scripted-bot fallback. */

function gemini_is_configured(): bool
{
    $g = config('gemini');
    return !empty($g['api_key']);
}

/** Map our sender types to Gemini roles (system messages are skipped). */
function gemini_history(array $messages): array
{
    $roleBySender = ['customer' => 'user', 'ai' => 'model', 'admin' => 'model'];
    $contents = [];
    foreach ($messages as $m) {
        $sender = $m['sender_type'] ?? '';
        if (!isset($roleBySender[$sender])) {
            continue;
        }
        $contents[] = [
            'role'  => $roleBySender[$sender],
            'parts' => [['text' => (string) ($m['body'] ?? '')]],
        ];
    }
    return $contents;
}

/** Call Gemini. Throws on any failure so callers can fall back. */
function gemini_generate_reply(array $messages, string $systemPrompt): string
{
    $g = config('gemini');
    if (empty($g['api_key'])) {
        throw new RuntimeException('Gemini is not configured');
    }
    $model = $g['model'] ?: 'gemini-1.5-flash';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($g['api_key']);

    $payload = [
        'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
        'contents'           => gemini_history($messages),
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false || $err) {
        throw new RuntimeException('Gemini request failed: ' . $err);
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Gemini returned HTTP {$status}");
    }
    $data = json_decode($body, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!is_string($text) || $text === '') {
        throw new RuntimeException('Gemini returned no text');
    }
    return $text;
}

/**
 * Produce an assistant reply for a message history. Uses Gemini when
 * configured, otherwise (or on error) the free scripted bot.
 */
function chat_ai_reply(array $messages): string
{
    if (gemini_is_configured()) {
        try {
            return gemini_generate_reply($messages, build_system_prompt());
        } catch (Throwable $e) {
            // fall through to scripted bot
        }
    }
    return scripted_reply_to_conversation($messages);
}
