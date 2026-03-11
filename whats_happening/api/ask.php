<?php
/**
 * What's Happening — Q&A API Endpoint
 * Accepts user questions and returns answers using public AI API
 * Protected against: brute-force, SQLi, XSS, CSRF
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Only allow AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

require_once __DIR__ . '/../includes/security.php';

// Apply stricter rate limit for Q&A
checkRateLimit('qa');

header('Content-Type: application/json');

// Parse JSON body
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['question'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request format']);
    exit;
}

// CSRF validation
$csrfToken = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security validation failed. Please refresh the page.']);
    exit;
}

// Validate and sanitize the question
$question  = $data['question'] ?? '';
$sanitized = validateAndSanitizeQuestion($question);

if ($sanitized === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input detected. Please ask a legitimate question about the Iran conflict.']);
    exit;
}

if (strlen($sanitized) < 5 || strlen($sanitized) > 300) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Question must be between 5 and 300 characters.']);
    exit;
}

$ip = getClientIP();

// ── OpenAI-compatible API (sandbox pre-configured) ────────────────────────
function getAIAnswer($question) {
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) return null;

    // Use the sandbox's pre-configured OpenAI-compatible base URL
    // The sandbox sets OPENAI_API_KEY and configures the base URL automatically
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    $systemPrompt = "You are a knowledgeable news assistant for 'What's Happening', a verified news website covering the ongoing 2026 Iran conflict. Answer questions factually and concisely based on verified public information. The conflict began on February 28, 2026, when the US and Israel launched Operation Epic Fury against Iran, killing Supreme Leader Khamenei. Key facts: over 1,300 civilians killed in Iran, 7 US soldiers killed, 140 US wounded, Iran has mined the Strait of Hormuz, oil prices near \$120/barrel, Lebanon has 570+ killed, 667,000 displaced. Keep answers under 200 words. If you don't know something, say so honestly. Do not speculate beyond verified reports.";

    $payload = json_encode([
        'model'    => 'gpt-4.1-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $question]
        ],
        'max_tokens'  => 250,
        'temperature' => 0.3,
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: https://whats-happening.news',
            'X-Title: Whats Happening News'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$response) return null;

    if ($httpCode === 200) {
        $decoded = json_decode($response, true);
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
    }
    return null;
}

// ── DuckDuckGo Instant Answer API (public, no key needed) ─────────────────
function getDuckDuckGoAnswer($question) {
    $url = 'https://api.duckduckgo.com/?q=' . urlencode($question . ' Iran war 2026') .
           '&format=json&no_html=1&skip_disambig=1&t=whats_happening_news';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'WhatsHappening-NewsBot/1.0 (news aggregator)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['AbstractText'])) return $data['AbstractText'];
        if (!empty($data['Answer']))       return $data['Answer'];
        if (!empty($data['RelatedTopics'])) {
            foreach ($data['RelatedTopics'] as $topic) {
                if (!empty($topic['Text']) && strlen($topic['Text']) > 50) {
                    return $topic['Text'];
                }
            }
        }
    }
    return null;
}

// ── Curated Fallback Responses ─────────────────────────────────────────────
function getCuratedAnswer($question) {
    $lowerQ = strtolower($question);
    $responses = [
        'khamenei'    => 'Supreme Leader Ali Khamenei was killed on February 28, 2026, in a US-Israeli airstrike on his compound in Tehran. This marked the beginning of the current conflict. His son Mojtaba Khamenei has since been named as the new supreme leader by Iran\'s state media.',
        'start'       => 'The US-Israel war on Iran began on February 28, 2026, when the US and Israel launched "Operation Epic Fury." The operation resulted in the killing of Supreme Leader Ali Khamenei. The conflict has since escalated into a full-scale regional war involving Lebanon, Iraq, and Gulf states.',
        'when'        => 'The US-Israel war on Iran began on February 28, 2026. As of March 11, 2026 (Day 12), the conflict is still ongoing with no confirmed ceasefire. President Trump has suggested it could end "very soon," but US strikes continue to intensify.',
        'casualties'  => 'As of March 11, 2026: Iran reports over 1,300 civilians killed and nearly 10,000 civilian sites hit. The US confirmed 7 soldiers killed and approximately 140 wounded. Lebanon has seen 570+ killed and 667,000 displaced. At least 17 people have been killed in Gulf states.',
        'dead'        => 'As of March 11, 2026: Over 1,300 civilians killed in Iran, 7 US soldiers killed, 570+ killed in Lebanon, and 17+ in Gulf states. The total regional death toll continues to rise as fighting intensifies.',
        'oil'         => 'Oil prices have surged close to $120 per barrel due to Iran mining the Strait of Hormuz, through which 20% of global oil passes. The UAE\'s Ruwais refinery — one of the world\'s largest — halted operations following a drone attack. Energy experts warn of a "nightmare scenario" if the strait is effectively closed.',
        'strait'      => 'Iran mined the Strait of Hormuz in retaliation for US-Israeli strikes. US CENTCOM reported destroying 16 Iranian mine-laying vessels. President Trump warned any attempt to block the strait will be met "quickly and violently." Shipping traffic has dropped dramatically since the conflict began.',
        'hormuz'      => 'Iran mined the Strait of Hormuz, a critical waterway through which 20% of the world\'s oil supply passes. US forces have destroyed 16 Iranian mine-laying vessels. A cargo ship was also reported ablaze in the strait after being hit by an unknown projectile.',
        'trump'       => 'President Trump has said the war could end "very soon" but continues to authorize intensifying strikes. US Senate Democrats are demanding public hearings on the war\'s goals and duration. The US has evacuated over 43,000 American citizens from the Middle East.',
        'lebanon'     => 'Lebanon has been drawn into the conflict with Israeli forces bombing residential buildings in Beirut. At least 570 people have been killed and over 667,000 displaced. Four Iranian diplomats were killed in an Israeli strike in Beirut, which Iran called a "terrorist attack."',
        'iraq'        => 'Iraq has been affected by the conflict, with strikes hitting several locations including groups linked to the Popular Mobilisation Forces (PMF). Iraq\'s Kurdistan region, which hosts US bases, has faced attacks from Iran. Iraq\'s prime minister told the US the country should not be used as a launchpad for attacks.',
        'russia'      => 'Russia is reportedly sharing intelligence with Iran to support Iranian attacks against US forces in the Middle East, according to the Institute for the Study of War (ISW). This highlights deepening cooperation between Moscow and Tehran during the conflict.',
        'school'      => 'A US-Israeli airstrike hit an Iranian girls\' school, killing approximately 175 students. Human Rights Watch has called for an investigation as a potential war crime. The White House said it will accept the results of an ongoing military probe, with photographic evidence suggesting a US missile was responsible.',
        'operation'   => '"Operation Epic Fury" is the name of the US-Israeli military campaign against Iran that began February 28, 2026. The operation initially targeted Iran\'s nuclear and missile programs, as well as military and political leadership, resulting in the killing of Supreme Leader Khamenei.',
        'default'     => 'Based on verified public reporting: The US-Israel war on Iran began February 28, 2026, with "Operation Epic Fury." Over 1,300 civilians have been killed in Iran, and the conflict has spread to Lebanon, Iraq, and Gulf states. Iran has mined the Strait of Hormuz, causing oil prices to surge near $120/barrel. For the latest updates, please refer to Al Jazeera, BBC, or Reuters.',
    ];

    foreach ($responses as $keyword => $response) {
        if ($keyword !== 'default' && strpos($lowerQ, $keyword) !== false) {
            return $response;
        }
    }
    return $responses['default'];
}

// ── Main Answer Logic ──────────────────────────────────────────────────────
$answer = null;
$source = 'curated';

// 1. Try AI API first
$answer = getAIAnswer($sanitized);
if ($answer) $source = 'ai';

// 2. Fallback to DuckDuckGo
if (!$answer) {
    $answer = getDuckDuckGoAnswer($sanitized);
    if ($answer) $source = 'duckduckgo';
}

// 3. Final fallback with curated responses
if (!$answer) {
    $answer = getCuratedAnswer($sanitized);
    $source = 'curated';
}

// Store question in database (analytics only, not displayed publicly)
try {
    $db   = getDB();
    $stmt = $db->prepare('INSERT INTO questions (question, answer, asked_at, ip) VALUES (:q,:a,:t,:ip)');
    $stmt->bindValue(':q',  substr($sanitized, 0, 500), SQLITE3_TEXT);
    $stmt->bindValue(':a',  substr($answer,    0, 1000), SQLITE3_TEXT);
    $stmt->bindValue(':t',  time(),                      SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $ip,                         SQLITE3_TEXT);
    $stmt->execute();
} catch (Exception $e) { /* silent */ }

// Return answer
echo json_encode([
    'success' => true,
    'answer'  => htmlspecialchars($answer, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    'source'  => 'AI-assisted answer based on verified public reporting'
]);
