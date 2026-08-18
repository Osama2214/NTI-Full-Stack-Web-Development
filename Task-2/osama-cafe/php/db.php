<?php
/**
 * Shared database connection.
 *
 * Defaults to SQLite — a single self-contained file, no MySQL server
 * required to run locally. Tables are created automatically on first
 * request.
 *
 * To run against MySQL instead (e.g. on shared hosting with no SSH/SQLite
 * support), add to config.php:
 *   define('DB_DRIVER', 'mysql');
 *   define('DB_HOST', 'sql123.example.com');
 *   define('DB_PORT', 3306);       // optional, defaults to 3306
 *   define('DB_NAME', 'your_db_name');
 *   define('DB_USER', 'your_db_user');
 *   define('DB_PASS', 'your_db_password');
 * Every query in this codebase already uses PDO + prepared statements, so
 * the handful of driver-specific bits (column types, upsert syntax) are
 * isolated to this file, db_now_minus_days(), and the two call sites that
 * use INSERT ... IGNORE / ON DUPLICATE KEY (subscribe.php, settings_admin.php).
 */

// Required here (not just by admin/contact endpoints) so DB_DRIVER/DB_* take
// effect no matter which entry point requires db.php first — index.php only
// ever required this file directly, so without this, its config.php was
// never actually loaded and the homepage would silently stay on SQLite even
// with DB_DRIVER=mysql set.
require_once __DIR__ . '/config.php';

/** Which DB backend is active: 'mysql' if configured, otherwise 'sqlite'. */
function db_driver(): string
{
    return (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'mysql' : 'sqlite';
}

function osama_cafe_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = db_driver();

    if ($driver === 'mysql') {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $port = defined('DB_PORT') ? DB_PORT : 3306;
        $name = DB_NAME;
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        $dbPath = __DIR__ . '/../data/osama_cafe.sqlite';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    // Driver-specific fragments used to build the CREATE TABLE statements below.
    $pk = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    // TIMESTAMP, not DATETIME: older/restrictive MySQL setups (common on free
    // hosts) reject "DATETIME ... DEFAULT CURRENT_TIMESTAMP" with error 1067
    // ("Invalid default value") — that dynamic default on DATETIME needs
    // MySQL 5.6.5+, while TIMESTAMP has supported it since MySQL 4.1.
    $now = $driver === 'mysql' ? 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' : "TEXT NOT NULL DEFAULT (datetime('now'))";
    $categoryRef = $driver === 'mysql' ? 'category_id INT NOT NULL' : 'category_id INTEGER NOT NULL REFERENCES categories(id)';
    $suffix = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id $pk,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at $now
        )$suffix
    ");

    // VARCHAR(191), not 255: older MySQL/InnoDB (pre-5.7, common on free
    // hosts) caps index keys at 767 bytes, and utf8mb4 uses up to 4 bytes/
    // char — 255*4 blows that limit on a UNIQUE column ("Specified key was
    // too long"), while 191*4 = 764 fits. Harmless for SQLite, which ignores
    // declared VARCHAR lengths entirely.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscribers (
            id $pk,
            email VARCHAR(191) NOT NULL UNIQUE,
            created_at $now
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id $pk,
            name TEXT NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            sort_order INTEGER NOT NULL DEFAULT 0
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id $pk,
            $categoryRef,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            image TEXT NOT NULL,
            link_label VARCHAR(100) NOT NULL DEFAULT 'Order Now',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at $now
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(100) PRIMARY KEY,
            value TEXT NOT NULL
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS branches (
            id $pk,
            name TEXT NOT NULL,
            address TEXT NOT NULL,
            phone TEXT NOT NULL,
            lat REAL,
            lng REAL,
            maps_url TEXT,
            is_primary INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0
        )$suffix
    ");
    ensure_branches_maps_url_column($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS faqs (
            id $pk,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS testimonials (
            id $pk,
            author_name TEXT NOT NULL,
            author_role TEXT NOT NULL,
            quote TEXT NOT NULL,
            rating REAL NOT NULL DEFAULT 5,
            sort_order INTEGER NOT NULL DEFAULT 0
        )$suffix
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gallery_items (
            id $pk,
            image TEXT NOT NULL,
            caption TEXT NOT NULL,
            alt_text TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
        )$suffix
    ");

    seed_menu_if_empty($pdo);
    seed_settings_if_empty($pdo);
    seed_branches_if_empty($pdo);
    seed_faqs_if_empty($pdo);
    seed_testimonials_if_empty($pdo);
    seed_gallery_if_empty($pdo);

    return $pdo;
}

/**
 * Cache-busting value for a static asset (its filesystem last-modified
 * time). Append as `?v=<?= asset_version('CSS/admin.css') ?>` on <link>/
 * <script> tags — otherwise the browser can keep serving an old cached copy
 * of the file indefinitely after an edit, with no visible sign anything is
 * wrong (no error, just stale behavior). $path is relative to the project
 * root, e.g. 'CSS/admin.css' or 'JS/admin.js', regardless of which page
 * (root-level index.php or a php/ page) is calling this.
 */
function asset_version(string $path): string
{
    $fsPath = __DIR__ . '/../' . ltrim($path, '/');
    return is_file($fsPath) ? (string)filemtime($fsPath) : '1';
}

/**
 * SQL fragment + bound value for "$column >= now minus $days days", written
 * against whichever DB backend is active. Use as:
 *   [$sql, $param] = db_now_minus_days('created_at', 7);
 *   $pdo->prepare("SELECT COUNT(*) FROM messages WHERE $sql")->execute($param);
 */
function db_now_minus_days(string $column, int $days): string
{
    if (db_driver() === 'mysql') {
        return "$column >= DATE_SUB(NOW(), INTERVAL $days DAY)";
    }
    return "$column >= datetime('now', '-$days days')";
}

/**
 * Migration for installs whose branches table predates the maps_url column
 * — a ready-made Google Maps link an admin can paste in, which takes
 * priority over lat/lng and the address text when building the "Open in
 * Google Maps" button. CREATE TABLE IF NOT EXISTS above only creates the
 * column on a brand-new database, so existing ones need this ALTER TABLE.
 */
function ensure_branches_maps_url_column(PDO $pdo): void
{
    if (db_driver() === 'mysql') {
        $columns = $pdo->query('SHOW COLUMNS FROM branches')->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        $columns = $pdo->query('PRAGMA table_info(branches)')->fetchAll(PDO::FETCH_COLUMN, 1);
    }
    if (!in_array('maps_url', $columns, true)) {
        $pdo->exec('ALTER TABLE branches ADD COLUMN maps_url TEXT');
    }
}

/** Reads all settings as an associative array, e.g. $settings['site_email']. */
function get_settings(PDO $pdo): array
{
    $rows = $pdo->query('SELECT `key`, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows;
}

/**
 * First-run only: seeds the two original categories and three menu items
 * so the site looks exactly the same the moment the tables are created.
 * Everything after that is fully editable from /php/admin-menu.php.
 */
function seed_menu_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $pdo->exec("
        INSERT INTO categories (name, slug, sort_order) VALUES
            ('Coffee', 'coffee', 1),
            ('Bakery', 'bakery', 2)
    ");

    $coffeeId = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'coffee'")->fetchColumn();
    $bakeryId = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'bakery'")->fetchColumn();

    $stmt = $pdo->prepare('
        INSERT INTO menu_items (category_id, title, description, price, image, link_label, sort_order)
        VALUES (:category_id, :title, :description, :price, :image, :link_label, :sort_order)
    ');

    $stmt->execute([
        'category_id' => $coffeeId,
        'title' => 'Espresso & Latte',
        'description' => 'Classic double espresso shots, velvety flat whites, and custom flavored lattes crafted on our state-of-the-art La Marzocco machine.',
        'price' => 4.50,
        'image' => 'espresso.jpg',
        'link_label' => 'Order Now',
        'sort_order' => 1,
    ]);
    $stmt->execute([
        'category_id' => $coffeeId,
        'title' => 'Slow Bar Brews',
        'description' => 'Indulge in clean V60, bold Chemex, or full-bodied AeroPress manual extractions designed to highlight origin-specific flavor profiles.',
        'price' => 5.20,
        'image' => 'slow_brew.jpg',
        'link_label' => 'Explore Blends',
        'sort_order' => 2,
    ]);
    $stmt->execute([
        'category_id' => $bakeryId,
        'title' => 'Fresh Pastries',
        'description' => 'Delicious butter croissants, organic sourdough bagels, and chocolate muffins baked fresh every morning to pair perfectly with your brew.',
        'price' => 3.75,
        'image' => 'pastries.jpg',
        'link_label' => 'View Menu',
        'sort_order' => 3,
    ]);
}

/**
 * First-run only: seeds the site-wide editable text so the page reads
 * exactly the same as before these were made editable. Change any of
 * these afterwards from /php/admin-settings.php.
 */
function seed_settings_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $defaults = [
        'site_email' => 'osamaahmed.dev00@gmail.com',
        'site_phone' => '01142520095',
        'whatsapp_number' => '201142520095',
        'hero_subtitle' => 'Welcome to',
        'hero_title' => 'Osama Café',
        'hero_description' => 'Experience the art of artisanal coffee brewing. We select the finest organic beans from sustainable farms and roast them locally for the perfect cup.',
        'about_title' => 'Our Story',
        'about_text' => 'We have been crafting premium coffee for over 15 years, working directly with small-scale coffee growers across Latin America, Africa, and Asia. Our mission is simple: to roast and brew with complete integrity, bringing out the unique flavor profile of every single origin bean.',
        'footer_about_text' => 'We are a community-driven specialty coffee shop and small-batch roastery dedicated to sourcing, roasting, and serving the highest grade coffee beans from sustainable origins.',
    ];

    $stmt = $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (:key, :value)');
    foreach ($defaults as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value]);
    }
}

/**
 * First-run only: seeds the original single location so nothing changes
 * visually until a second branch is added from /php/admin-settings.php.
 */
function seed_branches_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO branches (name, address, phone, lat, lng, is_primary, sort_order)
        VALUES (:name, :address, :phone, :lat, :lng, 1, 1)
    ');
    $stmt->execute([
        'name' => 'Osama Café',
        'address' => "El-Mahwar El-Markazi, Central Spine,\n6th of October City, Giza, Egypt",
        'phone' => '01142520095',
        'lat' => 29.973333613247092,
        'lng' => 30.946446768823897,
    ]);
}

/**
 * First-run only: seeds the original five FAQ entries so the site reads
 * exactly the same until edited from /php/admin-content.php.
 */
function seed_faqs_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $faqs = [
        ['What are your opening hours?', "We're open every day, 24/7 — whether it's an early sunrise espresso or a late-night pour-over, we've got you covered."],
        ['Where do your beans come from?', 'We work directly with small-scale, ethically certified growers across Latin America, Africa, and Asia, and roast every batch in-house for freshness.'],
        ['Do you offer dairy-free or gluten-free options?', "Yes — oat, soy, and almond milk are available for any drink, and select pastries are baked gluten-free daily. Ask your barista for today's options."],
        ['Can I book a table or a private event?', "Absolutely. Send us a message through the contact form below or reach out on WhatsApp, and we'll confirm your reservation the same day."],
        ['Do you sell whole roasted beans to take home?', "Every bean we brew is also sold by the bag, freshly roasted in small batches — just ask the counter for this week's origin selection."],
    ];

    $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order) VALUES (:question, :answer, :sort_order)');
    foreach ($faqs as $i => [$question, $answer]) {
        $stmt->execute(['question' => $question, 'answer' => $answer, 'sort_order' => $i + 1]);
    }
}

/**
 * First-run only: seeds the original four testimonials so the site reads
 * exactly the same until edited from /php/admin-content.php.
 */
function seed_testimonials_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $testimonials = [
        ['Sarah M.', 'Regular Guest', "Hands down the best V60 I've had in Cairo. The staff actually know the origin story behind every bag of beans they sell.", 5],
        ['Ahmed K.', 'Freelance Designer', "Cozy, warm, and the espresso is consistently perfect. It's become my unofficial second office.", 5],
        ['Laila R.', 'Food Blogger', "The pastries alone are worth the trip, but it's the sourcing story — ethical, small-batch, organic — that keeps me coming back.", 4.5],
        ['Youssef M.', 'Barista Enthusiast', 'Every visit feels intentional — the roast notes, the lighting, the playlist. A genuinely well-thought-out café.', 5],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO testimonials (author_name, author_role, quote, rating, sort_order)
        VALUES (:author_name, :author_role, :quote, :rating, :sort_order)
    ');
    foreach ($testimonials as $i => [$name, $role, $quote, $rating]) {
        $stmt->execute(['author_name' => $name, 'author_role' => $role, 'quote' => $quote, 'rating' => $rating, 'sort_order' => $i + 1]);
    }
}

/**
 * First-run only: seeds the original six gallery photos (already in
 * images/, never touched by this) so the site reads exactly the same until
 * edited from /php/admin-content.php.
 */
function seed_gallery_if_empty(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $items = [
        ['Hero.jpg', 'The Café', 'Osama Café storefront at golden hour'],
        ['about.jpg', 'Behind the Bar', 'Barista preparing coffee'],
        ['roast.jpg', 'The Roast', 'Coffee roasting process'],
        ['espresso.jpg', 'Espresso Art', 'Latte art coffee'],
        ['slow_brew.jpg', 'Slow Bar', 'Manual coffee pour over'],
        ['pastries.jpg', 'Fresh Bakes', 'Fresh pastries and croissant'],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO gallery_items (image, caption, alt_text, sort_order)
        VALUES (:image, :caption, :alt_text, :sort_order)
    ');
    foreach ($items as $i => [$image, $caption, $alt]) {
        $stmt->execute(['image' => $image, 'caption' => $caption, 'alt_text' => $alt, 'sort_order' => $i + 1]);
    }
}
