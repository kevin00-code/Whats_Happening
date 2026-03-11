<?php
/**
 * What's Happening — Main Homepage
 * Verified news about the ongoing Iran conflict
 */

require_once __DIR__ . '/includes/security.php';

$csrfToken   = generateCSRFToken();
$newsArticles = require __DIR__ . '/data/news.php';
$currentDate  = date('l, F j, Y');
$currentTime  = gmdate('H:i') . ' UTC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="google-site-verification" content="aTU6WyC787HJDb9Qq0YfTtFQzmveAjPKSIlFZKxWcYU" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="description" content="What's Happening — Verified, real-time news coverage of the ongoing US-Israel war on Iran. Sourced from Al Jazeera, Brookings, ISW, UN, and other credible outlets.">
  <meta name="robots" content="index, follow">
  <meta name="author" content="What's Happening News">
  <meta property="og:title" content="What's Happening — Iran War Coverage">
  <meta property="og:description" content="Verified news about the ongoing US-Israel war on Iran. Updated daily from credible sources.">
  <meta property="og:type" content="website">

  <!-- Security: prevent clickjacking via meta -->
  <meta http-equiv="X-Frame-Options" content="DENY">
  <meta http-equiv="X-Content-Type-Options" content="nosniff">

  <title>What's Happening — "Iran War": News Coverage</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "NewsMediaOrganization",
    "name": "What's Happening",
    "description": "Verified news coverage of the 2026 Iran conflict",
    "url": "https://whats-happening.news"
  }
  </script>
</head>
<body>

<!-- Dev-Tools Warning Overlay -->
<div id="devtools-warning" aria-hidden="true">
  <h1>&#x26A0; Security Alert</h1>
  <p>Developer tools have been detected. This site employs security monitoring to protect its content and users. Please close developer tools to continue browsing.</p>
  <p style="margin-top:12px;font-size:0.85rem;color:#555">If you are a security researcher, please contact us through official channels.</p>
</div>

<!-- Navigation -->
<nav class="navbar" role="navigation" aria-label="Main navigation">
  <a href="index.php" class="navbar-brand" aria-label="What's Happening Home">
    <div class="navbar-logo" aria-hidden="true">W</div>
    <span class="navbar-title">What's <span>Happening</span></span>
  </a>
  <div class="navbar-live">
    <div class="live-dot" aria-hidden="true"></div>
    <span id="live-clock" aria-live="polite"><?= $currentTime ?></span>
  </div>
</nav>

<!-- Breaking News Ticker -->
<div class="ticker-wrap" role="marquee" aria-label="Breaking news ticker">
  <span class="ticker-label">BREAKING</span>
  <span class="ticker-content">
    &#x2022; US-Israel war on Iran enters Day 12 — over 1,300 civilians killed &nbsp;&nbsp;
    &#x2022; Iran mines Strait of Hormuz; US destroys 16 mine-laying vessels &nbsp;&nbsp;
    &#x2022; Oil prices surge toward $120/barrel amid energy crisis &nbsp;&nbsp;
    &#x2022; UAE's Ruwais refinery halts operations after drone strike &nbsp;&nbsp;
    &#x2022; Lebanon: 570+ killed, 667,000 displaced &nbsp;&nbsp;
    &#x2022; Trump: War could end "very soon" — Senate demands public hearings &nbsp;&nbsp;
    &#x2022; Iran names Mojtaba Khamenei as new supreme leader &nbsp;&nbsp;
    &#x2022; WHO warns of toxic "black rain" over Tehran &nbsp;&nbsp;
    &#x2022; 43,000+ Americans evacuated from Middle East &nbsp;&nbsp;
  </span>
</div>

<!-- Hero Section -->
<section class="hero" role="banner">
  <p class="hero-eyebrow">&#x1F6A8; Live Coverage — <?= htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8') ?></p>
  <h1 class="hero-title">
    The <span class="highlight">Iran War</span>:<br>
    What You Need to Know
  </h1>
  <p class="hero-subtitle">
    Verified, fact-based reporting on the ongoing US-Israel military campaign against Iran.
    Sourced exclusively from credible international outlets.
  </p>
  <div class="hero-stats" role="list" aria-label="Key conflict statistics">
    <div class="stat-item" role="listitem">
      <span class="stat-number">1,300+</span>
      <span class="stat-label">Civilians Killed in Iran</span>
    </div>
    <div class="stat-item" role="listitem">
      <span class="stat-number">Day 12</span>
      <span class="stat-label">Of Active Conflict</span>
    </div>
    <div class="stat-item" role="listitem">
      <span class="stat-number">$120</span>
      <span class="stat-label">Oil Price / Barrel</span>
    </div>
    <div class="stat-item" role="listitem">
      <span class="stat-number">9+</span>
      <span class="stat-label">Countries Affected</span>
    </div>
  </div>
</section>

<!-- Main Content -->
<div class="container">
  <div class="main-grid">

    <!-- News Feed -->
    <main role="main" aria-label="News articles">
      <div class="section-header">
        <h2>Latest Verified Reports</h2>
        <span class="section-badge">Verified</span>
      </div>

      <?php foreach ($newsArticles as $article): ?>
      <article class="news-card" id="article-<?= (int)$article['id'] ?>" aria-labelledby="title-<?= (int)$article['id'] ?>">

        <?php if (!empty($article['image'])): ?>
        <figure>
          <img
            src="<?= htmlspecialchars($article['image'], ENT_QUOTES, 'UTF-8') ?>"
            alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>"
            class="news-card-image"
            loading="lazy"
          >
          <?php if (!empty($article['image_caption'])): ?>
          <figcaption class="image-caption">
            <?= htmlspecialchars($article['image_caption'], ENT_QUOTES, 'UTF-8') ?>
          </figcaption>
          <?php endif; ?>
        </figure>
        <?php endif; ?>

        <div class="news-card-body">
          <div class="news-card-meta">
            <span class="news-category"><?= htmlspecialchars($article['category'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="news-date">
              <time datetime="<?= date('Y-m-d', strtotime($article['date'])) ?>">
                <?= htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8') ?>
              </time>
            </span>
            <span class="news-source">
              Source:
              <?php if (!empty($article['source_url'])): ?>
                <a href="<?= htmlspecialchars($article['source_url'], ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank" rel="noopener noreferrer">
                  <?= htmlspecialchars($article['source'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              <?php else: ?>
                <?= htmlspecialchars($article['source'], ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </span>
          </div>

          <h2 class="news-card-title" id="title-<?= (int)$article['id'] ?>">
            <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
          </h2>

          <p class="news-card-summary">
            <?= htmlspecialchars($article['summary'], ENT_QUOTES, 'UTF-8') ?>
          </p>

          <div class="news-card-content">
            <?php
            $paragraphs = explode("\n\n", $article['content']);
            foreach ($paragraphs as $para):
              $para = trim($para);
              if ($para):
            ?>
            <p><?= htmlspecialchars($para, ENT_QUOTES, 'UTF-8') ?></p>
            <?php
              endif;
            endforeach;
            ?>
          </div>

          <?php if (!empty($article['tags'])): ?>
          <div class="news-tags" aria-label="Article tags">
            <?php foreach ($article['tags'] as $tag): ?>
            <span class="news-tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Public Domain Disclaimer (per article) -->
          <div class="disclaimer" role="note" aria-label="Content disclaimer">
            <strong>&#x24D8; Disclaimer:</strong> What's Happening does not claim authorship or ownership of this report. All content is sourced from publicly available, verified news outlets and is reproduced in the public interest. Original reporting credit belongs to the cited source.
          </div>

        </div>
      </article>
      <?php endforeach; ?>

    </main>

    <!-- Sidebar -->
    <aside class="sidebar" role="complementary" aria-label="Conflict overview">

      <!-- Key Statistics -->
      <div class="sidebar-widget">
        <h3>&#x1F4CA; Conflict Statistics</h3>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">War Start Date</span>
          <span class="sidebar-stat-value">Feb 28, 2026</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Days of Conflict</span>
          <span class="sidebar-stat-value">12</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Iran Civilian Deaths</span>
          <span class="sidebar-stat-value">1,300+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">US Soldiers Killed</span>
          <span class="sidebar-stat-value">7</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">US Wounded</span>
          <span class="sidebar-stat-value orange">140+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Lebanon Deaths</span>
          <span class="sidebar-stat-value">570+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Lebanon Displaced</span>
          <span class="sidebar-stat-value orange">667,000+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">US Targets Hit in Iran</span>
          <span class="sidebar-stat-value">5,000+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Americans Evacuated</span>
          <span class="sidebar-stat-value green">43,000+</span>
        </div>
        <div class="sidebar-stat">
          <span class="sidebar-stat-label">Oil Price (Brent)</span>
          <span class="sidebar-stat-value">~$120/bbl</span>
        </div>
      </div>

      <!-- Timeline -->
      <div class="sidebar-widget">
        <h3>&#x1F4C5; Key Timeline</h3>
        <div class="timeline-item">
          <div class="timeline-date">Feb 28, 2026</div>
          <div class="timeline-text">US &amp; Israel launch "Operation Epic Fury." Supreme Leader Khamenei killed in airstrike.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 1, 2026</div>
          <div class="timeline-text">Iran launches retaliatory missile strikes on Israel, Gulf states, and US bases.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 5, 2026</div>
          <div class="timeline-text">Humanitarian crisis escalates; UN warns of catastrophic civilian impact.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 7, 2026</div>
          <div class="timeline-text">Iran strikes Iranian girls' school — 175 students killed. HRW calls for war crimes probe.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 8, 2026</div>
          <div class="timeline-text">Mojtaba Khamenei named new Supreme Leader. Iran mines Strait of Hormuz.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 10, 2026</div>
          <div class="timeline-text">US destroys 16 Iranian mine-laying vessels. Oil prices near $120/barrel.</div>
        </div>
        <div class="timeline-item">
          <div class="timeline-date">Mar 11, 2026</div>
          <div class="timeline-text">Day 12: US announces "most intense" strikes. Trump says war may end "very soon."</div>
        </div>
      </div>

      <!-- Sources -->
      <div class="sidebar-widget">
        <h3>&#x1F4F0; Verified Sources</h3>
        <div style="font-family:var(--font-ui);font-size:0.82rem;color:var(--text-secondary);line-height:2">
          <div>&#x2713; <a href="https://www.aljazeera.com" target="_blank" rel="noopener" style="color:var(--accent-light)">Al Jazeera</a></div>
          <div>&#x2713; <a href="https://www.brookings.edu" target="_blank" rel="noopener" style="color:var(--accent-light)">Brookings Institution</a></div>
          <div>&#x2713; <a href="https://understandingwar.org" target="_blank" rel="noopener" style="color:var(--accent-light)">ISW (Understanding War)</a></div>
          <div>&#x2713; <a href="https://www.theguardian.com" target="_blank" rel="noopener" style="color:var(--accent-light)">The Guardian</a></div>
          <div>&#x2713; <a href="https://www.ohchr.org" target="_blank" rel="noopener" style="color:var(--accent-light)">UN OHCHR</a></div>
          <div>&#x2713; <a href="https://www.hrw.org" target="_blank" rel="noopener" style="color:var(--accent-light)">Human Rights Watch</a></div>
          <div>&#x2713; <a href="https://www.atlanticcouncil.org" target="_blank" rel="noopener" style="color:var(--accent-light)">Atlantic Council</a></div>
          <div>&#x2713; <a href="https://acleddata.com" target="_blank" rel="noopener" style="color:var(--accent-light)">ACLED Data</a></div>
        </div>
      </div>

      <!-- Site Notice -->
      <div class="sidebar-widget">
        <h3>&#x24D8; About This Site</h3>
        <p style="font-family:var(--font-ui);font-size:0.82rem;color:var(--text-secondary);line-height:1.6">
          <strong style="color:var(--text-primary)">What's Happening</strong> is an independent news aggregation platform. We do not produce original journalism. All content is sourced from verified, publicly available international news outlets.
        </p>
        <p style="font-family:var(--font-ui);font-size:0.82rem;color:var(--text-muted);margin-top:8px;line-height:1.6">
          This site does not take credit for any content displayed. All reporting credit belongs to the original sources cited. Content is shared in the public interest.
        </p>
      </div>

    </aside>
  </div>
</div>

<!-- Q&A Section -->
<section class="qa-section" id="ask-question" aria-labelledby="qa-heading">
  <div class="container">
    <div class="section-header">
      <h2 id="qa-heading">Ask a Question</h2>
      <span class="section-badge">AI-Powered</span>
    </div>

    <p class="qa-intro">
      Have a question about the Iran conflict? Ask below and receive an AI-assisted answer
      based on verified public reporting. Questions are processed securely and anonymously.
    </p>

    <form id="qa-form" novalidate autocomplete="off" aria-label="Ask a question about the Iran conflict">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <div class="qa-form">
        <input
          type="text"
          id="qa-input"
          name="question"
          class="qa-input"
          placeholder="e.g. How did the Iran war start? What is happening in Lebanon?"
          maxlength="300"
          minlength="5"
          required
          aria-label="Your question about the Iran conflict"
          aria-describedby="qa-counter qa-error"
          autocomplete="off"
          spellcheck="true"
        >
        <button type="submit" id="qa-submit" class="qa-btn" aria-label="Submit your question">
          Ask Question
        </button>
      </div>
      <div id="qa-counter" style="font-family:var(--font-ui);font-size:0.75rem;color:var(--text-muted);margin-top:-8px;margin-bottom:12px" aria-live="polite">
        300 characters remaining
      </div>
    </form>

    <div id="qa-loading" class="qa-loading" aria-live="polite" aria-busy="true">
      <div class="spinner" aria-hidden="true"></div>
      <span>Searching verified sources for your answer...</span>
    </div>

    <div id="qa-error" class="qa-error" role="alert" aria-live="assertive"></div>

    <div id="qa-answer" class="qa-answer" role="region" aria-label="Answer to your question" aria-live="polite">
      <div class="qa-answer-label">&#x1F916; AI-Assisted Answer</div>
      <div id="qa-answer-text" class="qa-answer-text"></div>
      <div class="disclaimer" style="margin-top:12px">
        <strong>&#x24D8; Note:</strong> This answer is AI-generated based on verified public reporting. It may not reflect the most recent developments. Always consult primary news sources for the latest information. <strong>What's Happening does not claim authorship of this content.</strong>
      </div>
    </div>

    <p style="font-family:var(--font-ui);font-size:0.78rem;color:var(--text-muted);margin-top:1rem">
      &#x1F512; Questions are rate-limited and monitored for security. Malicious inputs are automatically blocked and logged.
    </p>
  </div>
</section>

<!-- Footer -->
<footer class="footer" role="contentinfo">
  <div class="footer-title">What's Happening</div>
  <p style="color:var(--text-muted);font-size:0.8rem;margin-top:4px">
    Verified News Coverage &bull; <?= htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8') ?>
  </p>
  <div class="footer-disclaimer">
    <strong style="color:var(--text-secondary)">Public Domain Disclaimer:</strong>
    What's Happening does not take credit for, claim ownership of, or assert copyright over any news content displayed on this website. All articles, reports, and information are sourced from publicly available, verified international news outlets and reproduced solely in the public interest. Original authorship and reporting credit belongs entirely to the cited sources. This site serves as a news aggregation platform for informational purposes only.
  </div>
  <div class="footer-links" style="margin-top:16px">
    <a href="#ask-question">Ask a Question</a>
    <a href="https://www.aljazeera.com/news/liveblog/2026/3/11/iran-war-live-tehran-says-us-israel-hit-nearly-10000-civilian-sites" target="_blank" rel="noopener">Al Jazeera Live</a>
    <a href="https://www.brookings.edu/articles/after-the-strike-the-danger-of-war-in-iran/" target="_blank" rel="noopener">Brookings Analysis</a>
    <a href="https://understandingwar.org" target="_blank" rel="noopener">ISW Updates</a>
  </div>
  <p style="margin-top:16px;font-size:0.72rem;color:var(--text-muted)">
    &copy; <?= date('Y') ?> What's Happening &bull; This site is protected by security monitoring &bull;
    Unauthorized access attempts are logged and reported
  </p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
