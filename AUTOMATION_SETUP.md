# Automated News Updates — Setup Guide

Your "What's Happening" website now automatically fetches and updates news from NewsAPI. Here's how to set it up on your hosting provider.

---

## How It Works

1. **NewsAPI Script** (`api/update_news.php`) fetches the latest "Iran war 2026" articles
2. **Automatic Processing** converts them to your website's format
3. **Database Update** writes new articles to `data/news.php`
4. **Website Refresh** displays the new articles instantly

---

## Setup Instructions

### Step 1: Verify Your NewsAPI Key
Your API key is: `e0e129f3675a4225975c6c36f04ecfe0`

Test it by visiting: https://newsapi.org/v2/everything?q=Iran%20war&apiKey=e0e129f3675a4225975c6c36f04ecfe0

### Step 2: Upload Files to Your Host
Upload the entire `whats_happening/` folder to your web server, including:
- `api/update_news.php` (the automation script)
- `data/news.php` (will be auto-updated)
- `logs/update.log` (will track updates)

### Step 3: Set Up Automated Scheduling

#### Option A: Cron Job (Recommended - Most Hosting Providers)

1. Log into your hosting provider's **cPanel** or control panel
2. Find **Cron Jobs** section
3. Add a new cron job with these settings:

**Command:**
```
php /home/username/public_html/whats_happening/api/update_news.php
```
*(Replace `/home/username/public_html/` with your actual path)*

**Frequency:** Choose one:
- **Every 2 hours:** `0 */2 * * *`
- **Every 4 hours:** `0 */4 * * *`
- **Daily (midnight):** `0 0 * * *`
- **Twice daily (6am & 6pm):** `0 6,18 * * *`

**Example cron entry:**
```
0 */4 * * * php /home/username/public_html/whats_happening/api/update_news.php
```

#### Option B: Webhook (If Cron Not Available)

1. Create a simple trigger file at `api/trigger_update.php`:

```php
<?php
// Verify the request is from a trusted source
$token = $_GET['token'] ?? '';
if ($token !== 'your_secret_token_here') {
    http_response_code(403);
    exit('Forbidden');
}

// Run the update
require_once __DIR__ . '/update_news.php';
```

2. Use a free service like **IFTTT** or **Zapier** to call:
```
https://yourwebsite.com/whats_happening/api/trigger_update.php?token=your_secret_token_here
```

#### Option C: Manual Update (If Automation Not Available)

Simply visit this URL in your browser whenever you want to update:
```
https://yourwebsite.com/whats_happening/api/update_news.php
```

---

## Monitoring Updates

### Check the Update Log
After each update, check `logs/update.log` to see:
- When the update ran
- How many articles were fetched
- Any errors that occurred

**Example log output:**
```
[2026-03-11 04:25:46] === News Update Started ===
[2026-03-11 04:25:46] Starting news fetch from NewsAPI...
[2026-03-11 04:25:48] Successfully fetched 14 articles from NewsAPI
[2026-03-11 04:25:48] Successfully updated news.php with 14 new articles
[2026-03-11 04:25:48] === News Update Completed Successfully ===
```

### Verify Updates on Your Site
1. Visit your website
2. Scroll to the top news article
3. Check the date and source
4. Should show the latest articles from NewsAPI

---

## Troubleshooting

### Issue: "No articles fetched" or "NewsAPI error"
**Solution:** 
- Verify your API key is correct: `e0e129f3675a4225975c6c36f04ecfe0`
- Check your NewsAPI account at https://newsapi.org to ensure you haven't exceeded your quota (free tier: 100 requests/day)
- Ensure your server can make outbound HTTPS connections

### Issue: "Could not write to news.php"
**Solution:**
- Ensure `data/` directory is writable: `chmod 755 data/`
- Check that `data/news.php` is not locked by another process
- Verify your hosting provider allows PHP to write files

### Issue: Cron job not running
**Solution:**
- Check your hosting provider's cron logs
- Verify the command path is correct
- Test manually: `php /path/to/api/update_news.php`
- Contact your hosting provider's support

---

## Advanced: Custom News Queries

To fetch different news topics, edit `api/update_news.php` line 12:

```php
'q' => 'Iran war 2026',  // Change this to your query
```

Examples:
- `'q' => 'Middle East conflict'`
- `'q' => 'US Israel military'`
- `'q' => 'Lebanon Syria'`

---

## Security Notes

1. **API Key Protection:** Your NewsAPI key is embedded in the script. Keep `api/update_news.php` private and don't share it.
2. **Rate Limiting:** The free NewsAPI tier allows 100 requests/day. Scheduling updates every 4-6 hours keeps you well under this limit.
3. **File Permissions:** Ensure `logs/` directory is writable but not publicly accessible.

---

## Support

For issues with:
- **NewsAPI:** Visit https://newsapi.org/docs
- **Cron Jobs:** Contact your hosting provider's support
- **PHP:** Ensure your server runs PHP 8.0+

---

## Next Steps

1. Upload your website to your hosting provider
2. Set up the cron job (or choose another automation method)
3. Wait for the first update to run
4. Check `logs/update.log` to confirm it worked
5. Refresh your website to see the new articles!

Your website will now automatically stay updated with the latest news about the Iran conflict. 🚀
