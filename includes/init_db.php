<?php
/**
 * What's Happening — Database Initialization
 * Run once to set up all tables
 */

$dbPath = __DIR__ . '/../data/whats_happening.db';
$db = new SQLite3($dbPath);

$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA foreign_keys=ON');

// Rate limiting table
$db->exec('CREATE TABLE IF NOT EXISTS rate_limit (
    ip TEXT NOT NULL,
    attempts INTEGER DEFAULT 0,
    last_attempt INTEGER NOT NULL,
    blocked_until INTEGER DEFAULT 0,
    PRIMARY KEY(ip)
)');

// Questions/Q&A log table
$db->exec('CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    answer TEXT,
    asked_at INTEGER NOT NULL,
    ip TEXT NOT NULL
)');

// Security events log table
$db->exec('CREATE TABLE IF NOT EXISTS security_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT NOT NULL,
    event TEXT NOT NULL,
    timestamp INTEGER NOT NULL
)');

// Create indexes for performance
$db->exec('CREATE INDEX IF NOT EXISTS idx_rate_limit_ip ON rate_limit(ip)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_security_log_ip ON security_log(ip)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_security_log_ts ON security_log(timestamp)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_questions_ip ON questions(ip)');

echo "Database initialized successfully.\n";
echo "Tables created: rate_limit, questions, security_log\n";
