<?php
/**
 * Free, rule-based assistant that answers from the business info — no API key
 * required. Used whenever Gemini is not configured or fails. Ported from the
 * original scriptedBot.js.
 */

function scripted_reply(?string $text): string
{
    $b = business_info();
    $t = strtolower(trim((string) $text));

    $has = function (array $words) use ($t): bool {
        foreach ($words as $w) {
            if ($w !== '' && strpos($t, $w) !== false) {
                return true;
            }
        }
        return false;
    };

    $services = implode(', ', $b['services']);

    if ($t === '') {
        return "Hi! Welcome to {$b['name']}. Ask me about our services, hours, pricing, or service area — or book a free estimate.";
    }
    if ($has(['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'kumusta'])) {
        return "Hi! Welcome to {$b['name']}. I can help with our services, hours, pricing, or booking a free estimate. What do you need?";
    }
    if ($has(['human', 'owner', 'randy', 'real person', 'representative', 'agent', 'someone', 'team member', 'support', 'talk to'])) {
        return "I can connect you with our team. Tap \"Talk to the owner / a team member\" below — you'll be asked to log in or register, then {$b['owner']} or a team member will take over the chat.";
    }
    if ($has(['price', 'cost', 'how much', 'quote', 'estimate', 'rate', 'pricing', 'budget', 'pila', 'magkano'])) {
        return "{$b['pricingNotes']} We give free written estimates — book one and we'll confirm the visit.";
    }
    if ($has(['book', 'appointment', 'schedule', 'reserve', 'set up', 'visit'])) {
        return 'Great! Use the "Book" page to request an appointment — choose a service, your preferred date & time, and your address. We\'ll confirm it shortly. Estimates are free.';
    }
    if ($has(['hour', 'open', 'close', 'what time', 'available', 'oras'])) {
        return "Our hours are {$b['hours']}. Want a specific day? Book a free estimate and we'll lock in a time.";
    }
    if ($has(['where', 'area', 'location', 'serve', 'lehigh', 'allentown', 'near me', 'asa', 'lugar'])) {
        return "We serve {$b['serviceArea']}. If you're nearby, we'd love to help — book a free estimate to get started.";
    }
    if ($has(['contact', 'phone', 'call', 'email', 'number', 'reach', 'text', 'tawag'])) {
        return "You can reach {$b['name']} at {$b['phone']} or {$b['email']} ({$b['website']}). Or book a free estimate and we'll contact you.";
    }
    if ($has(['service', 'offer', 'do you do', 'paint', 'drywall', 'exterior', 'interior', 'cabinet', 'texture', 'ceiling', 'commercial', 'wall', 'repair', 'finish'])) {
        return "We offer: {$services}. Want a free estimate for your project? Tap \"Book\" — or ask me anything else.";
    }
    if ($has(['thank', 'salamat', 'appreciate'])) {
        return "You're very welcome! Anything else — services, pricing, or booking an estimate?";
    }

    $first3 = implode(', ', array_slice($b['services'], 0, 3));
    return "Thanks for your message! I can help with our services ({$first3}…), hours, pricing, service area, or booking a free estimate. To speak with a person, tap \"Talk to the owner / a team member\".";
}

/** Pick the most recent customer message from a list and answer it. */
function scripted_reply_to_conversation(array $messages): string
{
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if (($messages[$i]['sender_type'] ?? '') === 'customer') {
            return scripted_reply($messages[$i]['body'] ?? '');
        }
    }
    return scripted_reply('');
}
