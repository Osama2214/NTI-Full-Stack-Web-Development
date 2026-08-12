# Osama Café ☕
### Premium Artisanal Coffee Roastery & Café — Landing Page + Backend

Welcome to the **Osama Café** project repository! This project started as **Task 2** for the **NTI Full Stack Developer using PHP** training program, and has since been extended independently into a full portfolio-ready build: a richer, animated front end plus a small real PHP backend (SQLite or MySQL) for the contact form and newsletter signup.

---

## 🌐 Live Preview & Developer Info
* **Live Demo:** [osama-cafe.xo.je](https://osama-cafe.xo.je/)
* **Developed By:** Osama Ahmed
* **GitHub Profile:** [@Osama2214](https://github.com/Osama2214)
* **Portfolio:** [Osama's Portfolio](https://osama-portfolio-six.vercel.app/)
* **Email:** [osamaahmed.dev00@gmail.com](mailto:osamaahmed.dev00@gmail.com)
* **Phone:** 01142520095

---

## ✨ Features

- **Cozy & Premium Aesthetics:** Dark, warm coffee-roastery theme — a single warm brown/amber hue family used throughout (backgrounds, accent, and text), verified for strong contrast rather than picked by eye.
- **Fully Responsive Layout:** Optimized for desktops, laptops, tablets, and mobile, using CSS Flexbox and Grid.
- **Real Preloader:** Tied to the actual `window.load` event (not a fixed timer), with a CSS-only safety net in case JavaScript never runs.
- **Scroll-reveal animations, animated stat counters, sticky/shrinking header, and scrollspy navigation** — all vanilla JS, no animation libraries.
- **Progressive enhancement throughout:** anything that depends on JavaScript (reveal animations, live stat counters) has a plain, fully-visible fallback if JS fails to load — the page never depends on JS just to be readable.
- **Rich Sections:** Hero, animated stats strip, Our Story, The Roastery, Photo Gallery (with a keyboard-navigable lightbox), a filterable Menu with pricing, a Testimonials carousel, an FAQ accordion, Contact (form + "Find Us" panel linking out to Google Maps), a Newsletter signup band, and a detailed footer.
- **Working Contact Form & Newsletter — real backend, not a mailto: trick.** See [Backend Setup](#-backend-setup-php--sqlite) below.
- **Fully automatic email, once configured:** a copy of every contact message and an auto-reply to the visitor, plus a one-click "send to all subscribers" newsletter broadcast (with ready-made templates) from the admin page — powered by PHPMailer + SMTP.
- **Fully editable menu — no code required.** Categories and menu items (title, description, price, photo, button label) are stored in the database and managed entirely from `/php/admin-menu.php`: add, edit, delete, upload a new photo, reorder. The public menu section and its filter buttons render straight from that data.
- **Fully editable site text & locations.** Contact email/phone/WhatsApp number, the hero and "Our Story" copy, and the footer blurb are all editable from `/php/admin-settings.php`. The same page supports **multiple branches** — one primary location (shown in the main "Find Us Here" panel) plus any number of additional branches (rendered as extra location cards further down the page automatically).
- **Fully editable Gallery, Testimonials & FAQ.** The homepage's photo grid, review carousel, and question accordion are all database-backed and managed from `/php/admin-content.php` — add, edit, delete, reorder, no code required.
- **SEO Best Practices:** Semantic HTML5 structure, correct heading hierarchy, metadata, and accessibility attributes.

---

## 🛠️ Technologies Used

* **PHP 8** — the page itself (`index.php`) renders the menu from the database server-side; everything else is semantic HTML5 markup.
* **Vanilla CSS3** — custom properties for theme consistency, CSS Grid/Flexbox, transition-based micro-animations.
* **Vanilla JavaScript** — no frameworks, no build step. All interactivity in [`JS/main.js`](JS/main.js).
* **PDO** — a small JSON API (`php/`) for the contact form, newsletter, and menu content, with a password-protected admin area. Runs on **SQLite** by default (zero setup, one self-contained file) or **MySQL** (switchable via config, for shared hosts with no SQLite/SSH support — this is what the live demo runs on).
* **PHPMailer** (via Composer) — real SMTP email sending, fully optional (see below).
* **Font Awesome v6** and **Google Fonts** for icons and typography.

---

## 📁 Project Structure

```bash
NTI-Task-2/
├── CSS/
│   ├── style.css             # Public site styles, theme variables, responsive media queries
│   └── admin.css              # Shared admin dashboard styles (nav, stat cards, tables, pagination)
├── JS/
│   ├── main.js                # All public-site interactivity (vanilla JS)
│   └── admin.js                # Admin dashboard interactivity (mobile nav toggle, copy emails)
├── php/
│   ├── db.php                 # PDO connection (SQLite by default, MySQL via config) + auto-creates tables + first-run menu seed
│   ├── helpers.php            # Shared JSON response / input helpers
│   ├── mailer.php              # PHPMailer/SMTP wrapper — fails soft if not configured
│   ├── contact.php            # POST endpoint: saves contact form messages, emails a copy + auto-reply
│   ├── subscribe.php          # POST endpoint: saves newsletter subscribers
│   ├── admin-common.php        # Shared admin helpers: session/auth, CSRF, login rate limiting, pagination, nav
│   ├── broadcast.php           # Admin-only: emails a subject/body to every subscriber
│   ├── menu_admin.php          # Admin-only: add/edit/delete categories & menu items, image upload
│   ├── settings_admin.php       # Admin-only: site text settings + branch (location) CRUD
│   ├── content_admin.php        # Admin-only: add/edit/delete FAQ, testimonials, gallery photos
│   ├── admin.php               # Login page only — redirects to admin-overview.php once authenticated
│   ├── admin-overview.php       # Password-protected: dashboard home — stats, recent activity, quick links
│   ├── admin-messages.php       # Password-protected: messages & subscribers, search + pagination, broadcast
│   ├── admin-menu.php           # Password-protected: manage menu categories & items, search/filter/pagination
│   ├── admin-content.php        # Password-protected: gallery photos, testimonials, FAQ
│   ├── admin-settings.php       # Password-protected: site text + branches/locations
│   ├── partials/                # Shared page-content fragments, reused for both full loads and AJAX
│   ├── config.example.php      # Template — copy to config.php and edit
│   └── config.php              # Your local settings (git-ignored, not committed)
├── vendor/                    # Composer dependencies (git-ignored — run `composer install`)
├── data/
│   ├── osama_cafe.sqlite       # Created automatically on first request (git-ignored)
│   └── login_attempts.json     # Admin login rate-limit tracking, created on first failed login (git-ignored)
├── images/
│   ├── menu/                   # Photos uploaded through the admin menu editor (git-ignored)
│   └── ...                    # Original site photography
├── composer.json / composer.lock
├── index.php                  # Main page — renders the menu section from the database
└── README.md
```

---

## 🚀 Running the Site

The homepage is now `index.php`, not a plain HTML file — it queries the menu from the database when it renders, so **it always needs to run through a PHP server**, even just to look at the design. Opening it by double-clicking no longer works. See [Backend Setup](#-backend-setup-php--sqlite) below to get it running (the quickest path is `php -S localhost:8000`).

---

## 🔧 Backend Setup (PHP + SQLite/MySQL)

To get the contact form and newsletter actually saving to a real database, the site needs to be served *through PHP*, not opened as a plain file.

**1. Install PHP dependencies (PHPMailer):**
```bash
composer install
```

**2. Copy the config template:**
```bash
cp php/config.example.php php/config.php
```
Open `php/config.php` and set your own `ADMIN_PASSWORD` (used to log into `/php/admin.php`).

**3. Serve the project with PHP.** Either:

- **Quick option — PHP's built-in server** (from the project folder):
  ```bash
  php -S localhost:8000
  ```
  Then open `http://localhost:8000/` (or `http://localhost:8000/index.php`).

- **Or with XAMPP:** place/move the project folder into `C:\xampp\htdocs\`, start **Apache** in the XAMPP Control Panel (MySQL is not needed — this uses SQLite), then open `http://localhost/NTI-Task-2/`.

**4. That's it for storage — no database server to install or configure.** `php/db.php` creates `data/osama_cafe.sqlite` and its tables automatically on the first request. Messages and subscribers are saved here **even if you skip the email setup below entirely.**

**Deploying to shared hosting with no SQLite/SSH support?** `php/db.php` also supports MySQL — every query in the codebase already goes through PDO + prepared statements, so switching backends is just config, no code changes. Add to `php/config.php`:
```php
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'sql123.example.com');
define('DB_PORT', 3306);       // optional, defaults to 3306
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```
Tables are created automatically on first request either way. The [live demo](https://osama-cafe.xo.je/) runs on this MySQL path.

**5. View submissions & reply:** go to `/php/admin.php` and log in with the `ADMIN_PASSWORD` from step 2 — you'll land on the **Overview** dashboard. From there you can reply to a message or email a subscriber with one click (opens your own email app, pre-filled) — no further setup needed for that.

---

### 🖥️ The Admin Dashboard

Logging in at `/php/admin.php` takes you to `/php/admin-overview.php`, the dashboard home — stat cards for messages, subscribers, menu items, categories, branches, and whether automatic email is on, plus a glance at the 5 most recent messages and subscribers. A sticky top nav (with a mobile-friendly collapsing menu) links out to the working pages:

- **`/php/admin-messages.php`** — Messages & Subscribers. Both lists have a search box (name/email/message, or subscriber email) with matches highlighted, and paginate 10 rows at a time. The one-click reply/email actions and the newsletter broadcast form (with templates) are unchanged.
- **`/php/admin-menu.php`** — Manage Menu. The item list has a title search and a category filter, both paginated.
- **`/php/admin-content.php`** — Site Content: the homepage's Gallery, Testimonials, and FAQ sections. See below.
- **`/php/admin-settings.php`** — Site Settings, unchanged in behavior.

**No full page reloads.** Every search, filter, pagination click, add/edit/delete, and settings save runs through `fetch()` and swaps in just the changed part of the page (see `JS/admin.js`) — the browser's Back/Forward buttons and bookmarking still work normally via `history.pushState`. Custom-styled `<select>` dropdowns and number-input steppers (the browser's native versions can't be reskinned consistently) re-apply automatically to whatever content just loaded. If JavaScript fails to load for any reason, every form still works as a plain POST-and-redirect — nothing depends on the AJAX layer to function.

Under the hood: every admin form submission is protected by a CSRF token, failed login attempts are rate-limited (5 tries, then a 5-minute lockout, tracked per IP in `data/login_attempts.json`), and the session ID is regenerated on login to prevent fixation. See `php/admin-common.php` for all of it in one place.

---

### 🖼️ Managing Gallery, Testimonials & FAQ (no code changes needed)

From the admin dashboard, click **"Site Content"** (or go straight to `/php/admin-content.php`) to:

- **Gallery photos** — add/edit/delete the photos in the homepage's "Our Gallery" grid, each with a caption (shown on hover) and alt text. Uploaded photos are saved under `images/gallery/`; deleting a photo removes its file too (the original site photography is never touched).
- **Testimonials** — add/edit/delete the reviews in the "What People Are Saying" carousel: guest name, role, quote, and a star rating (half-star steps). The avatar circle's initials are generated automatically from the name.
- **FAQ** — add/edit/delete the accordion entries in "Frequently Asked Questions": a question and its answer.

All three support an optional sort order (lower numbers show first). Changes appear on the live site immediately — no deploy or code edit required.

---

### 🧾 Managing the Menu (no code changes needed)

From the admin dashboard, click **"Manage Menu"** (or go straight to `/php/admin-menu.php`) to:

- **Add/rename/delete categories** — the public filter buttons (currently "All / Coffee / Bakery") are generated from these automatically.
- **Add a menu item** — title, description, price, category, button label, and a photo (JPG/PNG/WEBP, 5MB max). Uploaded photos are saved under `images/menu/`.
- **Edit or delete** any existing item — deleting removes its uploaded photo too (original seed photos in `images/` are never touched).
- **Search by title or filter by category** once the list gets long — both are paginated 10 items per page.
- A category can't be deleted while it still has items in it, to avoid orphaning them.

Changes appear on the live site immediately — no deploy or code edit required.

---

### 🏠 Managing Site Text & Branches (no code changes needed)

From the admin dashboard, click **"Site Settings"** (or go straight to `/php/admin-settings.php`) to:

- **Edit site-wide text:** contact email, phone, WhatsApp number, the Hero headline/subtitle/description, "Our Story" heading & text, and the footer's About blurb. These feed the actual page directly, plus the contact form's email/WhatsApp targets — no more hardcoded values in the source.
- **Manage branches (locations):** add as many as you like. Exactly one is always the **Primary** branch — its address/phone drive the main "Get in Touch" details and the "Find Us Here" map panel. Any additional branches automatically appear as extra location cards further down the Contact section, each with its own "Open in Google Maps" link.
- Deleting the primary branch (when others exist) automatically promotes the next one, so there's always exactly one. You can't delete your only remaining branch.

---

### ✉️ Optional: Fully-automatic email (PHPMailer + SMTP)

Enables: an automatic copy of every contact message to your inbox, an automatic "we got your message" reply to the visitor, and a **one-click "Send to All Subscribers"** newsletter button in the admin page — all sent by the server itself, no manual step.

1. Get a Gmail **App Password** (a normal Gmail password will not work): turn on 2-Step Verification on the Google account, then generate one at [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).
2. In `php/config.php`, set:
   ```php
   define('MAIL_ENABLED', true);
   define('SMTP_USERNAME', 'youraddress@gmail.com');
   define('SMTP_PASSWORD', 'the 16-character app password');
   ```
3. That's it — reload `/php/admin.php` and the "Send Newsletter Automatically" form appears once subscribers exist.

If this step is skipped, `MAIL_ENABLED` stays `false` and everything above (saving messages, viewing them, manual reply/BCC buttons) still works exactly the same — email sending is the only thing gated behind it.

**Security notes:** `php/config.php`, `data/*.sqlite`, `data/login_attempts.json`, and most of `vendor/` are all git-ignored — never commit real secrets, visitor data, or dependencies. Both `contact.php` and `subscribe.php` validate input server-side (not just in the browser) and use prepared statements; the contact form also has a honeypot field to quietly drop bot spam. Every admin-only endpoint (`broadcast.php`, `menu_admin.php`, `settings_admin.php`) requires a logged-in session **and** a matching CSRF token; the admin login itself is rate-limited (5 wrong attempts locks that IP out for 5 minutes) and regenerates the session ID on success.

The admin password is never stored in plaintext — `config.php` holds an `ADMIN_PASSWORD_HASH` (via `password_hash()`), checked with `password_verify()`, so even direct access to that file wouldn't reveal the real password. Under normal operation PHP is always executed server-side anyway, so `config.php`'s contents never reach the browser — but as defense in depth, `.htaccess` files under `php/`, `data/`, and `vendor/` explicitly block direct web access to `config.php`, stray editor backup files, the SQLite database, the rate-limit file, and the whole `vendor/` folder. (These only take effect under Apache, e.g. XAMPP — PHP's built-in `php -S` server ignores `.htaccess` entirely, so don't rely on it there.)
