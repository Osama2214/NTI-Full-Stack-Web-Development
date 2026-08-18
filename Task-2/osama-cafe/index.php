<?php
require_once __DIR__ . '/php/db.php';
$pdo = osama_cafe_db();
$menuCategories = $pdo->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
$menuItems = $pdo->query('
    SELECT menu_items.*, categories.slug AS category_slug
    FROM menu_items
    JOIN categories ON categories.id = menu_items.category_id
    ORDER BY menu_items.sort_order, menu_items.id
')->fetchAll(PDO::FETCH_ASSOC);
$settings = get_settings($pdo);
$branches = $pdo->query('SELECT * FROM branches ORDER BY is_primary DESC, sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$primaryBranch = $branches[0] ?? null;
$otherBranches = array_slice($branches, 1);
$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$faqs = $pdo->query('SELECT * FROM faqs ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$galleryItems = $pdo->query('SELECT * FROM gallery_items ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function setting(array $settings, string $key, string $fallback = ''): string
{
    return $settings[$key] !== '' && isset($settings[$key]) ? $settings[$key] : $fallback;
}
/** Initials for a testimonial's avatar circle, e.g. "Sarah M." -> "SM". */
function initials(string $name): string
{
    preg_match_all('/\b\p{L}/u', $name, $matches);
    return mb_strtoupper(implode('', array_slice($matches[0], 0, 2)));
}
/** Renders a rating (0–5, half-steps) as Font Awesome stars — full, half, then outline for the rest. */
function render_star_rating(float $rating): string
{
    $rating = max(0, min(5, $rating));
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('<i class="fa-solid fa-star"></i>', $full)
        . ($half ? '<i class="fa-solid fa-star-half-stroke"></i>' : '')
        . str_repeat('<i class="fa-regular fa-star"></i>', $empty);
}
/**
 * The "Open in Google Maps" URL for a branch: a saved maps_url link wins if
 * set, otherwise lat/lng, otherwise a text search on the address.
 */
function branch_maps_url(array $branch): string
{
    if (!empty($branch['maps_url'])) {
        return $branch['maps_url'];
    }
    $query = ($branch['lat'] !== null && $branch['lng'] !== null)
        ? $branch['lat'] . ',' . $branch['lng']
        : $branch['address'];
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($query);
}
?>
    <!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
      // Only hide .reveal content if JS actually confirms it can animate it
      // back in. If JS/main.js fails to load or run within 2s for any
      // reason, this class is removed and everything is just plain visible.
      document.documentElement.classList.add('js');
      setTimeout(function () {
        if (!window.__osamaCafeJsReady) document.documentElement.classList.remove('js');
      }, 2000);
    </script>
    <title>Osama Café - Premium Artisanal Coffee Roastery</title>

  <meta name="description" content="Welcome to Osama Café. Experience the finest ethically-sourced artisanal coffee roasted locally in a warm, cozy atmosphere. Explore our specialties.">
<meta name="keywords" content="Cafe, coffee shop, specialty coffee, artisanal roastery, espresso, cold brew, cafe menu">

  <link rel="icon" type="image/png" href="images/logo-1.png?v=<?= asset_version('images/logo-1.png') ?>">
  <link rel="apple-touch-icon" href="images/logo-1.png?v=<?= asset_version('images/logo-1.png') ?>">


    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>


<link rel="stylesheet" href="CSS/style.css?v=<?= asset_version('CSS/style.css') ?>">
      </head>
    <body>

  <input type="checkbox" id="menu-toggle" class="menu-toggle-checkbox" style="display: none;">


<div class="loader-wrapper" id="page-loader">
<div class="loader-coffee">
      <img src="images/logo-1.png" alt="Osama Café" class="loader-logo-img" decoding="async" fetchpriority="high">
    <div class="loader-text">Osama Café</div>
      </div>
    </div>


  <header class="site-header" id="site-header">
    <div class="nav-container">

    <a href="#home" class="logo" id="header-logo">
      <img src="images/logo-1.png" alt="Osama Café Logo" class="logo-img" decoding="async" fetchpriority="high">
    <span class="logo-text">Osama<span>Café</span></span>
    </a>
  <nav class="nav-menu" id="desktop-nav">
<a href="#home" class="nav-link active">Home</a>
  <a href="#about" class="nav-link">About Us</a>
<a href="#roast" class="nav-link">The Roastery</a>
  <a href="#gallery" class="nav-link">Gallery</a>
  <a href="#specialties" class="nav-link">Our Menu</a>
  <a href="#testimonials" class="nav-link">Reviews</a>
    <a href="#contact" class="nav-link">Visit Us</a>
  </nav>

  <label for="menu-toggle" class="hamburger" id="hamburger-btn" aria-label="Toggle Navigation Menu">
                <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
  </label>
    </div>
  </header>


    <nav class="mobile-nav-menu" id="mobile-nav">
    <label for="menu-toggle"><a href="#home" class="mobile-nav-link" id="mob-link-home">Home</a></label>
    <label for="menu-toggle"><a href="#about" class="mobile-nav-link" id="mob-link-about">About Us</a></label>
      <label for="menu-toggle"><a href="#roast" class="mobile-nav-link" id="mob-link-roast">The Roastery</a></label>
      <label for="menu-toggle"><a href="#gallery" class="mobile-nav-link" id="mob-link-gallery">Gallery</a></label>
      <label for="menu-toggle"><a href="#specialties" class="mobile-nav-link" id="mob-link-specs">Our Menu</a></label>
      <label for="menu-toggle"><a href="#testimonials" class="mobile-nav-link" id="mob-link-reviews">Reviews</a></label>
  <label for="menu-toggle"><a href="#contact" class="mobile-nav-link" id="mob-link-contact">Visit Us</a></label>
</nav>

  <section class="hero-section" id="home" style="background-image: url('images/Hero.jpg');">
        <div class="hero-overlay"></div>
      <div class="hero-content">
  <p class="hero-subtitle-top"><?= esc(setting($settings, 'hero_subtitle', 'Welcome to')) ?></p>
    <h1 class="hero-title"><?= esc(setting($settings, 'hero_title', 'Osama Café')) ?></h1>
  <p class="hero-description"><?= esc(setting($settings, 'hero_description')) ?></p>
            <div class="hero-btn-container">
    <a href="#specialties" class="btn btn-primary" id="hero-btn-explore">Explore Menu</a>
  <a href="#contact" class="btn btn-secondary" id="hero-btn-visit">Visit Us</a>
  </div>
    </div>
    <a href="#about" class="scroll-indicator" id="scroll-down-btn" aria-label="Scroll Down">
            <div class="mouse-icon">
  <div class="mouse-wheel"></div>
  </div>
        </a>
    </section>

    <section class="stats-section" id="stats">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-item reveal">
            <div class="stat-icon"><i class="fa-solid fa-mug-hot"></i></div>
            <div class="stat-number" data-target="15" data-decimals="0"><span class="stat-value">15</span><span class="stat-suffix">+</span></div>
            <div class="stat-label">Years Roasting</div>
          </div>
          <div class="stat-item reveal reveal-delay-1">
            <div class="stat-icon"><i class="fa-solid fa-seedling"></i></div>
            <div class="stat-number" data-target="100" data-decimals="0"><span class="stat-value">100</span><span class="stat-suffix">%</span></div>
            <div class="stat-label">Organic Beans</div>
          </div>
          <div class="stat-item reveal reveal-delay-2">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-number" data-target="50" data-decimals="0"><span class="stat-value">50</span><span class="stat-suffix">K+</span></div>
            <div class="stat-label">Cups Served Yearly</div>
          </div>
          <div class="stat-item reveal reveal-delay-3">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="stat-number" data-target="4.9" data-decimals="1"><span class="stat-value">4.9</span><span class="stat-suffix">/5</span></div>
            <div class="stat-label">Average Rating</div>
          </div>
        </div>
      </div>
    </section>

<section class="section section-dark" id="about">
    <div class="container">
      <div class="grid-2col">

<div class="about-image-wrapper reveal">
  <img src="images/about.jpg" alt="Barista preparing coffee" loading="lazy" decoding="async">
                </div>

                <div class="about-info reveal reveal-delay-1">
      <span class="section-tag">Who we are?</span>
<h2><?= esc(setting($settings, 'about_title', 'Our Story')) ?></h2>
    <p><?= esc(setting($settings, 'about_text')) ?></p>


  <ul class="icon-list">
    <li class="icon-list-item">
<div class="list-icon-box">
  <i class="fa-solid fa-leaf"></i>
    </div>
                            <span class="list-text">100% Ethically Sourced & Organic Beans</span>
      </li>
  <li class="icon-list-item">
    <div class="list-icon-box">
  <i class="fa-solid fa-fire"></i>
                            </div>
      <span class="list-text">Artisanal In-House Small Batch Roasting</span>
  </li>
    <li class="icon-list-item">
    <div class="list-icon-box">
  <i class="fa-solid fa-heart"></i>
      </div>
    <span class="list-text">Crafted with Passion by Award-Winning Baristas</span>
      </li>
    <li class="icon-list-item">
<div class="list-icon-box">
  <i class="fa-solid fa-seedling"></i>
</div>
                            <span class="list-text">Eco-Friendly & Zero-Waste Coffee Pods & Packaging</span>
      </li>
      </ul>
  </div>
</div>
      </div>
    </section>

<section class="split-section" id="roast">
      <div class="split-container">

<div class="split-content reveal">
    <span class="section-tag">The Roastery</span>
    <h2>The Perfect Roast</h2>
  <p>Every coffee bean has a story written by its soil, altitude, and weather. Our master roasters decode this story through precise heat application, bringing out fruit notes in light roasts, sweet caramels in medium roasts, and bold chocolatey depths in dark roasts.</p>
      <p>We roast daily in our vintage Diedrich roaster, monitoring temperature curves to ensure consistency, freshness, and optimal flavor in every cup we pour.</p>
  </div>
    <div class="split-image" style="background-image: url('images/roast.jpg');">
    <div class="split-image-overlay"></div>
  </div>
  </div>
    </section>

    <section class="section section-dark" id="gallery">
      <div class="container">
        <div class="section-header-center reveal">
          <span class="section-tag">A Peek Inside</span>
          <h3 class="section-title">Our Gallery</h3>
          <p class="section-subtitle">Moments from the roastery floor, the espresso bar, and everything in between. Click any photo for a closer look.</p>
        </div>
        <div class="gallery-grid">
          <?php if (!$galleryItems): ?>
            <p style="text-align:center; color: var(--text-muted); grid-column: 1 / -1;">Photos coming soon.</p>
          <?php endif; ?>
          <?php foreach ($galleryItems as $i => $photo):
            $delayClass = $i % 3 !== 0 ? ' reveal-delay-' . ($i % 3) : '';
          ?>
          <div class="gallery-item reveal<?= $delayClass ?>" data-caption="<?= esc($photo['alt_text']) ?>">
            <img src="images/<?= esc($photo['image']) ?>" alt="<?= esc($photo['alt_text']) ?>" loading="lazy" decoding="async">
            <div class="gallery-overlay"><span class="gallery-caption"><?= esc($photo['caption']) ?></span></div>
            <div class="gallery-zoom-icon"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section section-dark" id="specialties">
<div class="container">
  <div class="section-header-center reveal">
    <span class="section-tag">Our Crafts</span>
  <h3 class="section-title">Our Specialties</h3>
  <p class="section-subtitle">Discover our guest favorites. Handcrafted using premium espresso beans, manual pour-over techniques, and fresh locally-baked accompaniments.</p>
</div>

  <div class="menu-filter-bar reveal">
    <button class="menu-filter-btn active" data-filter="all">All</button>
    <?php foreach ($menuCategories as $cat): ?>
      <button class="menu-filter-btn" data-filter="<?= esc($cat['slug']) ?>"><?= esc($cat['name']) ?></button>
    <?php endforeach; ?>
  </div>

            <div class="grid-3col">
<?php if (!$menuItems): ?>
  <p style="text-align:center; color: var(--text-muted); grid-column: 1 / -1;">The menu is being updated — check back soon.</p>
<?php endif; ?>
<?php foreach ($menuItems as $i => $item):
  $delayClass = $i > 0 ? ' reveal-delay-' . min($i, 3) : '';
?>
<div class="specialty-card reveal<?= $delayClass ?>" data-category="<?= esc($item['category_slug']) ?>">
  <span class="price-tag">$<?= number_format((float)$item['price'], 2) ?></span>
  <div class="card-img-box">
    <img src="images/<?= esc($item['image']) ?>" alt="<?= esc($item['title']) ?>" loading="lazy" decoding="async">
  </div>
  <div class="card-content">
    <h4 class="card-title"><?= esc($item['title']) ?></h4>
    <p class="card-description"><?= esc($item['description']) ?></p>
    <a href="#contact" class="card-link"><?= esc($item['link_label']) ?></a>
  </div>
</div>
<?php endforeach; ?>
        </div>
    </section>

    <section class="section testimonials-section" id="testimonials">
      <div class="container">
        <div class="section-header-center reveal">
          <span class="section-tag">Guest Love</span>
          <h3 class="section-title">What People Are Saying</h3>
          <p class="section-subtitle">A few words from the regulars who make our mornings worthwhile.</p>
        </div>
        <div class="testimonial-slider reveal">
          <div class="testimonial-track">
            <div class="testimonial-slides">
              <?php if (!$testimonials): ?>
                <div class="testimonial-slide">
                  <div class="testimonial-card">
                    <p class="testimonial-quote">Reviews coming soon.</p>
                  </div>
                </div>
              <?php endif; ?>
              <?php foreach ($testimonials as $t): ?>
              <div class="testimonial-slide">
                <div class="testimonial-card">
                  <div class="testimonial-stars"><?= render_star_rating((float)$t['rating']) ?></div>
                  <p class="testimonial-quote">"<?= esc($t['quote']) ?>"</p>
                  <div class="testimonial-author">
                    <div class="testimonial-avatar"><?= esc(initials($t['author_name'])) ?></div>
                    <div class="testimonial-meta">
                      <div class="testimonial-name"><?= esc($t['author_name']) ?></div>
                      <div class="testimonial-role"><?= esc($t['author_role']) ?></div>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="testimonial-controls">
            <button class="testimonial-arrow prev" aria-label="Previous review"><i class="fa-solid fa-arrow-left"></i></button>
            <div class="testimonial-dots"></div>
            <button class="testimonial-arrow next" aria-label="Next review"><i class="fa-solid fa-arrow-right"></i></button>
          </div>
        </div>
      </div>
    </section>

    <section class="section section-dark" id="faq">
      <div class="container">
        <div class="section-header-center reveal">
          <span class="section-tag">Good to Know</span>
          <h3 class="section-title">Frequently Asked Questions</h3>
          <p class="section-subtitle">Everything guests usually ask us before their first visit.</p>
        </div>
        <div class="faq-list reveal">
          <?php if (!$faqs): ?>
            <p style="text-align:center; color: var(--text-muted);">More answers coming soon.</p>
          <?php endif; ?>
          <?php foreach ($faqs as $f): ?>
          <div class="faq-item">
            <div class="faq-question">
              <h4><?= esc($f['question']) ?></h4>
              <div class="faq-icon"><i class="fa-solid fa-plus"></i></div>
            </div>
            <div class="faq-answer"><p><?= esc($f['answer']) ?></p></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section split-section contact-section" id="contact">
  <div class="contact-grid">

<div class="contact-info-panel">
  <span class="section-tag">Get in Touch</span>
                <h2>Visit Our Cafe</h2>
  <p>Step in to enjoy freshly brewed coffee, buy fresh roasted beans, or talk coffee chemistry with our friendly baristas.</p>


  <div class="contact-details-list">
                    <div class="contact-detail-item">
                        <div class="contact-icon-box">
    <i class="fa-solid fa-map-location-dot"></i>
    </div>
  <div class="detail-content">
  <h4>Address</h4>
                            <p><?= $primaryBranch ? nl2br(esc($primaryBranch['address'])) : '' ?></p>
    </div>
    </div>
    <div class="contact-detail-item">
<div class="contact-icon-box">
    <i class="fa-solid fa-phone"></i>
    </div>
      <div class="detail-content">
  <h4>Phone</h4>
                            <p><a href="tel:<?= esc(setting($settings, 'site_phone')) ?>" target="_blank" rel="noopener"><?= esc(setting($settings, 'site_phone')) ?></a></p>
</div>
  </div>
    <div class="contact-detail-item">
    <div class="contact-icon-box">
  <i class="fa-solid fa-envelope"></i>
</div>
                        <div class="detail-content">
  <h4>Email</h4>
    <p><a href="mailto:<?= esc(setting($settings, 'site_email')) ?>" target="_blank" rel="noopener"><?= esc(setting($settings, 'site_email')) ?></a></p>
    </div>
  </div>
    </div>

    <form class="contact-form" id="contact-form" novalidate>
      <h3>Send Us a Message</h3>
      <input type="text" name="website" id="cf-website" class="cf-honeypot" autocomplete="off" tabindex="-1" aria-hidden="true">
      <div class="form-group">
        <input type="text" class="form-input" id="cf-name" name="name" placeholder="Your Name" autocomplete="name" required>
        <span class="form-error">Please enter your name.</span>
      </div>
      <div class="form-group">
        <input type="email" class="form-input" id="cf-email" name="email" placeholder="Your Email" autocomplete="email" required>
        <span class="form-error">Please enter a valid email address.</span>
      </div>
      <div class="form-group">
        <textarea class="form-textarea" id="cf-message" name="message" placeholder="How can we help?" required></textarea>
        <span class="form-error">Please write a short message.</span>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Send Message</button>
        <button type="button" class="btn btn-whatsapp" id="cf-whatsapp"><i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp</button>
      </div>
    </form>
    </div>

  <div class="map-container" style="background-image: url('images/roast.jpg');">
    <div class="location-overlay"></div>
    <div class="location-card">
      <div class="location-pin"><i class="fa-solid fa-location-dot"></i></div>
      <h3>Find Us Here</h3>
      <?php if ($primaryBranch): ?>
        <p><?= nl2br(esc($primaryBranch['address'])) ?></p>
        <a href="<?= esc(branch_maps_url($primaryBranch)) ?>" target="_blank" rel="noopener" class="btn btn-primary location-btn">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Google Maps
        </a>
      <?php endif; ?>
    </div>
  </div>
  </div>

  <?php if ($otherBranches): ?>
  <div class="container other-branches">
    <h3 class="section-title" style="text-align:center; margin: 60px 0 30px;">Our Other Locations</h3>
    <div class="branch-grid">
      <?php foreach ($otherBranches as $branch): ?>
        <div class="branch-card reveal">
          <h4><?= esc($branch['name']) ?></h4>
          <p><?= nl2br(esc($branch['address'])) ?></p>
          <p><a href="tel:<?= esc($branch['phone']) ?>" target="_blank" rel="noopener"><?= esc($branch['phone']) ?></a></p>
          <a href="<?= esc(branch_maps_url($branch)) ?>" target="_blank" rel="noopener" class="card-link">Open in Google Maps</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
    </section>

    <section class="newsletter-section">
      <div class="container">
        <div class="newsletter-inner reveal">
          <div class="newsletter-text">
            <h3>Stay in the Loop</h3>
            <p>New origins, seasonal drinks, and roastery news — straight to your inbox, no spam.</p>
          </div>
          <form class="newsletter-form" id="newsletter-form" novalidate>
            <input type="email" placeholder="Enter your email" aria-label="Email address" required>
            <button type="submit">Subscribe</button>
          </form>
        </div>
      </div>
    </section>

      <footer class="site-footer">
<div class="footer-top">
    <div class="container">
                <div class="footer-grid">

<div class="footer-widget">
  <h4>About Osama Café</h4>
  <p><?= esc(setting($settings, 'footer_about_text')) ?></p>
                    </div>


  <div class="footer-widget">
  <h4>Our Values</h4>
      <ul class="footer-list">
    <li class="footer-list-item"><i class="fa-solid fa-chevron-right"></i> Direct Fair Trade</li>
    <li class="footer-list-item"><i class="fa-solid fa-chevron-right"></i> Sustainable Farming</li>
                            <li class="footer-list-item"><i class="fa-solid fa-chevron-right"></i> Zero Waste Strategy</li>
    <li class="footer-list-item"><i class="fa-solid fa-chevron-right"></i> Barista Education</li>
<li class="footer-list-item"><i class="fa-solid fa-chevron-right"></i> Community Support</li>
      </ul>
  </div>


<div class="footer-widget">
<h4>Opening Hours</h4>
<ul class="footer-hours-list">
    <li><span>Monday - Sunday</span> <span>Open 24/7</span></li>
  </ul>
    </div>

    <div class="footer-widget">
  <h4>Contact Details</h4>
    <ul class="footer-contact-details">
                            <li><i class="fa-solid fa-location-dot"></i> <span><?= $primaryBranch ? esc(str_replace("\n", ' ', $primaryBranch['address'])) : '' ?></span></li>
      <li><i class="fa-solid fa-phone"></i> <span><a href="tel:<?= esc(setting($settings, 'site_phone')) ?>" target="_blank" rel="noopener"><?= esc(setting($settings, 'site_phone')) ?></a></span></li>
<li><i class="fa-solid fa-envelope"></i> <span><a href="mailto:<?= esc(setting($settings, 'site_email')) ?>" target="_blank" rel="noopener"><?= esc(setting($settings, 'site_email')) ?></a></span></li>
  <li><i class="fa-solid fa-globe"></i> <span><a href="https://osama-portfolio-six.vercel.app/" target="_blank">Osama's Portfolio</a></span></li>
  </ul>
</div>
      </div>
    </div>
  </div>


  <div class="footer-bottom">
<div class="container">
<div class="footer-bottom-content">
                    <div class="footer-logo">
                        <img src="images/logo-1.png" alt="Osama Café Logo" class="logo-img footer-logo-img" decoding="async">
    <span class="logo-text">Osama<span>Café</span></span>
      </div>
    <div class="footer-social-icons">
<a href="https://github.com/Osama2214" target="_blank" class="social-icon-btn" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
<a href="https://osama-portfolio-six.vercel.app/" target="_blank" class="social-icon-btn" aria-label="Portfolio"><i class="fa-solid fa-globe"></i></a>
  <a href="mailto:<?= esc(setting($settings, 'site_email')) ?>" target="_blank" rel="noopener" class="social-icon-btn" aria-label="Email"><i class="fa-solid fa-envelope"></i></a>
                    </div>
      <p class="copyright">&copy; 2026 Osama Café. All rights reserved. Developed by <a href="https://osama-portfolio-six.vercel.app/" target="_blank" style="color: var(--accent-color); font-weight: 500;">Osama Ahmed</a>.</p>
      </div>
            </div>
  </div>
  </footer>
    <a href="#home" class="scrollup" id="scroll-up-btn" aria-label="Scroll Back to Top">
      <i class="fa-solid fa-angle-up"></i>
    </a>

    <script>
      // Read by JS/main.js for the contact form's email/WhatsApp targets —
      // sourced from the database (editable in /php/admin-settings.php)
      // instead of being hardcoded in the script file.
      window.OSAMA_CAFE_CONFIG = {
        email: <?= json_encode(setting($settings, 'site_email')) ?>,
        whatsapp: <?= json_encode(setting($settings, 'whatsapp_number')) ?>
      };
    </script>
    <script src="JS/main.js?v=<?= asset_version('JS/main.js') ?>" defer></script>
  </body>
</html>