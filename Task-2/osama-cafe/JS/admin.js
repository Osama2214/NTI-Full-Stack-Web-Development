/**
 * Shared behavior for admin-*.php pages.
 *
 * Search, filters, pagination, edit/cancel links, and every admin form
 * (add/edit/delete category or item, save settings, branch CRUD, broadcast)
 * run through fetch() and swap into #admin-content instead of doing a full
 * page reload — see navigate()/submitFormAjax() below. Everything that acts
 * on content inside #admin-content is wired with event delegation (bound
 * once on `document`), so it keeps working after a swap replaces that
 * content with fresh HTML — nothing needs to be manually re-initialized,
 * except the custom-select/number-stepper enhancement, which by nature has
 * to run again on whatever new elements just appeared (see enhanceContent).
 *
 * The login page (admin.php) is deliberately NOT part of this — it's a
 * one-time action per session, not a repeated one, so a normal form post
 * keeps things simple there. Navigating between top-level sections (the nav
 * bar, or "View all" links on the Overview page) is also a real page load,
 * not a fragment swap — those are genuinely different pages/views.
 */
(function () {
  'use strict';

  var contentEl = document.getElementById('admin-content');
  // The server's own record of this page's path (PHP's SCRIPT_NAME), not
  // location.pathname — the address bar can end up wrong (a stale bookmark,
  // an old history entry, autocomplete) and every relative link/form here
  // has no leading slash, so resolving against a possibly-wrong current URL
  // would silently keep building on that wrong path. This anchors every
  // fetch/pushState to a value the server itself vouches for, and each one
  // corrects the address bar going forward.
  var pagePath = contentEl ? contentEl.dataset.pagePath : null;
  var pageBaseHref = pagePath ? location.origin + pagePath : location.href;

  // ---------------------------------------------------------------------
  // Mobile nav toggle
  // ---------------------------------------------------------------------
  var navToggle = document.getElementById('admin-nav-toggle');
  var nav = document.getElementById('admin-nav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  // ---------------------------------------------------------------------
  // Toasts — built server-side for a normal page load, or by showToast()
  // here after an AJAX action. Auto-dismiss after TOAST_LIFETIME_MS with a
  // countdown bar, pausing while hovered/focused; close early with the ×.
  // ---------------------------------------------------------------------
  var TOAST_LIFETIME_MS = 8000;
  var TOAST_TICK_MS = 100;
  var TOAST_FADE_MS = 250;

  function attachToastBehavior(toast) {
    var bar = toast.querySelector('.admin-toast-progress');
    var dismissed = false;
    var paused = false;
    var remaining = TOAST_LIFETIME_MS;

    function hide() {
      if (dismissed) return;
      dismissed = true;
      clearInterval(interval);
      toast.classList.add('hide');
      setTimeout(function () { toast.remove(); }, TOAST_FADE_MS);
    }

    // Driven by a plain interval that sets the bar's width directly (not a
    // CSS @keyframes animation) — a wall-clock JS timer keeps the bar and
    // the actual dismissal reliably in sync.
    var interval = setInterval(function () {
      if (paused || dismissed) return;
      remaining -= TOAST_TICK_MS;
      if (bar) {
        bar.style.width = Math.max(0, (remaining / TOAST_LIFETIME_MS) * 100) + '%';
      }
      if (remaining <= 0) hide();
    }, TOAST_TICK_MS);

    toast.addEventListener('mouseenter', function () { paused = true; });
    toast.addEventListener('mouseleave', function () { paused = false; });
    toast.addEventListener('focusin', function () { paused = true; });
    toast.addEventListener('focusout', function () { paused = false; });

    var closeBtn = toast.querySelector('.admin-toast-close');
    if (closeBtn) closeBtn.addEventListener('click', hide);
  }

  /**
   * A container dedicated to JS-created toasts, appended directly to
   * <body> — deliberately never the server-rendered .admin-toast-container
   * that a normal page load might have put inside #admin-content. That one
   * gets wiped out by the very next navigate() content swap; this one,
   * living outside #admin-content entirely, survives it.
   */
  function getToastRoot() {
    var root = document.getElementById('admin-toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'admin-toast-root';
      root.className = 'admin-toast-container';
      document.body.appendChild(root);
    }
    return root;
  }

  /** Shows a toast for the result of an AJAX action — same look/behavior as a server-rendered one. */
  function showToast(type, message) {
    var container = getToastRoot();

    var toast = document.createElement('div');
    toast.className = 'admin-toast ' + type;
    toast.setAttribute('role', 'status');

    var span = document.createElement('span');
    span.textContent = message;
    toast.appendChild(span);

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'admin-toast-close';
    closeBtn.setAttribute('aria-label', 'Dismiss');
    closeBtn.innerHTML = '&times;';
    toast.appendChild(closeBtn);

    var bar = document.createElement('div');
    bar.className = 'admin-toast-progress';
    toast.appendChild(bar);

    container.appendChild(toast);
    attachToastBehavior(toast);
  }

  document.querySelectorAll('.admin-toast').forEach(attachToastBehavior);

  // ---------------------------------------------------------------------
  // Custom confirm dialog — replaces the browser's native confirm() popup
  // for any <form data-confirm="message">. A single overlay, reused for
  // whichever form triggers it (delegation handles new forms after a swap
  // — see the submit listener further down).
  // ---------------------------------------------------------------------
  var confirmOverlay = null;
  var confirmMessageEl, confirmOkBtn, confirmCancelBtn;
  var pendingForm = null;

  var CONFIRM_ICON_WARNING = '<path d="M12 3L22 20H2L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 9.5v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="16.7" r="1" fill="currentColor"/>';
  var CONFIRM_ICON_SEND = '<path d="M21 3L2 10.3L10.3 13.7L13.7 22L21 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/><path d="M21 3L10.3 13.7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>';

  var confirmIconEl;

  function ensureConfirmDialog() {
    if (confirmOverlay) return;
    confirmOverlay = document.createElement('div');
    confirmOverlay.className = 'confirm-overlay';
    confirmOverlay.innerHTML =
      '<div class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="confirm-message">' +
        '<div class="confirm-icon"><svg viewBox="0 0 24 24" fill="none"></svg></div>' +
        '<p class="confirm-message" id="confirm-message"></p>' +
        '<div class="confirm-actions">' +
          '<button type="button" class="btn btn-secondary confirm-cancel">Cancel</button>' +
          '<button type="button" class="btn btn-primary confirm-ok">Confirm</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(confirmOverlay);

    confirmMessageEl = confirmOverlay.querySelector('.confirm-message');
    confirmOkBtn = confirmOverlay.querySelector('.confirm-ok');
    confirmCancelBtn = confirmOverlay.querySelector('.confirm-cancel');
    confirmIconEl = confirmOverlay.querySelector('.confirm-icon');

    confirmOkBtn.addEventListener('click', function () {
      var form = pendingForm;
      closeConfirmDialog();
      if (form) submitFormAjax(form);
    });
    confirmCancelBtn.addEventListener('click', closeConfirmDialog);
    confirmOverlay.addEventListener('click', function (e) {
      if (e.target === confirmOverlay) closeConfirmDialog();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && confirmOverlay.classList.contains('open')) closeConfirmDialog();
    });
  }

  function closeConfirmDialog() {
    if (confirmOverlay) confirmOverlay.classList.remove('open');
    pendingForm = null;
  }

  function openConfirmDialog(form) {
    ensureConfirmDialog();
    pendingForm = form;
    var isDanger = form.dataset.confirmDanger === 'true';
    confirmMessageEl.textContent = form.dataset.confirm;
    confirmOkBtn.textContent = form.dataset.confirmLabel || 'Confirm';
    confirmOkBtn.classList.toggle('btn-danger', isDanger);
    confirmOkBtn.classList.toggle('btn-primary', !isDanger);
    confirmIconEl.classList.toggle('danger', isDanger);
    confirmIconEl.querySelector('svg').innerHTML = isDanger ? CONFIRM_ICON_WARNING : CONFIRM_ICON_SEND;
    confirmOverlay.classList.add('open');
    confirmOkBtn.focus();
  }

  // ---------------------------------------------------------------------
  // AJAX navigation — fetch a page's own content fragment (the part inside
  // #admin-content) and swap it in, instead of a full reload.
  // ---------------------------------------------------------------------

  /** Fetches url as a dashboard fragment. Resolves to its HTML; redirects the whole page itself if the session's expired. */
  function fetchFragment(url) {
    // no-store: this same URL (often the page's own clean base — e.g. every
    // action ends by re-navigating there) gets fetched repeatedly, and the
    // data behind it just changed; a cached response would silently show
    // stale content.
    return fetch(url, { headers: { 'X-Requested-With': 'fetch' }, cache: 'no-store' }).then(function (res) {
      var contentType = res.headers.get('Content-Type') || '';
      if (contentType.indexOf('application/json') !== -1) {
        return res.json().then(function (data) {
          if (data.authRequired) {
            window.location.href = 'admin.php';
            return Promise.reject(new Error('auth required'));
          }
          return Promise.reject(new Error(data.message || 'Request failed'));
        });
      }
      if (!res.ok) {
        // Not JSON and not a success status — e.g. a 404 HTML page. Never
        // treat that as valid fragment content.
        return Promise.reject(new Error('HTTP ' + res.status));
      }
      return res.text();
    });
  }

  function navigate(url, push) {
    // Normalized once, up front, against the server-verified base (not
    // location.href, which is what can be wrong in the first place) — so
    // every use below (fetch, pushState, the error fallback) works with a
    // real, correctly-rooted absolute URL.
    url = new URL(url, pageBaseHref).href;
    if (!contentEl) {
      window.location.href = url;
      return;
    }
    fetchFragment(url)
      .then(function (html) {
        contentEl.innerHTML = html;
        enhanceContent(contentEl);
        // Deliberately no scroll-to-top here — a search box further down
        // the page (e.g. subscriber search) would otherwise get yanked out
        // from under whoever's typing in it. Scroll position stays exactly
        // where it was.
        if (push) history.pushState({ ajax: true }, '', url);
      })
      .catch(function () {
        // Something went wrong fetching the fragment (network hiccup, etc.)
        // — fall back to a real navigation rather than leaving a stale or
        // half-updated page on screen.
        window.location.href = url;
      });
  }

  window.addEventListener('popstate', function () {
    navigate(location.href, false);
  });

  function submitFormAjax(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    // Resolved against pageBaseHref, not left to fetch()'s own relative
    // resolution (which uses the document's actual current URL — the very
    // thing that can be wrong here).
    var actionUrl = new URL(form.getAttribute('action'), pageBaseHref).href;

    fetch(actionUrl, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'fetch' },
      cache: 'no-store',
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.authRequired) {
          window.location.href = 'admin.php';
          return;
        }
        showToast(data.type || (data.ok ? 'success' : 'warn'), data.message || 'Done.');
        // Refresh from the page's own clean base URL — matches where a
        // normal (non-AJAX) form post used to land after its redirect.
        navigate(pagePath || location.pathname, true);
      })
      .catch(function () {
        // Couldn't tell whether the action went through — a real reload
        // shows the true current state instead of guessing.
        window.location.reload();
      })
      .finally(function () {
        if (submitBtn) submitBtn.disabled = false;
      });
  }

  // ---------------------------------------------------------------------
  // Delegated form submit — GET forms (search/filter) navigate; POST forms
  // either go through the confirm dialog first or submit via fetch directly.
  // ---------------------------------------------------------------------
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.closest('#admin-content')) return;

    if (form.method.toLowerCase() === 'get') {
      e.preventDefault();
      var qs = new URLSearchParams(new FormData(form)).toString();
      var base = form.getAttribute('action') || pagePath || location.pathname;
      // Resolve through URL() against the server-verified base, rather than
      // raw string concatenation against a possibly-wrong current location.
      var target = new URL(base, pageBaseHref);
      target.search = qs;
      navigate(target.pathname + target.search, true);
      return;
    }

    if (form.dataset.confirm) {
      e.preventDefault();
      openConfirmDialog(form);
      return;
    }

    e.preventDefault();
    submitFormAjax(form);
  });

  // ---------------------------------------------------------------------
  // Delegated link clicks — same-page links (pagination, edit/cancel,
  // clear-filter) navigate via fetch. mailto:/tel:/new-tab/external/
  // different-page links are left completely alone.
  // ---------------------------------------------------------------------
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link || !link.closest('#admin-content')) return;
    if (link.target === '_blank') return;

    var href = link.getAttribute('href') || '';
    if (href === '' || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) return;

    var url;
    try {
      url = new URL(href, pageBaseHref);
    } catch (err) {
      return;
    }
    if (url.origin !== location.origin || url.pathname !== (pagePath || location.pathname)) return;

    e.preventDefault();
    navigate(url.pathname + url.search, true);
  });

  // ---------------------------------------------------------------------
  // "Copy All Emails" button (admin-messages.php)
  // ---------------------------------------------------------------------
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#copy-emails-btn');
    if (!btn) return;
    navigator.clipboard.writeText(btn.dataset.emails).then(function () {
      var original = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = original; }, 1500);
    });
  });

  // ---------------------------------------------------------------------
  // Broadcast form template picker (admin-messages.php) — delegated
  // because the form (and its #template-select) gets replaced by any AJAX
  // content swap.
  // ---------------------------------------------------------------------
  var BROADCAST_TEMPLATES = {
    new_item: {
      subject: '☕ New on the Menu: [Item Name]',
      body: 'Hi there,\n\nWe just added something new to the menu — [Item Name]! [A line or two on what makes it special — the beans, the flavor, how it\'s made].\n\nCome try it this week at Osama Café.\n\nSee you soon,\nOsama Café',
    },
    seasonal: {
      subject: 'Limited Time: [Special Name] is here',
      body: 'Hi there,\n\nFor a limited time, we\'re serving [Special Name] — [short description]. Available until [end date], while it lasts.\n\nDon\'t miss it!\n\n— Osama Café',
    },
    discount: {
      subject: 'This Week Only: [X]% Off [Item or Category]',
      body: 'Hi there,\n\nAs a thank-you for being part of our community, enjoy [X]% off [item or category] this week at Osama Café. Just mention this email when you order.\n\nOffer valid [start date] – [end date].\n\n— Osama Café',
    },
    announcement: {
      subject: 'An Update from Osama Café',
      body: 'Hi there,\n\nWe wanted to share some news: [your announcement — new hours, a new location, an event, etc.]\n\nThanks for being part of our story.\n\n— Osama Café',
    },
    we_miss_you: {
      subject: 'We miss you at Osama Café ☕',
      body: 'Hi there,\n\nIt\'s been a while! Come back and enjoy a fresh cup on us — show this email for [your offer, e.g. a free pastry with any drink].\n\nWe\'d love to see you again.\n\n— Osama Café',
    },
  };

  document.addEventListener('change', function (e) {
    if (e.target.id !== 'template-select') return;
    var t = BROADCAST_TEMPLATES[e.target.value];
    if (!t) return;
    var subjectInput = document.getElementById('broadcast-subject');
    var bodyInput = document.getElementById('broadcast-body');
    if (!subjectInput || !bodyInput) return;
    subjectInput.value = t.subject;
    bodyInput.value = t.body;
    subjectInput.focus();
  });

  // ---------------------------------------------------------------------
  // Custom <select> dropdowns — a native select's open option list is drawn
  // by the OS, not the page, so no CSS can theme its colors/hover state.
  // This builds a themed replacement on top of every <select>, keeping the
  // real one in the DOM (invisible, overlaying the fake trigger) so form
  // submission and "required" validation still work exactly as before.
  // ---------------------------------------------------------------------
  function enhanceSelect(select) {
    var wrapper = document.createElement('div');
    wrapper.className = 'custom-select';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('custom-select-native');
    select.tabIndex = -1;

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    wrapper.appendChild(trigger);

    var optionsList = document.createElement('div');
    optionsList.className = 'custom-select-options';
    optionsList.setAttribute('role', 'listbox');
    wrapper.appendChild(optionsList);

    var optionEls = [];

    function renderOptions() {
      optionsList.innerHTML = '';
      optionEls = Array.prototype.map.call(select.options, function (opt, i) {
        var item = document.createElement('div');
        item.className = 'custom-select-option';
        item.textContent = opt.textContent.trim();
        item.setAttribute('role', 'option');
        item.addEventListener('click', function () {
          select.selectedIndex = i;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          syncFromSelect();
          closeDropdown();
          trigger.focus();
        });
        optionsList.appendChild(item);
        return item;
      });
    }

    function syncFromSelect() {
      var current = select.options[select.selectedIndex];
      trigger.textContent = current ? current.textContent.trim() : '';
      optionEls.forEach(function (item, i) {
        item.classList.toggle('selected', i === select.selectedIndex);
      });
    }

    function openDropdown() {
      wrapper.classList.add('open');
      trigger.setAttribute('aria-expanded', 'true');
    }
    function closeDropdown() {
      wrapper.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', function () {
      wrapper.classList.contains('open') ? closeDropdown() : openDropdown();
    });

    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        var delta = e.key === 'ArrowDown' ? 1 : -1;
        var next = Math.min(select.options.length - 1, Math.max(0, select.selectedIndex + delta));
        select.selectedIndex = next;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncFromSelect();
        openDropdown();
      } else if (e.key === 'Escape') {
        closeDropdown();
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrapper.contains(e.target)) closeDropdown();
    });

    select.addEventListener('change', syncFromSelect);

    renderOptions();
    syncFromSelect();
  }

  // ---------------------------------------------------------------------
  // Custom number-input steppers — replaces the browser's native (and
  // hard-to-theme) spin arrows with buttons matching the dashboard style.
  // ---------------------------------------------------------------------
  function enhanceNumberInput(input) {
    var wrapper = document.createElement('div');
    wrapper.className = 'number-stepper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    function makeButton(direction, label, path) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'number-stepper-btn ' + direction;
      btn.setAttribute('aria-label', label);
      btn.innerHTML = '<svg viewBox="0 0 10 10" fill="none"><path d="' + path + '" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      btn.addEventListener('click', function () {
        try {
          direction === 'up' ? input.stepUp() : input.stepDown();
        } catch (err) {
          var step = parseFloat(input.step) || 1;
          input.value = (parseFloat(input.value) || 0) + (direction === 'up' ? step : -step);
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
      return btn;
    }

    wrapper.appendChild(makeButton('up', 'Increase', 'M1.5 7L5 3.5L8.5 7'));
    wrapper.appendChild(makeButton('down', 'Decrease', 'M1.5 3.5L5 7L8.5 3.5'));
  }

  /** Runs every DOM-enhancing initializer over a freshly-loaded or freshly-swapped region. */
  function enhanceContent(root) {
    root.querySelectorAll('select').forEach(enhanceSelect);
    root.querySelectorAll('input[type="number"]').forEach(enhanceNumberInput);
  }

  if (contentEl) enhanceContent(contentEl);
})();
