/**
 * What's Happening — Client-Side Security & Interactivity
 * - Developer Tools detection & blocking
 * - Console protection
 * - Right-click & keyboard shortcut blocking
 * - Q&A API integration
 */

(function () {
  'use strict';

  /* ── 1. Console Protection ─────────────────────────────────────────────── */
  const noop = function () {};
  const consoleMethods = ['log','debug','info','warn','error','table','dir','dirxml',
                          'group','groupEnd','groupCollapsed','trace','assert','profile',
                          'profileEnd','count','time','timeEnd','timeLog','timeStamp','clear'];
  consoleMethods.forEach(function (m) {
    try { console[m] = noop; } catch (e) {}
  });

  // Override console object entirely
  try {
    Object.defineProperty(window, 'console', {
      get: function () {
        return {
          log: noop, debug: noop, info: noop, warn: noop, error: noop,
          table: noop, dir: noop, trace: noop, assert: noop, clear: noop,
          group: noop, groupEnd: noop, count: noop, time: noop, timeEnd: noop
        };
      },
      set: noop,
      configurable: false
    });
  } catch (e) {}

  /* ── 2. Developer Tools Detection ─────────────────────────────────────── */
  var devToolsOpen = false;
  var warningShown = false;

  function showDevToolsWarning() {
    if (warningShown) return;
    warningShown = true;
    var overlay = document.getElementById('devtools-warning');
    if (overlay) {
      overlay.classList.add('active');
      // Report to server
      try {
        fetch('/api/security_report.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ event: 'devtools_open', ts: Date.now() })
        });
      } catch (e) {}
    }
  }

  // Method 1: Size-based detection
  var threshold = 160;
  function checkDevTools() {
    var widthDiff  = window.outerWidth  - window.innerWidth;
    var heightDiff = window.outerHeight - window.innerHeight;
    if (widthDiff > threshold || heightDiff > threshold) {
      if (!devToolsOpen) {
        devToolsOpen = true;
        showDevToolsWarning();
      }
    } else {
      devToolsOpen = false;
    }
  }

  // Method 2: toString trick
  var element = new Image();
  Object.defineProperty(element, 'id', {
    get: function () {
      devToolsOpen = true;
      showDevToolsWarning();
    }
  });

  // Method 3: debugger timing
  function detectDebugger() {
    var start = performance.now();
    // eslint-disable-next-line no-debugger
    debugger;
    var end = performance.now();
    if (end - start > 100) {
      showDevToolsWarning();
    }
  }

  setInterval(checkDevTools, 1000);
  setInterval(detectDebugger, 3000);

  /* ── 3. Right-Click & Context Menu Blocking ────────────────────────────── */
  document.addEventListener('contextmenu', function (e) {
    e.preventDefault();
    return false;
  });

  /* ── 4. Keyboard Shortcut Blocking ────────────────────────────────────── */
  document.addEventListener('keydown', function (e) {
    // F12
    if (e.key === 'F12') { e.preventDefault(); return false; }
    // Ctrl+Shift+I / Cmd+Option+I (DevTools)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'I' || e.key === 'i')) {
      e.preventDefault(); return false;
    }
    // Ctrl+Shift+J (Console)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'J' || e.key === 'j')) {
      e.preventDefault(); return false;
    }
    // Ctrl+Shift+C (Inspector)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'C' || e.key === 'c')) {
      e.preventDefault(); return false;
    }
    // Ctrl+U (View Source)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'U' || e.key === 'u')) {
      e.preventDefault(); return false;
    }
    // Ctrl+S (Save)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'S' || e.key === 's')) {
      e.preventDefault(); return false;
    }
  });

  /* ── 5. Text Selection Disable (optional, UX trade-off) ─────────────── */
  // Disabled to maintain readability — news sites should allow text selection

  /* ── 6. Q&A API Integration ────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var form      = document.getElementById('qa-form');
    var input     = document.getElementById('qa-input');
    var btn       = document.getElementById('qa-submit');
    var answerBox = document.getElementById('qa-answer');
    var answerTxt = document.getElementById('qa-answer-text');
    var errorBox  = document.getElementById('qa-error');
    var loading   = document.getElementById('qa-loading');

    if (!form) return;

    // Character counter
    input.addEventListener('input', function () {
      var remaining = 300 - this.value.length;
      var counter   = document.getElementById('qa-counter');
      if (counter) {
        counter.textContent = remaining + ' characters remaining';
        counter.style.color = remaining < 50 ? '#e63946' : '#606078';
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var question = input.value.trim();
      if (!question || question.length < 5) {
        showError('Please enter a question of at least 5 characters.');
        return;
      }
      if (question.length > 300) {
        showError('Question must be 300 characters or fewer.');
        return;
      }

      // Basic client-side injection check
      var dangerPatterns = [/<script/i, /javascript:/i, /on\w+\s*=/i,
                            /union.*select/i, /drop\s+table/i, /--\s*$/];
      for (var i = 0; i < dangerPatterns.length; i++) {
        if (dangerPatterns[i].test(question)) {
          showError('Invalid input detected. Please ask a legitimate question.');
          return;
        }
      }

      setLoading(true);
      hideAll();

      var csrfToken = document.querySelector('meta[name="csrf-token"]');
      var token     = csrfToken ? csrfToken.getAttribute('content') : '';

      fetch('/whats_happening/api/ask.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ question: question, csrf_token: token })
      })
      .then(function (res) {
        if (!res.ok) {
          if (res.status === 429) throw new Error('Too many requests. Please wait a moment before asking again.');
          if (res.status === 403) throw new Error('Request blocked for security reasons.');
          throw new Error('Server error (' + res.status + '). Please try again.');
        }
        return res.json();
      })
      .then(function (data) {
        setLoading(false);
        if (data.success && data.answer) {
          answerTxt.textContent = data.answer;
          answerBox.classList.add('visible');
          input.value = '';
          var counter = document.getElementById('qa-counter');
          if (counter) counter.textContent = '300 characters remaining';
        } else {
          showError(data.error || 'Unable to get an answer. Please try again.');
        }
      })
      .catch(function (err) {
        setLoading(false);
        showError(err.message || 'Network error. Please check your connection.');
      });
    });

    function setLoading(state) {
      btn.disabled = state;
      if (state) {
        loading.classList.add('visible');
        btn.textContent = 'Asking...';
      } else {
        loading.classList.remove('visible');
        btn.textContent = 'Ask Question';
      }
    }

    function showError(msg) {
      errorBox.textContent = msg;
      errorBox.classList.add('visible');
    }

    function hideAll() {
      answerBox.classList.remove('visible');
      errorBox.classList.remove('visible');
    }
  });

  /* ── 7. Smooth scroll for anchor links ─────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  });

  /* ── 8. Live clock ─────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var clockEl = document.getElementById('live-clock');
    if (!clockEl) return;
    function updateClock() {
      var now = new Date();
      clockEl.textContent = now.toUTCString().replace('GMT', 'UTC');
    }
    updateClock();
    setInterval(updateClock, 1000);
  });

})();
