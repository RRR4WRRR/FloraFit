<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function loadEnvFile(string $path): array
{
    $values = [];
    if (!is_file($path)) {
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $values[$name] = $value;
    }

    return $values;
}

function containsAnyText(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        $keyword = strtolower(trim((string)$keyword));
        if ($keyword === '') {
            continue;
        }

        if (preg_match('/^[a-z0-9-]+$/i', $keyword)) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                return true;
            }
            continue;
        }

        if (str_contains($text, $keyword)) {
            return true;
        }
    }
    return false;
}

function pickReplyVariant(array $responses): string
{
    if ($responses === []) {
        return '';
    }

    try {
        return $responses[random_int(0, count($responses) - 1)];
    } catch (Throwable $e) {
        return $responses[array_rand($responses)];
    }
}

function floraFitFallbackReply(string $message): string
{
    $text = strtolower(trim((string)preg_replace('/\s+/', ' ', $message)));

    if ($text === '' || preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/i', $text)) {
        return pickReplyVariant([
            'Hi! 🌸 I can help with delivery, bouquet customization, payment, order tracking, vouchers, feedback, location, and direct contact details. Just tell me which part you need help with.',
            'Hello and welcome to FloraFit! I’m here for questions about delivery schedules, GCash or COD payment, order updates, vouchers, feedback, custom bouquet design, and shop details.',
            'Hi there! 🌷 Ask me about delivery options, customizing a bouquet, payment steps, order status, vouchers, feedback, or how to contact and visit FloraFit.'
        ]);
    }

    if (containsAnyText($text, ['what can you do', 'what can i ask', 'help me', 'can you help', 'assist me', 'support'])) {
        return pickReplyVariant([
            'I can guide you through delivery concerns, bouquet customization, payment methods, tracking your order, voucher questions, feedback steps, and FloraFit’s location and contact details.',
            'You can ask me about Cash on Delivery, GCash payment confirmation, same-day delivery availability, order statuses, vouchers, feedback after delivery, and how to reach FloraFit directly.',
            'I’m here for the common customer questions: delivery, customization, pricing, payment, tracking, vouchers, feedback, location, and contact support.'
        ]);
    }

    if (containsAnyText($text, ['payment', 'pay', 'gcash', 'cash on delivery', 'cod', 'qr', 'method', 'receipt', 'proof of payment'])) {
        return pickReplyVariant([
            'FloraFit currently supports Cash on Delivery and GCash. Choose your preferred method at checkout, and if you select GCash, wait for order confirmation first, then open your profile, scan the shop QR code, and submit your payment confirmation.',
            'For payment, you can use COD or GCash. COD means you pay when the bouquet arrives, while GCash is completed after the order is confirmed and you send the payment confirmation from your profile page.',
            'Payment is handled during checkout. If you choose GCash, the usual flow is: place the order, wait for confirmation, scan the FloraFit QR code, then tap the payment confirmation button so the team can review it.'
        ]);
    }

    if (containsAnyText($text, ['track', 'tracking', 'order status', 'status', 'pending', 'accepted', 'preparing', 'delivering', 'delivered', 'where is my order', 'follow up', 'follow-up'])) {
        return pickReplyVariant([
            'You can track your bouquet from your FloraFit profile. Orders usually move through Pending, Accepted, Preparing, Delivering, and Delivered so you can see which stage your order is currently in.',
            'For tracking, open your FloraFit profile and check your orders list. The status there will tell you whether your bouquet is still waiting for review, being prepared, already out for delivery, or fully delivered.',
            'The easiest way to monitor your order is through the profile page. If the status already says Delivering, it usually means the bouquet is on the way; if it says Pending or Accepted, the team is still processing it.'
        ]);
    }

    if (containsAnyText($text, ['cancel', 'cancellation', 'refund', 'change order', 'edit order', 'wrong address'])) {
        return pickReplyVariant([
            'If you need to cancel or change an order, contact FloraFit as soon as possible. Requests are easier to accommodate before the bouquet is already being prepared or marked for delivery.',
            'Order changes like address corrections, cancellation requests, or special adjustments should be reported right away. The earlier you contact FloraFit, the better the chance the team can still update it.',
            'For cancellations or edits, it is best to reach FloraFit immediately through the contact details on the site. Once an order is already in the preparing or delivering stage, changes may be limited.'
        ]);
    }

    if (containsAnyText($text, ['feedback', 'review', 'rating', 'rate'])) {
        return pickReplyVariant([
            'Once your order is marked Delivered, open your FloraFit profile and look for the Give Feedback option beside that order. You can leave a star rating and a short message about the bouquet and service.',
            'Feedback becomes available after delivery is completed. From your profile page, open the delivered order and submit your rating and comments so FloraFit can review your experience.',
            'If you want to leave a review, wait until the order status is Delivered, then go to your profile and use the feedback button for that order. You can usually share both a score and a short written note.'
        ]);
    }

    if (containsAnyText($text, ['delivery', 'ship', 'shipping', 'arrive', 'receive', 'same-day', 'same day'])) {
        return pickReplyVariant([
            'FloraFit offers delivery for bouquet orders, and same-day delivery may be available for selected areas when orders are placed early enough. Your final delivery details, address, and schedule are confirmed during checkout.',
            'For delivery concerns, make sure your address, preferred date, and any special instructions are complete at checkout. Same-day delivery can be available in some areas, but it still depends on timing and confirmation.',
            'Delivery information is shown during checkout, including the schedule you pick and your destination details. If you are hoping for same-day service, it is best to order early and double-check your contact information.'
        ]);
    }

    if (containsAnyText($text, ['3d', 'preview', 'rotate', 'view model', 'model', 'visualize'])) {
        return pickReplyVariant([
            'FloraFit’s 3D feature lets you preview your bouquet visually before placing the order, so you can get a better idea of how the arrangement may look from the design stage.',
            'If you are using the 3D customizer, you can explore the bouquet preview while choosing flowers and styling details. It is meant to help you visualize the arrangement before checkout.',
            'The 3D experience is there to give you a more interactive bouquet preview instead of relying only on a flat image. It helps you see the design more clearly before you add it to your cart.'
        ]);
    }

    if (containsAnyText($text, ['custom', 'bouquet', 'design', 'arrangement', 'wrap', 'filler', 'greenery'])) {
        return pickReplyVariant([
            'You can build a personalized bouquet in FloraFit’s 3D customizer by choosing the flowers, fillers, greenery, and wrap style you want. Once you like the final arrangement, add it to your cart and continue to checkout.',
            'The custom bouquet flow lets you mix and match flowers and styling options so you can preview your arrangement before ordering. It is a good option if you want something more personal than the ready-made shop items.',
            'If you want a more customized gift, head to the 3D customizer and select your preferred flowers, accents, and presentation. FloraFit will show the design before you add it to your cart.'
        ]);
    }

    if (containsAnyText($text, ['shop', 'product', 'ready made', 'premade', 'pre-made', 'available bouquet'])) {
        return pickReplyVariant([
            'If you prefer a faster order, you can browse the Shop page for ready-made bouquet options. If you want something more personal, you can switch to the customizer and build your own design.',
            'FloraFit has both ready-made bouquets and customizable arrangements. The Shop page is best for browsing existing items, while the customizer is ideal for a more personalized bouquet.',
            'You can explore bouquet choices in two ways: ready-made products on the Shop page or a custom arrangement through the 3D design feature.'
        ]);
    }

    if (containsAnyText($text, ['stock', 'available', 'availability', 'have roses', 'have tulips', 'have flowers'])) {
        return pickReplyVariant([
            'Flower availability can change based on current stock. The best way to check is to browse the Shop or customization page, and for a very specific flower request, contact FloraFit directly to confirm availability.',
            'Some flowers or colors may depend on what is currently in stock. If you are looking for a specific bloom, the shop listings and customizer can help, and the FloraFit team can confirm special requests.',
            'Availability may vary from day to day, especially for certain flowers or colors. You can review current options online, but for an exact item request, contacting FloraFit is the safest choice.'
        ]);
    }

    if (containsAnyText($text, ['price', 'cost', 'budget', 'how much', 'expensive', 'pricing'])) {
        return pickReplyVariant([
            'Bouquet pricing depends on the flowers, size, wrap, and any add-ons you choose. FloraFit shows the total clearly before you finalize the order at checkout.',
            'There is no single fixed price for all bouquets because the final amount changes based on your design or chosen shop item. You can review the full total in your cart and again before placing the order.',
            'If you are working with a budget, try checking both the Shop page and the customizer. The final price updates based on the flowers and extras you pick before checkout.'
        ]);
    }

    if (containsAnyText($text, ['voucher', 'discount', 'promo', 'coupon', 'sale'])) {
        return pickReplyVariant([
            'If your account has an active voucher or promo, you can usually apply it during checkout. Voucher availability depends on current promotions, account eligibility, and whether the voucher is still active.',
            'FloraFit vouchers may appear in your account when a promo is available or one has been issued to you. You can check your voucher section and apply eligible discounts during checkout.',
            'For voucher questions, it helps to check whether the code is active, not expired, and valid for your order type. If a promo is available for your account, you should be able to use it at checkout.'
        ]);
    }

    if (containsAnyText($text, ['login', 'log in', 'sign up', 'signup', 'register', 'profile', 'account', 'password'])) {
        return pickReplyVariant([
            'You can create an account from the Sign Up page and log in from the Login page. Once you are signed in, your profile is where you manage orders, payments, vouchers, and feedback.',
            'Your FloraFit profile is the main place for checking order history, payment confirmation, feedback, and account details. If you are new, just register first from the Sign Up page.',
            'If you need account help, start from the Login or Sign Up page, then use your profile after signing in to manage your orders and other customer actions.'
        ]);
    }

    if (containsAnyText($text, ['where', 'location', 'address', 'visit', 'branch', 'store'])) {
        return pickReplyVariant([
            'You can visit FloraFit at HUB l Make Lab, Escolta St., Binondo, Manila. If you are planning to visit, it is also a good idea to message first for any specific bouquet concern.',
            'FloraFit is located at HUB l Make Lab, Escolta St., Binondo, Manila. That is the shop address shown on the site for visits and location-related inquiries.',
            'For location questions, FloraFit’s listed address is HUB l Make Lab, Escolta St., Binondo, Manila. You can also use the Contact page if you want to ask first before heading over.'
        ]);
    }

    if (containsAnyText($text, ['hour', 'open', 'schedule', 'time', 'closing'])) {
        return pickReplyVariant([
            'You can message FloraFit online anytime through the website. If you need a specific operating-hour confirmation, the best next step is to contact the shop directly using the contact details provided.',
            'FloraFit support through the site is always available for basic guidance, but if you need exact opening or pickup timing, it is safest to confirm through the Contact page.',
            'For schedule questions, the site can guide you anytime, while detailed timing concerns are best confirmed directly with FloraFit.'
        ]);
    }

    if (containsAnyText($text, ['contact', 'email', 'call', 'phone', 'telephone', 'reach you'])) {
        return pickReplyVariant([
            'You can reach FloraFit through the Contact page, where the listed details include the email `projecthappinessmanila@gmail.com`, the shop phone number, and the Binondo, Manila location.',
            'If you want to contact FloraFit directly, check the Contact page for the email address, phone details, and shop location. That is the best place for urgent follow-ups and special concerns.',
            'For direct support, FloraFit’s Contact page lists the available email, phone, and address details. It is the right place to use if you need help beyond the quick AI responses.'
        ]);
    }

    return pickReplyVariant([
        'I can help with delivery, customization, payment, tracking, vouchers, feedback, location, and contact details. Try asking something like “How do I track my order?” or “How does GCash payment work?”',
        'Ask me about FloraFit delivery, bouquet design, prices, payment options, order status, vouchers, feedback, or the shop location and contact details.',
        'If you want a quicker answer, try one of these topics: delivery, customize, payment, track order, vouchers, feedback, location, or contact support.'
    ]);
}

function buildGeminiSystemInstruction(string $referenceReply = ''): string
{
    $instruction = "You are FloraFit AI, a warm, polished, practical customer-support assistant for a flower shop. Prefer natural AI-style answers over template-like wording. Reply in around 2 to 5 sentences when useful, sound conversational and helpful, and focus only on FloraFit topics such as delivery, payment, bouquet customization, 3D preview, order tracking, vouchers, feedback, pricing, shop items, and contact or location questions. Do not invent policies or stock facts. If something specific is unknown, say so briefly and suggest contacting FloraFit directly. Known details: FloraFit is at HUB l Make Lab, Escolta St., Binondo, Manila; the shop offers bouquet customization and a Shop page for ready-made items; at checkout, customers can choose Cash on Delivery or GCash; for GCash, the user pays after order confirmation by scanning the shop QR code from the profile page and then submitting payment confirmation; order statuses can include Pending, Accepted, Preparing, Delivering, and Delivered; after delivery, the user can leave feedback from the profile page; same-day delivery may be available for selected areas when orders are placed early.";

    if ($referenceReply !== '') {
        $instruction .= " Use this reference guidance when relevant, but rewrite it naturally instead of copying it word for word: " . $referenceReply;
    }

    return $instruction;
}

function sendGeminiGenerateRequest(string $apiKey, string $model, string $message, string $systemInstruction): array
{
    $payload = [
        'systemInstruction' => [
            'parts' => [
                ['text' => $systemInstruction]
            ]
        ],
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $message]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.95,
            'topP' => 0.95,
            'maxOutputTokens' => 360
        ]
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        return [
            'reply' => null,
            'httpCode' => 0,
            'error' => 'Failed to encode Gemini request payload.',
        ];
    }

    $rawResponse = null;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
        ]);
        $rawResponse = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return [
                'reply' => null,
                'httpCode' => $httpCode,
                'error' => 'Gemini cURL error: ' . $curlError,
            ];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $jsonPayload,
                'timeout' => 25,
                'ignore_errors' => true,
            ]
        ]);
        $rawResponse = @file_get_contents($url, false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                    $httpCode = (int) $matches[1];
                    break;
                }
            }
        }

        if ($rawResponse === false) {
            return [
                'reply' => null,
                'httpCode' => $httpCode,
                'error' => 'Gemini HTTP request failed.',
            ];
        }
    }

    $decoded = json_decode((string) $rawResponse, true);

    if ($httpCode >= 400) {
        return [
            'reply' => null,
            'httpCode' => $httpCode,
            'error' => trim((string) ($decoded['error']['message'] ?? ('Gemini request failed with HTTP ' . $httpCode))),
        ];
    }

    $parts = $decoded['candidates'][0]['content']['parts'] ?? [];
    $segments = [];

    foreach ($parts as $part) {
        if (!empty($part['text'])) {
            $segments[] = trim((string) $part['text']);
        }
    }

    $reply = trim(implode("\n", $segments));

    return [
        'reply' => $reply !== '' ? $reply : null,
        'httpCode' => $httpCode,
        'error' => $reply !== '' ? null : 'Gemini returned an empty response.',
    ];
}

function callGeminiApi(string $apiKey, string $model, string $message): array
{
    $referenceReply = floraFitFallbackReply($message);
    $systemInstruction = buildGeminiSystemInstruction($referenceReply);
    $modelsToTry = array_values(array_unique(array_filter([
        trim($model),
        'gemini-flash-latest',
        'gemini-2.0-flash-lite'
    ], static fn($value) => trim((string) $value) !== '')));

    $lastError = null;

    foreach ($modelsToTry as $candidateModel) {
        $result = sendGeminiGenerateRequest($apiKey, $candidateModel, $message, $systemInstruction);

        if (!empty($result['reply'])) {
            return [
                'reply' => $result['reply'],
                'model' => $candidateModel,
                'error' => null,
                'httpCode' => $result['httpCode'] ?? 200,
            ];
        }

        $lastError = trim((string) ($result['error'] ?? ('Gemini request failed for ' . $candidateModel)));
        error_log('[FloraFit] Gemini request failed for ' . $candidateModel . ': ' . $lastError);
    }

    return [
        'reply' => null,
        'model' => null,
        'error' => $lastError ?: 'Gemini is currently unavailable.',
        'httpCode' => 0,
    ];
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'POST requests only.'
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$message = trim((string) ($payload['message'] ?? ''));

if ($message === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a message first.'
    ]);
    exit;
}

if (strlen($message) > 500) {
    echo json_encode([
        'success' => false,
        'message' => 'Please keep your message under 500 characters.'
    ]);
    exit;
}

$config = loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
$apiKey = trim((string) ($config['GEMINI_API_KEY'] ?? ''));
$model = trim((string) ($config['GEMINI_MODEL'] ?? 'gemini-flash-latest'));

if ($apiKey === '' || str_contains($apiKey, 'PASTE_YOUR_NEW_GEMINI_API_KEY_HERE')) {
    echo json_encode([
        'success' => true,
        'configured' => false,
        'mode' => 'fallback',
        'reply' => floraFitFallbackReply($message)
    ]);
    exit;
}

$geminiResult = callGeminiApi($apiKey, $model, $message);

if (($geminiResult['reply'] ?? '') === '') {
    echo json_encode([
        'success' => true,
        'configured' => true,
        'mode' => 'fallback',
        'geminiError' => $geminiResult['error'] ?? null,
        'reply' => floraFitFallbackReply($message)
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'configured' => true,
    'mode' => 'gemini',
    'model' => $geminiResult['model'] ?? $model,
    'reply' => $geminiResult['reply']
]);
?>

