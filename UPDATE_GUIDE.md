# How to Update "What's Happening" News

Updating your website is designed to be simple and secure. You have two main ways to do it:

---

## 1. Manual Update (Recommended for Accuracy)
All news content is stored in a single file: `/data/news.php`. To add a new article, simply add a new entry to the `$newsArticles` array.

### Steps:
1. Open `/data/news.php` in any text editor.
2. Copy an existing article block (from `[` to `],`).
3. Paste it at the **top** of the array (so it appears first on the site).
4. Update the fields: `title`, `date`, `source`, `source_url`, `category`, `image`, `summary`, `content`, and `tags`.
5. Save the file. The website will update **instantly**.

### Example Entry:
```php
[
    'id'       => 8, // Increment the ID
    'title'    => 'New Update Title Here',
    'date'     => 'March 12, 2026',
    'source'   => 'Reuters',
    'source_url' => 'https://reuters.com/link-to-article',
    'category' => 'Breaking News',
    'image'    => 'images/new_photo.jpg',
    'image_caption' => 'Description of the photo. [Source]',
    'summary'  => 'A short 1-2 sentence summary.',
    'content'  => 'The full article text goes here...',
    'tags'     => ['Tag1', 'Tag2'],
],
```

---

## 2. Automated Updates (Advanced)
If you want the site to update itself, you can create a "Cron Job" (a scheduled task) on your server that runs a PHP script to fetch news from a public API (like NewsAPI.org or GNews).

### How to set it up:
1. **Get an API Key:** Sign up for a free key at [NewsAPI.org](https://newsapi.org/).
2. **Create an Update Script:** I can write a script for you that connects to this API and automatically writes new articles into your `/data/news.php` file.
3. **Schedule it:** Set your server to run this script once every hour or once a day.

---

## 3. Updating Photos
1. Upload your new photo to the `/images/` folder.
2. Reference the filename (e.g., `images/my_new_photo.jpg`) in the `image` field of your news entry in `/data/news.php`.

---

## 4. Security Note
When updating, **never** change the structure of the PHP file (the `<?php`, `return [`, and `];` parts). If you make a syntax error, the site may show a blank page. Always keep a backup of the working `news.php` before editing!
