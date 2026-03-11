<?php
/**
 * What's Happening - Security Module
 * Comprehensive security: brute-force, SQLi, XSS, CSRF, rate limiting, dev-tools blocking
 */

// ─── Session Security ───────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // set to 1 in production with HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// ─── Database Connection ─────────────────────────────────────────────────────
function getDB() {
    static $db = null;
    if ($db === null) {
        $dbPath = __DIR__ . '/../data/whats_happening.db';
        $db = new SQLite3($dbPath);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
    }
    return $db;
}

// ─── Rate Limiting / Brute-Force Protection ──────────────────────────────────
define('MAX_ATTEMPTS', 10);
define('BLOCK_DURATION', 600);   // 10 minutes
define('RATE_WINDOW', 60);       // 1 minute window

function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function checkRateLimit($action = 'general') {
    $ip  = getClientIP();
    $key = $ip . '_' . $action;
    $db  = getDB();
    $now = time();

    $stmt = $db->prepare('SELECT attempts, last_attempt, blocked_until FROM rate_limit WHERE ip = :ip');
    $stmt->bindValue(':ip', $key, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        // Still blocked?
        if ($row['blocked_until'] > $now) {
            $remaining = $row['blocked_until'] - $now;
            logSecurityEvent($ip, "BLOCKED_REQUEST: $action (remaining: {$remaining}s)");
            http_response_code(429);
            die(renderBlockPage($remaining));
        }
        // Reset window if expired
        if ($now - $row['last_attempt'] > RATE_WINDOW) {
            $db->exec("UPDATE rate_limit SET attempts=1, last_attempt=$now, blocked_until=0 WHERE ip='" . $db->escapeString($key) . "'");
            // use prepared statement below
            $upd = $db->prepare('UPDATE rate_limit SET attempts=1, last_attempt=:now, blocked_until=0 WHERE ip=:ip');
            $upd->bindValue(':now', $now, SQLITE3_INTEGER);
            $upd->bindValue(':ip', $key, SQLITE3_TEXT);
            $upd->execute();
        } else {
            $attempts = $row['attempts'] + 1;
            $blocked  = ($attempts >= MAX_ATTEMPTS) ? ($now + BLOCK_DURATION) : 0;
            if ($blocked > 0) logSecurityEvent($ip, "RATE_LIMIT_BLOCKED: $action after $attempts attempts");
            $upd = $db->prepare('UPDATE rate_limit SET attempts=:a, last_attempt=:now, blocked_until=:b WHERE ip=:ip');
            $upd->bindValue(':a',   $attempts, SQLITE3_INTEGER);
            $upd->bindValue(':now', $now,      SQLITE3_INTEGER);
            $upd->bindValue(':b',   $blocked,  SQLITE3_INTEGER);
            $upd->bindValue(':ip',  $key,      SQLITE3_TEXT);
            $upd->execute();
            if ($blocked > 0) {
                http_response_code(429);
                die(renderBlockPage(BLOCK_DURATION));
            }
        }
    } else {
        $ins = $db->prepare('INSERT INTO rate_limit (ip, attempts, last_attempt, blocked_until) VALUES (:ip,1,:now,0)');
        $ins->bindValue(':ip',  $key, SQLITE3_TEXT);
        $ins->bindValue(':now', $now, SQLITE3_INTEGER);
        $ins->execute();
    }
}

function renderBlockPage($seconds) {
    $min = ceil($seconds / 60);
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access Restricted - What\'s Happening</title>
    <style>body{font-family:Arial,sans-serif;background:#0a0a0a;color:#e0e0e0;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .box{background:#1a1a2e;border:1px solid #e63946;padding:40px;border-radius:8px;text-align:center;max-width:400px}
    h1{color:#e63946}p{color:#aaa}</style></head>
    <body><div class="box"><h1>&#x26A0; Access Restricted</h1>
    <p>Too many requests detected from your IP address.</p>
    <p>Please wait approximately <strong>' . $min . ' minute(s)</strong> before trying again.</p>
    <p style="font-size:12px;color:#666">If you believe this is an error, please contact the site administrator.</p>
    </div></body></html>';
}

// ─── Security Event Logger ───────────────────────────────────────────────────
function logSecurityEvent($ip, $event) {
    try {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO security_log (ip, event, timestamp) VALUES (:ip,:ev,:ts)');
        $stmt->bindValue(':ip', $ip,    SQLITE3_TEXT);
        $stmt->bindValue(':ev', $event, SQLITE3_TEXT);
        $stmt->bindValue(':ts', time(), SQLITE3_INTEGER);
        $stmt->execute();
    } catch (Exception $e) { /* silent fail */ }
    // Also write to file log
    $logFile = __DIR__ . '/../logs/security.log';
    $line    = date('Y-m-d H:i:s') . " | $ip | $event\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ─── Input Sanitization ──────────────────────────────────────────────────────
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    $input = trim($input);
    switch ($type) {
        case 'int':
            return (int) $input;
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        default:
            // Strip dangerous patterns
            $input = strip_tags($input);
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return $input;
    }
}

// ─── SQL Injection Detection ─────────────────────────────────────────────────
function detectSQLInjection($input) {
    $patterns = [
        '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
        '/(\bOR\b|\bAND\b)\s+[\d\'\"]+\s*=\s*[\d\'\"]+/i',
        '/[\'\"]\s*(OR|AND)\s*[\'\"]/i',
        '/;\s*(DROP|DELETE|INSERT|UPDATE|CREATE|ALTER)/i',
        '/--\s*$/',
        '/\/\*.*?\*\//',
        '/\bEXEC\b|\bEXECUTE\b|\bxp_/i',
        '/SLEEP\s*\(|BENCHMARK\s*\(/i',
        '/LOAD_FILE\s*\(|INTO\s+OUTFILE/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

function validateAndSanitizeQuestion($input) {
    $ip = getClientIP();
    if (detectSQLInjection($input)) {
        logSecurityEvent($ip, 'SQL_INJECTION_ATTEMPT: ' . substr($input, 0, 100));
        return false;
    }
    // XSS patterns
    $xssPatterns = [
        '/<script/i', '/javascript:/i', '/on\w+\s*=/i',
        '/data:text\/html/i', '/<iframe/i', '/<object/i',
        '/<embed/i', '/vbscript:/i', '/<img[^>]+onerror/i',
    ];
    foreach ($xssPatterns as $p) {
        if (preg_match($p, $input)) {
            logSecurityEvent($ip, 'XSS_ATTEMPT: ' . substr($input, 0, 100));
            return false;
        }
    }
    return sanitizeInput($input);
}

// ─── CSRF Token ──────────────────────────────────────────────────────────────
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $ip = getClientIP();
        logSecurityEvent($ip, 'CSRF_VALIDATION_FAILED');
        return false;
    }
    return true;
}

// ─── Security Headers ────────────────────────────────────────────────────────
function sendSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self' https://api.duckduckgo.com https://generativelanguage.googleapis.com;");
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    // Remove server fingerprinting
    header_remove('X-Powered-By');
    header_remove('Server');
}

// ─── Bot / Automated Request Detection ───────────────────────────────────────
function detectBot() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $botPatterns = [
        '/sqlmap/i', '/nikto/i', '/nmap/i', '/masscan/i',
        '/zgrab/i', '/python-requests/i', '/curl\//i',
        '/wget\//i', '/libwww/i', '/scrapy/i', '/burpsuite/i',
        '/havij/i', '/acunetix/i', '/nessus/i',
    ];
    foreach ($botPatterns as $p) {
        if (preg_match($p, $ua)) {
            $ip = getClientIP();
            logSecurityEvent($ip, 'BOT_DETECTED: ' . substr($ua, 0, 100));
            http_response_code(403);
            die('403 Forbidden');
        }
    }
}

// ─── Initialize Security on Every Request ────────────────────────────────────
sendSecurityHeaders();
detectBot();
checkRateLimit('page');
