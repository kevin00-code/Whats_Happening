# What's Happening — Iran War News Website

A secure PHP news website covering the ongoing 2026 Iran conflict with verified reporting, comprehensive security, and an AI-powered Q&A section.

## Features

### News Coverage
- 7 verified news articles sourced from Al Jazeera, Brookings Institution, ISW, The Guardian, UN OHCHR, Human Rights Watch, and Atlantic Council
- Real-time breaking news ticker
- Conflict statistics sidebar with key data
- Chronological timeline of key events
- Verified source links

### Security Implemented
| Security Layer | Implementation |
|---|---|
| **Brute-Force Protection** | IP-based rate limiting (10 req/min, 10-min block) |
| **SQL Injection Prevention** | Prepared statements + pattern detection + blocking |
| **XSS Protection** | Input sanitization, `htmlspecialchars()`, CSP headers |
| **CSRF Protection** | Token-based validation on all POST requests |
| **Developer Tools Blocking** | JS size-based detection + keyboard shortcut blocking |
| **Console Protection** | All console methods overridden with no-ops |
| **Right-Click Blocking** | `contextmenu` event prevention |
| **Bot Detection** | User-agent pattern matching (sqlmap, nikto, curl, etc.) |
| **Security Headers** | X-Frame-Options, CSP, HSTS, X-XSS-Protection, etc. |
| **Path Traversal Block** | `.htaccess` rewrite rules |
| **Directory Listing** | Disabled via `Options -Indexes` |
| **Sensitive File Protection** | `.htaccess` blocks `.db`, `.log`, `.env`, etc. |
| **Session Security** | HttpOnly, SameSite=Strict, periodic regeneration |
| **Security Logging** | All events logged to SQLite + file |

### Q&A Section
- AI-powered answers using OpenAI-compatible API
- Fallback to DuckDuckGo Instant Answer API (public, no key required)
- Curated fallback responses for common questions
- Rate-limited (separate limit from page browsing)
- CSRF-protected
- SQL injection and XSS validated before processing

### Public Domain Disclaimer
- Per-article disclaimer: "What's Happening does not claim authorship..."
- Site-wide footer disclaimer
- Q&A answer disclaimer

## File Structure

```
whats_happening/
├── index.php              # Main homepage
├── .htaccess              # Apache security rules
├── README.md              # This file
├── css/
│   └── style.css          # Main stylesheet
├── js/
│   └── app.js             # Security + Q&A JavaScript
├── includes/
│   ├── security.php       # Core security module
│   └── init_db.php        # Database initialization
├── api/
│   ├── ask.php            # Q&A API endpoint
│   └── security_report.php # Client security event reporter
├── data/
│   ├── news.php           # Verified news articles data
│   └── whats_happening.db # SQLite database
├── images/
│   ├── iran_war_1.jpg     # Tehran airstrikes (Al Jazeera)
│   ├── iran_war_2.jpg     # Rescue workers in rubble (WTTW/Getty)
│   ├── iran_war_3.jpg     # Black smoke over Tehran (Al Jazeera)
│   ├── iran_war_4.jpg     # Oil depot fires (Al Jazeera)
│   ├── iran_war_5.jpg     # Rescue teams in Tehran (Al Jazeera)
│   └── iran_war_6.jpg     # Smoke over Tehran (Al Jazeera)
└── logs/
    └── security.log       # Security event log
```

## Deployment

### Requirements
- PHP 8.0+ with SQLite3, cURL, mbstring extensions
- Apache with mod_rewrite enabled (for .htaccess rules)
- HTTPS recommended for production

### Quick Start (Development)
```bash
php -S 0.0.0.0:8080 -t .
```

### Production Deployment
1. Upload all files to your web server's document root
2. Ensure `data/` and `logs/` directories are writable: `chmod 755 data logs`
3. Set `OPENAI_API_KEY` environment variable for AI Q&A
4. Enable HTTPS and uncomment the HTTPS redirect in `.htaccess`
5. Set `session.cookie_secure = 1` in `includes/security.php`
6. Run `php includes/init_db.php` once to initialize the database

### Environment Variables
- `OPENAI_API_KEY` — Required for AI-powered Q&A answers

## News Sources (Verified)
All content is sourced from:
- [Al Jazeera](https://www.aljazeera.com) — Primary live coverage
- [Brookings Institution](https://www.brookings.edu) — Expert analysis
- [ISW / Understanding War](https://understandingwar.org) — Military analysis
- [The Guardian](https://www.theguardian.com) — International reporting
- [UN OHCHR](https://www.ohchr.org) — Human rights data
- [Human Rights Watch](https://www.hrw.org) — War crimes reporting
- [Atlantic Council](https://www.atlanticcouncil.org) — Policy analysis
- [ACLED Data](https://acleddata.com) — Conflict data

## Disclaimer
What's Happening does not claim authorship, ownership, or copyright over any content displayed. All articles are sourced from publicly available, verified international news outlets and reproduced in the public interest. Original reporting credit belongs entirely to the cited sources.
