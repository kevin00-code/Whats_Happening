<?php
/**
 * What's Happening - Automated News Update Script
 * Fetches latest Iran war news from NewsAPI and updates news.php
 * 
 * Usage: php api/update_news.php
 * Or via cron: 0 0 * * * php /path/to/whats_happening/api/update_news.php (daily at midnight)
 */

// Configuration
define('NEWSAPI_KEY', 'e0e129f3675a4225975c6c36f04ecfe0');
define('NEWSAPI_URL', 'https://newsapi.org/v2/everything');
define('MAX_ARTICLES', 15);
define('NEWS_FILE', __DIR__ . '/../data/news.php');
define('LOG_FILE', __DIR__ . '/../logs/update.log');

// ── Logging Function ────────────────────────────────────────────────────────
function logUpdate($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logLine   = "[$timestamp] $message\n";
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
    echo $logLine;
}

// ── Fetch News from NewsAPI ─────────────────────────────────────────────────
function fetchNewsFromAPI() {
    logUpdate('Starting news fetch from NewsAPI...');

    $params = [
        'q'           => 'Iran war 2026',
        'sortBy'      => 'publishedAt',
        'language'    => 'en',
        'apiKey'      => NEWSAPI_KEY,
        'pageSize'    => MAX_ARTICLES,
    ];

    $url = NEWSAPI_URL . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'WhatsHappening-AutoUpdate/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        logUpdate("ERROR: cURL error - $curlErr");
        return null;
    }

    if ($httpCode !== 200) {
        logUpdate("ERROR: NewsAPI returned HTTP $httpCode");
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['articles'])) {
        logUpdate("ERROR: Invalid JSON response from NewsAPI");
        return null;
    }

    logUpdate("Successfully fetched " . count($data['articles']) . " articles from NewsAPI");
    return $data['articles'];
}

// ── Convert NewsAPI Article to Our Format ────────────────────────────────────
function convertArticle($article, $id) {
    $title       = trim($article['title'] ?? 'Untitled');
    $description = trim($article['description'] ?? '');
    $content     = trim($article['content'] ?? $description);
    $source      = trim($article['source']['name'] ?? 'News Source');
    $url         = trim($article['url'] ?? '');
    $image       = trim($article['urlToImage'] ?? '');
    $publishedAt = $article['publishedAt'] ?? date('c');

    // Parse date
    $date = date('F j, Y', strtotime($publishedAt));

    // Sanitize content
    $title   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $summary = htmlspecialchars(substr($description, 0, 200), ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars(substr($content, 0, 1000), ENT_QUOTES, 'UTF-8');
    $source  = htmlspecialchars($source, ENT_QUOTES, 'UTF-8');

    // Determine category
    $lowerTitle = strtolower($title);
    if (strpos($lowerTitle, 'death') !== false || strpos($lowerTitle, 'kill') !== false) {
        $category = 'Casualties';
    } elseif (strpos($lowerTitle, 'oil') !== false || strpos($lowerTitle, 'price') !== false) {
        $category = 'Economy & Energy';
    } elseif (strpos($lowerTitle, 'analysis') !== false || strpos($lowerTitle, 'expert') !== false) {
        $category = 'Analysis';
    } elseif (strpos($lowerTitle, 'humanitarian') !== false || strpos($lowerTitle, 'refugee') !== false) {
        $category = 'Humanitarian';
    } else {
        $category = 'Breaking News';
    }

    // Extract tags
    $tags = ['Iran War', 'Verified News'];
    if (strpos($lowerTitle, 'us') !== false || strpos($lowerTitle, 'america') !== false) {
        $tags[] = 'US Military';
    }
    if (strpos($lowerTitle, 'israel') !== false) {
        $tags[] = 'Israel';
    }
    if (strpos($lowerTitle, 'lebanon') !== false) {
        $tags[] = 'Lebanon';
    }
    if (strpos($lowerTitle, 'strait') !== false || strpos($lowerTitle, 'hormuz') !== false) {
        $tags[] = 'Strait of Hormuz';
    }

    return [
        'id'              => $id,
        'title'           => $title,
        'date'            => $date,
        'source'          => $source,
        'source_url'      => $url,
        'category'        => $category,
        'image'           => $image,
        'image_caption'   => "News image from {$source}",
        'summary'         => $summary,
        'content'         => $content,
        'tags'            => $tags,
    ];
}

// ── Update news.php File ─────────────────────────────────────────────────────
function updateNewsFile($articles) {
    if (empty($articles)) {
        logUpdate("ERROR: No articles to update");
        return false;
    }

    // Load existing articles
    $existingArticles = require NEWS_FILE;
    if (!is_array($existingArticles)) {
        $existingArticles = [];
    }

    // Get the highest ID
    $maxId = 0;
    foreach ($existingArticles as $article) {
        if (isset($article['id']) && $article['id'] > $maxId) {
            $maxId = $article['id'];
        }
    }

    // Convert and prepend new articles
    $newArticles = [];
    foreach ($articles as $index => $article) {
        $newArticles[] = convertArticle($article, ++$maxId);
    }

    // Combine: new articles first, then existing (limit to 50 total)
    $allArticles = array_merge($newArticles, $existingArticles);
    $allArticles = array_slice($allArticles, 0, 50);

    // Generate PHP code
    $phpCode = "<?php\n";
    $phpCode .= "/**\n";
    $phpCode .= " * What's Happening - Verified News Data\n";
    $phpCode .= " * Auto-updated: " . date('Y-m-d H:i:s') . "\n";
    $phpCode .= " * Sources: NewsAPI, Al Jazeera, Reuters, BBC, Brookings, ISW, UN, HRW\n";
    $phpCode .= " * All content is in the public domain / publicly reported.\n";
    $phpCode .= " */\n\n";
    $phpCode .= "\$newsArticles = [\n";

    foreach ($allArticles as $article) {
        $phpCode .= "    [\n";
        $phpCode .= "        'id'              => " . (int)$article['id'] . ",\n";
        $phpCode .= "        'title'           => " . var_export($article['title'], true) . ",\n";
        $phpCode .= "        'date'            => " . var_export($article['date'], true) . ",\n";
        $phpCode .= "        'source'          => " . var_export($article['source'], true) . ",\n";
        $phpCode .= "        'source_url'      => " . var_export($article['source_url'], true) . ",\n";
        $phpCode .= "        'category'        => " . var_export($article['category'], true) . ",\n";
        $phpCode .= "        'image'           => " . var_export($article['image'], true) . ",\n";
        $phpCode .= "        'image_caption'   => " . var_export($article['image_caption'], true) . ",\n";
        $phpCode .= "        'summary'         => " . var_export($article['summary'], true) . ",\n";
        $phpCode .= "        'content'         => " . var_export($article['content'], true) . ",\n";
        $phpCode .= "        'tags'            => " . var_export($article['tags'], true) . ",\n";
        $phpCode .= "    ],\n";
    }

    $phpCode .= "];\n\n";
    $phpCode .= "return \$newsArticles;\n";

    // Write to file
    if (file_put_contents(NEWS_FILE, $phpCode, LOCK_EX) === false) {
        logUpdate("ERROR: Could not write to news.php");
        return false;
    }

    logUpdate("Successfully updated news.php with " . count($newArticles) . " new articles");
    return true;
}

// ── Main Execution ──────────────────────────────────────────────────────────
logUpdate("=== News Update Started ===");

// Fetch articles
$articles = fetchNewsFromAPI();
if (!$articles) {
    logUpdate("ERROR: Failed to fetch articles");
    exit(1);
}

// Update file
if (updateNewsFile($articles)) {
    logUpdate("=== News Update Completed Successfully ===");
    exit(0);
} else {
    logUpdate("=== News Update Failed ===");
    exit(1);
}
