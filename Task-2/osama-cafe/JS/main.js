/* ==========================================================================
   Osama Café — main.js
   Vanilla JS interactions: preloader, sticky header, scrollspy, reveal
   animations, animated stats, menu filter, gallery lightbox, testimonial
   slider, FAQ accordion, contact form (mailto/WhatsApp), newsletter signup,
   toast notifications, back-to-top and mobile menu.
   No frameworks, no dependencies — everything below is plain DOM APIs.
   ========================================================================== */

window.__osamaCafeJsReady = true;

document.addEventListener('DOMContentLoaded', () => {
  initLoader();
  initHeaderScroll();
  initMobileMenu();
  initScrollSpy();
  initScrollToTop();
  initRevealAnimations();
  initStatCounters();
  initMenuFilter();
  initGallery();
  initTestimonialSlider();
  initFAQ();
  initContactForm();
  initNewsletterForm();
});

/* ------------------------------------------------------------------ *
 * Preloader — fades out once the window has actually finished
 * loading, rather than on a fixed timer.
 * ------------------------------------------------------------------ */
function initLoader() {
  const loader = document.getElementById('page-loader');
  if (!loader) return;

  let hidden = false;
  const hideLoader = () => {
    if (hidden) return; // guard: this must only ever run once
    hidden = true;
    loader.classList.add('fade-out');
    setTimeout(() => {
      loader.style.display = 'none';
    }, 450);
  };

  if (document.readyState === 'complete') {
    hideLoader();
  } else {
    window.addEventListener('load', hideLoader, { once: true });
  }
}

/* ------------------------------------------------------------------ *
 * Sticky header — shrinks and gets a stronger backdrop once the
 * page has scrolled past the hero.
 * ------------------------------------------------------------------ */
function initHeaderScroll() {
  const header = document.getElementById('site-header');
  if (!header) return;

  const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 50);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

/* ------------------------------------------------------------------ *
 * Mobile nav — close the slide-out menu whenever a link is tapped.
 * ------------------------------------------------------------------ */
function initMobileMenu() {
  const menuToggle = document.getElementById('menu-toggle');
  document.querySelectorAll('.mobile-nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (menuToggle) menuToggle.checked = false;
    });
  });
}

/* ------------------------------------------------------------------ *
 * Scrollspy — highlights the nav link matching whichever section is
 * currently centred in the viewport.
 * ------------------------------------------------------------------ */
function initScrollSpy() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
  if (!sections.length || !navLinks.length) return;

  const setActive = (id) => {
    navLinks.forEach((link) => {
      link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
    });
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) setActive(entry.target.id);
      });
    },
    { rootMargin: '-45% 0px -50% 0px', threshold: 0 }
  );

  sections.forEach((section) => observer.observe(section));
}

/* ------------------------------------------------------------------ *
 * Back-to-top button visibility.
 * ------------------------------------------------------------------ */
function initScrollToTop() {
  const scrollUpBtn = document.getElementById('scroll-up-btn');
  if (!scrollUpBtn) return;

  window.addEventListener(
    'scroll',
    () => scrollUpBtn.classList.toggle('show', window.scrollY > 300),
    { passive: true }
  );
}

/* ------------------------------------------------------------------ *
 * Scroll-reveal — fades/slides any .reveal element into place the
 * first time it enters the viewport.
 * ------------------------------------------------------------------ */
function initRevealAnimations() {
  const items = document.querySelectorAll('.reveal');
  if (!items.length) return;

  if (!('IntersectionObserver' in window)) {
    items.forEach((item) => item.classList.add('active'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  items.forEach((item) => observer.observe(item));
}

/* ------------------------------------------------------------------ *
 * Animated stat counters — count up from 0 to data-target once the
 * stats strip scrolls into view.
 * ------------------------------------------------------------------ */
function initStatCounters() {
  const counters = document.querySelectorAll('.stat-number[data-target]');
  if (!counters.length) return;

  const animate = (el) => {
    const target = parseFloat(el.dataset.target);
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const valueEl = el.querySelector('.stat-value') || el;
    const duration = 1600;
    const start = performance.now();

    const step = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      const value = target * eased;
      valueEl.textContent = decimals ? value.toFixed(decimals) : Math.floor(value).toLocaleString();
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        valueEl.textContent = decimals ? target.toFixed(decimals) : target.toLocaleString();
      }
    };
    requestAnimationFrame(step);
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  counters.forEach((counter) => observer.observe(counter));
}

/* ------------------------------------------------------------------ *
 * Menu filter — toggles specialty cards by data-category.
 * ------------------------------------------------------------------ */
function initMenuFilter() {
  const buttons = document.querySelectorAll('.menu-filter-btn');
  const cards = document.querySelectorAll('.specialty-card');
  if (!buttons.length || !cards.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      buttons.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;

      cards.forEach((card) => {
        const show = filter === 'all' || card.dataset.category === filter;
        card.classList.toggle('hidden-card', !show);
        if (show) {
          card.classList.remove('filtering');
          void card.offsetWidth; // restart animation
          card.classList.add('filtering');
        }
      });
    });
  });
}

/* ------------------------------------------------------------------ *
 * Gallery lightbox — click any gallery photo to open a full-size
 * viewer with keyboard + click navigation.
 * ------------------------------------------------------------------ */
function initGallery() {
  const items = document.querySelectorAll('.gallery-item');
  if (!items.length) return;

  const images = Array.from(items).map((item) => ({
    src: item.querySelector('img').getAttribute('src'),
    caption: item.dataset.caption || item.querySelector('img').alt,
  }));

  const overlay = document.createElement('div');
  overlay.className = 'lightbox-overlay';
  overlay.innerHTML = `
    <div class="lightbox-close" role="button" tabindex="0" aria-label="Close gallery"><i class="fa-solid fa-xmark"></i></div>
    <div class="lightbox-nav lightbox-prev" role="button" tabindex="0" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></div>
    <div class="lightbox-nav lightbox-next" role="button" tabindex="0" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></div>
    <div class="lightbox-content">
      <img id="lightbox-img" src="" alt="">
      <p class="lightbox-caption" id="lightbox-caption"></p>
    </div>`;
  document.body.appendChild(overlay);

  const imgEl = overlay.querySelector('#lightbox-img');
  const capEl = overlay.querySelector('#lightbox-caption');
  let currentIndex = 0;

  const open = (index) => {
    currentIndex = (index + images.length) % images.length;
    imgEl.src = images[currentIndex].src;
    imgEl.alt = images[currentIndex].caption;
    capEl.textContent = images[currentIndex].caption;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  };
  const close = () => {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  };
  const next = () => open(currentIndex + 1);
  const prev = () => open(currentIndex - 1);

  items.forEach((item, idx) => item.addEventListener('click', () => open(idx)));
  overlay.querySelector('.lightbox-close').addEventListener('click', close);
  overlay.querySelector('.lightbox-next').addEventListener('click', next);
  overlay.querySelector('.lightbox-prev').addEventListener('click', prev);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
  document.addEventListener('keydown', (e) => {
    if (!overlay.classList.contains('active')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });
}

/* ------------------------------------------------------------------ *
 * Testimonial slider — autoplay + arrows + dots + swipe.
 * ------------------------------------------------------------------ */
function initTestimonialSlider() {
  const slider = document.querySelector('.testimonial-slider');
  const slidesWrap = document.querySelector('.testimonial-slides');
  if (!slider || !slidesWrap) return;

  const slides = slidesWrap.querySelectorAll('.testimonial-slide');
  const dotsWrap = document.querySelector('.testimonial-dots');
  const prevBtn = document.querySelector('.testimonial-arrow.prev');
  const nextBtn = document.querySelector('.testimonial-arrow.next');
  let index = 0;
  let autoplayTimer;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'testimonial-dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', `Go to review ${i + 1}`);
    dot.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(dot);
  });
  const dots = dotsWrap.querySelectorAll('.testimonial-dot');

  function update() {
    slidesWrap.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === index));
  }
  function goTo(i) {
    index = (i + slides.length) % slides.length;
    update();
    resetAutoplay();
  }
  function next() { goTo(index + 1); }
  function prev() { goTo(index - 1); }
  function resetAutoplay() {
    clearInterval(autoplayTimer);
    autoplayTimer = setInterval(next, 6000);
  }

  prevBtn?.addEventListener('click', prev);
  nextBtn?.addEventListener('click', next);
  slider.addEventListener('mouseenter', () => clearInterval(autoplayTimer));
  slider.addEventListener('mouseleave', resetAutoplay);

  let touchStartX = 0;
  slidesWrap.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
  slidesWrap.addEventListener('touchend', (e) => {
    const diff = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(diff) > 40) (diff > 0 ? prev() : next());
  });

  resetAutoplay();
}

/* ------------------------------------------------------------------ *
 * FAQ accordion — one item open at a time.
 * ------------------------------------------------------------------ */
function initFAQ() {
  const items = document.querySelectorAll('.faq-item');
  if (!items.length) return;

  items.forEach((item) => {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    question.addEventListener('click', () => {
      const isActive = item.classList.contains('active');

      items.forEach((other) => {
        other.classList.remove('active');
        other.querySelector('.faq-answer').style.maxHeight = null;
      });

      if (!isActive) {
        item.classList.add('active');
        answer.style.maxHeight = `${answer.scrollHeight}px`;
      }
    });
  });
}

/* ------------------------------------------------------------------ *
 * Contact form — POSTs to php/contact.php, which validates again on
 * the server and saves the message to the database. If that request
 * can't even be made at all (e.g. the page is opened as a plain file
 * with no PHP server behind it), a pre-filled mailto: link is used as
 * a fallback so the form still does *something* useful.
 * ------------------------------------------------------------------ */
function initContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;

  // Sourced from index.php (which reads them from the database — see
  // /php/admin-settings.php) with sensible fallbacks in case that config
  // object is missing for any reason (e.g. this script runs standalone).
  const cafeConfig = window.OSAMA_CAFE_CONFIG || {};
  const CAFE_EMAIL = cafeConfig.email || 'osamaahmed.dev00@gmail.com';
  const CAFE_WHATSAPP = cafeConfig.whatsapp || '201142520095';

  const nameInput = form.querySelector('#cf-name');
  const emailInput = form.querySelector('#cf-email');
  const messageInput = form.querySelector('#cf-message');
  const websiteInput = form.querySelector('#cf-website'); // honeypot
  const whatsappBtn = form.querySelector('#cf-whatsapp');
  const submitBtn = form.querySelector('button[type="submit"]');

  const rules = [
    [nameInput, (v) => v.trim().length > 1],
    [emailInput, (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)],
    [messageInput, (v) => v.trim().length > 4],
  ];

  function validate() {
    let valid = true;
    rules.forEach(([input, test]) => {
      const group = input.closest('.form-group');
      if (!test(input.value)) {
        group.classList.add('error');
        valid = false;
      } else {
        group.classList.remove('error');
      }
    });
    return valid;
  }

  function sendViaMailtoFallback() {
    const subject = encodeURIComponent(`New message from ${nameInput.value}`);
    const body = encodeURIComponent(`${messageInput.value}\n\n— ${nameInput.value} (${emailInput.value})`);
    window.open(`mailto:${CAFE_EMAIL}?subject=${subject}&body=${body}`, '_blank', 'noopener');
    showToast('No server connection — opening your email app instead.');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) {
      showToast('Please check the highlighted fields.', 'error');
      return;
    }

    if (submitBtn) submitBtn.disabled = true;
    try {
      const res = await fetch('php/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: nameInput.value.trim(),
          email: emailInput.value.trim(),
          message: messageInput.value.trim(),
          website: websiteInput ? websiteInput.value : '', // honeypot
        }),
      });
      const data = await res.json().catch(() => null);

      if (res.ok && data && data.ok) {
        showToast('Message sent — thank you! We\'ll get back to you soon.');
        form.reset();
      } else {
        showToast((data && data.error) || 'Could not send your message. Please try again.', 'error');
      }
    } catch (err) {
      // fetch itself failed — most likely there's no PHP server behind
      // this page at all (e.g. opened directly as a file).
      sendViaMailtoFallback();
      form.reset();
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  whatsappBtn?.addEventListener('click', () => {
    if (!validate()) {
      showToast('Please check the highlighted fields.', 'error');
      return;
    }
    const text = encodeURIComponent(
      `Hi Osama Café! I'm ${nameInput.value} (${emailInput.value}).\n${messageInput.value}`
    );
    window.open(`https://wa.me/${CAFE_WHATSAPP}?text=${text}`, '_blank', 'noopener');
  });
}

/* ------------------------------------------------------------------ *
 * Newsletter — POSTs to php/subscribe.php, which saves the email to
 * the database. Falls back to localStorage only if that request can't
 * be made at all (no PHP server behind the page).
 * ------------------------------------------------------------------ */
function initNewsletterForm() {
  const form = document.getElementById('newsletter-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = form.querySelector('input[type="email"]');

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
      showToast('Please enter a valid email address.', 'error');
      return;
    }

    try {
      const res = await fetch('php/subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: input.value.trim() }),
      });
      const data = await res.json().catch(() => null);

      if (res.ok && data && data.ok) {
        showToast('Thanks for subscribing! ☕');
        form.reset();
      } else {
        showToast((data && data.error) || 'Could not subscribe. Please try again.', 'error');
      }
      return;
    } catch (err) {
      // No PHP server behind this page — fall back to a local-only save
      // so the form still visibly works instead of doing nothing.
    }

    try {
      const subs = JSON.parse(localStorage.getItem('osamacafe_subscribers') || '[]');
      if (!subs.includes(input.value)) subs.push(input.value);
      localStorage.setItem('osamacafe_subscribers', JSON.stringify(subs));
    } catch (err) {
      /* localStorage unavailable — subscription still "succeeds" visually */
    }

    showToast('Thanks for subscribing! ☕');
    form.reset();
  });
}

/* ------------------------------------------------------------------ *
 * Toast notifications — small reusable helper used across the forms.
 * ------------------------------------------------------------------ */
function ensureToastContainer() {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  return container;
}

function showToast(message, type = 'success') {
  const container = ensureToastContainer();
  const toast = document.createElement('div');
  toast.className = `toast${type === 'error' ? ' toast-error' : ''}`;
  const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
  toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
  container.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add('show'));
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}
