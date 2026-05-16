<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/news_lib.php';

function site_content_sanitize_html(string $html): string {
    return news_sanitize_html($html);
}

function site_content_slugify(string $title): string {
    return news_slugify($title);
}

function site_content_ensure_unique_project_slug(PDO $pdo, string $base, ?int $exceptId): string {
    $slug = $base;
    $n = 2;
    while (true) {
        if ($exceptId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM projects WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $exceptId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM projects WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function site_content_init_schema(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        title TEXT NOT NULL,
        short_title TEXT NOT NULL,
        tagline TEXT NOT NULL DEFAULT \'\',
        icon TEXT NOT NULL DEFAULT \'fa-folder-open\',
        excerpt TEXT NOT NULL DEFAULT \'\',
        body_html TEXT NOT NULL DEFAULT \'\',
        featured_image_path TEXT,
        sort_order INTEGER NOT NULL DEFAULT 0,
        published INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_projects_published_sort ON projects(published, sort_order)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS project_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        image_path TEXT NOT NULL,
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_project_images_project ON project_images(project_id, sort_order)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS project_videos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        video_type TEXT NOT NULL DEFAULT \'file\',
        src_path TEXT,
        youtube_id TEXT,
        title TEXT NOT NULL DEFAULT \'\',
        sort_order INTEGER NOT NULL DEFAULT 0,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS hero_slides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        image_path TEXT NOT NULL,
        alt_text TEXT NOT NULL DEFAULT \'\',
        sort_order INTEGER NOT NULL DEFAULT 0,
        published INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hero_slides_published_sort ON hero_slides(published, sort_order)');

    $pdo->exec('CREATE TABLE IF NOT EXISTS public_pages (
        page_key TEXT PRIMARY KEY,
        label TEXT NOT NULL,
        href TEXT NOT NULL DEFAULT \'\',
        under_update INTEGER NOT NULL DEFAULT 0,
        notice TEXT NOT NULL DEFAULT \'\',
        updated_at TEXT NOT NULL
    )');
}

/** @return array<string, array<string, mixed>> */
function site_content_projects_seed_catalog(): array {
    return [
        'ahanta-language-renaissance' => [
            'slug' => 'ahanta-language-renaissance',
            'title' => 'Ahanta Language Renaissance Project',
            'short_title' => 'Ahanta Language Project',
            'tagline' => 'Reviving, teaching, and celebrating the Ahanta language for every generation.',
            'icon' => 'fa-language',
            'featured_image' => 'assets/projects/ahanta-language-renaissance/featured.jpg',
            'excerpt' => 'A community-led effort to document, teach, and promote the Ahanta language in schools, homes, and public life.',
            'body_html' => <<<'HTML'
<p>The Ahanta Language Renaissance Project works to keep our mother tongue alive and thriving. Through literacy materials, community classes, cultural storytelling, and partnerships with educators, the initiative helps young people and adults speak Ahanta with confidence.</p>
<h2>What we are building</h2>
<ul>
  <li>Teaching resources and learning materials for schools and community groups</li>
  <li>Documentation of vocabulary, proverbs, and oral traditions</li>
  <li>Events and media that celebrate Ahanta speech in everyday life</li>
  <li>Volunteer networks of teachers, elders, and youth ambassadors</li>
</ul>
HTML,
            'images' => [
                ['path' => 'assets/kuukuacares.jpg'],
                ['path' => 'assets/kbbmore.jpg'],
            ],
            'videos' => [],
            'sort_order' => 1,
        ],
        'ahanta-trust-fund' => [
            'slug' => 'ahanta-trust-fund',
            'title' => 'Ahanta Trust Fund',
            'short_title' => 'Ahanta Trust Fund',
            'tagline' => 'Pooling resources for education, welfare, and sustainable community investment.',
            'icon' => 'fa-hand-holding-heart',
            'featured_image' => 'assets/projects/ahanta-trust-fund/featured.jpg',
            'excerpt' => 'A dedicated fund supporting scholarships, small grants, and priority needs identified by Ahanta communities.',
            'body_html' => <<<'HTML'
<p>The Ahanta Trust Fund channels goodwill into practical impact. Contributions and partnerships help fund education support, emergency assistance, and locally agreed development priorities.</p>
<h2>Focus areas</h2>
<ul>
  <li>Scholarships and school support for deserving students</li>
  <li>Targeted welfare assistance for vulnerable households</li>
  <li>Transparent stewardship and reporting to donors and communities</li>
  <li>Partnerships that multiply local effort with outside support</li>
</ul>
HTML,
            'images' => [
                ['path' => 'assets/kuukua2.jpg'],
                ['path' => 'assets/honkuukua.jpg'],
            ],
            'videos' => [],
            'sort_order' => 2,
        ],
        'ahanta-heritage-months' => [
            'slug' => 'ahanta-heritage-months',
            'title' => 'Ahanta Heritage Months',
            'short_title' => 'Ahanta Heritage Months',
            'tagline' => 'A season of culture, history, arts, and pride across Ahanta West.',
            'icon' => 'fa-landmark',
            'featured_image' => 'assets/projects/ahanta-heritage-months/featured.jpg',
            'excerpt' => 'Annual heritage programming that showcases Ahanta traditions, creativity, and unity.',
            'body_html' => <<<'HTML'
<p>Ahanta Heritage Months bring communities together to honour history, dress, music, cuisine, and the values that define us. Parades, exhibitions, dialogues with elders, and youth-led performances make heritage visible and shared.</p>
<h2>Program highlights</h2>
<ul>
  <li>Cultural exhibitions and traditional arts showcases</li>
  <li>Heritage tours and storytelling with community historians</li>
  <li>Inter-generational forums on identity and progress</li>
  <li>Partnerships with schools, chiefs, and cultural groups</li>
</ul>
HTML,
            'images' => [
                ['path' => 'assets/mykbb.jpg'],
                ['path' => 'assets/kbbexe.jpg'],
            ],
            'videos' => [],
            'sort_order' => 3,
        ],
        'ahanta-sportyfest' => [
            'slug' => 'ahanta-sportyfest',
            'title' => 'Ahanta Sportyfest',
            'short_title' => 'Ahanta Sportyfest',
            'tagline' => 'Sports, fitness, and friendly competition that unite towns and youth.',
            'icon' => 'fa-futbol',
            'featured_image' => 'assets/projects/ahanta-sportyfest/featured.jpg',
            'excerpt' => 'A flagship sporting festival promoting health, talent discovery, and community spirit across Ahanta West.',
            'body_html' => <<<'HTML'
<p>Ahanta Sportyfest combines football, athletics, and recreational games with music, local enterprise, and family-friendly activities. It gives young athletes a platform and encourages healthy lifestyles community-wide.</p>
<h2>Festival features</h2>
<ul>
  <li>Inter-community tournaments and youth leagues</li>
  <li>Talent showcases and awards for outstanding performers</li>
  <li>Health and wellness outreach alongside competition</li>
  <li>Opportunities for local vendors and volunteers</li>
</ul>
HTML,
            'images' => [
                ['path' => 'assets/kuukuabissuesidepicture.jpg'],
                ['path' => 'assets/kuukuacares.jpg'],
            ],
            'videos' => [],
            'sort_order' => 4,
        ],
        'mobile-clinic' => [
            'slug' => 'mobile-clinic',
            'title' => 'Mobile Clinic',
            'short_title' => 'Mobile Clinic',
            'tagline' => 'Bringing essential healthcare closer to Ahanta communities.',
            'icon' => 'fa-truck-medical',
            'featured_image' => 'assets/projects/mobile-clinic/featured.jpg',
            'excerpt' => 'A roving clinic offering screenings, maternal care, chronic disease support, and referrals across Ahanta West.',
            'body_html' => <<<'HTML'
<p>The Mobile Clinic brings doctors, nurses, and community health workers to towns and villages that are far from fixed facilities. Services are scheduled with local leaders so families know when care is available near home.</p>
<h2>Services</h2>
<ul>
  <li>General consultations and vital-signs screening</li>
  <li>Maternal and child health outreach</li>
  <li>Chronic disease monitoring and medication counselling</li>
  <li>Health education and referrals to partner hospitals</li>
</ul>
HTML,
            'images' => [
                ['path' => 'assets/kuukuacares.jpg'],
                ['path' => 'assets/kbbmore.jpg'],
            ],
            'videos' => [],
            'sort_order' => 5,
        ],
    ];
}

/** @return array<string, array{label: string, href: string}> */
function public_pages_registry(): array {
    return [
        'home' => ['label' => 'Home', 'href' => 'index.php'],
        'about' => ['label' => 'About', 'href' => 'about.php'],
        'vision' => ['label' => 'Vision', 'href' => 'vision.php'],
        'projects' => ['label' => 'Projects', 'href' => 'projects.php'],
        'project_detail' => ['label' => 'Project detail', 'href' => 'project_detail.php'],
        'news' => ['label' => 'News', 'href' => 'news.php'],
        'news_post' => ['label' => 'News article', 'href' => 'news_post.php'],
        'membership' => ['label' => 'Membership registration', 'href' => 'register.php'],
        'contact' => ['label' => 'Contact', 'href' => 'contact.php'],
    ];
}

function site_content_insert_catalog_project(PDO $pdo, array $row): void {
    $now = date('c');
    $feat = (string) ($row['featured_image'] ?? '');
    if ($feat !== '' && !is_file(BASE_DIR . '/' . $feat)) {
        $feat = null;
    } else {
        $feat = $feat !== '' ? $feat : null;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO projects (slug, title, short_title, tagline, icon, excerpt, body_html, featured_image_path, sort_order, published, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,1,?,?)'
    );
    $stmt->execute([
        $row['slug'],
        $row['title'],
        $row['short_title'],
        $row['tagline'],
        $row['icon'],
        $row['excerpt'],
        $row['body_html'],
        $feat,
        (int) ($row['sort_order'] ?? 0),
        $now,
        $now,
    ]);
    $projectId = (int) $pdo->lastInsertId();
    $imgOrder = 0;
    foreach ($row['images'] ?? [] as $img) {
        $path = str_replace('\\', '/', (string) ($img['path'] ?? ''));
        if ($path === '' || !is_file(BASE_DIR . '/' . $path)) {
            continue;
        }
        $imgOrder++;
        $pdo->prepare('INSERT INTO project_images (project_id, image_path, sort_order, created_at) VALUES (?,?,?,?)')
            ->execute([$projectId, $path, $imgOrder, $now]);
    }
}

function site_content_seed_missing_projects(PDO $pdo): void {
    foreach (site_content_projects_seed_catalog() as $row) {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $stmt = $pdo->prepare('SELECT id FROM projects WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            site_content_insert_catalog_project($pdo, $row);
        }
    }
}

function site_content_seed_public_pages(PDO $pdo): void {
    $now = date('c');
    $stmt = $pdo->prepare(
        'INSERT OR IGNORE INTO public_pages (page_key, label, href, under_update, notice, updated_at) VALUES (?,?,?,0,?,?)'
    );
    foreach (public_pages_registry() as $key => $meta) {
        $stmt->execute([
            $key,
            $meta['label'],
            $meta['href'],
            'This page is being updated. Please check back soon.',
            $now,
        ]);
    }
}

/** @return list<array<string, mixed>> */
function public_pages_list_all(PDO $pdo): array {
    site_content_seed_public_pages($pdo);

    return $pdo->query('SELECT * FROM public_pages ORDER BY label ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function public_page_row(PDO $pdo, string $pageKey): ?array {
    site_content_seed_public_pages($pdo);
    $stmt = $pdo->prepare('SELECT * FROM public_pages WHERE page_key = ?');
    $stmt->execute([$pageKey]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function public_page_is_under_update(PDO $pdo, string $pageKey): bool {
    $row = public_page_row($pdo, $pageKey);

    return $row !== null && !empty($row['under_update']);
}

function public_page_notice_text(PDO $pdo, string $pageKey): string {
    $row = public_page_row($pdo, $pageKey);
    $notice = trim((string) ($row['notice'] ?? ''));
    if ($notice !== '') {
        return $notice;
    }

    return 'This page is being updated. Please check back soon.';
}

/** Coordinators/super admins may preview live content with ?preview=1 while a page is under update. */
function public_staff_previewing_page(): bool {
    return isset($_GET['preview'])
        && (string) $_GET['preview'] === '1'
        && function_exists('can_manage_site_content')
        && can_manage_site_content();
}

function public_staff_can_bypass_page_maintenance(): bool {
    return public_staff_previewing_page();
}

/** @return string URL for staff to preview a public page (includes sample slug where needed). */
function public_page_staff_preview_href(PDO $pdo, string $pageKey, string $href): string {
    if ($pageKey === 'project_detail') {
        $stmt = $pdo->query('SELECT slug FROM projects WHERE published = 1 ORDER BY sort_order ASC, id ASC LIMIT 1');
        $slug = $stmt ? $stmt->fetchColumn() : false;
        if (is_string($slug) && $slug !== '') {
            return 'project_detail.php?slug=' . rawurlencode($slug) . '&preview=1';
        }
    }
    if ($pageKey === 'news_post') {
        $stmt = $pdo->query('SELECT slug FROM news_posts WHERE published = 1 ORDER BY published_at DESC, id DESC LIMIT 1');
        $slug = $stmt ? $stmt->fetchColumn() : false;
        if (is_string($slug) && $slug !== '') {
            return 'news_post.php?slug=' . rawurlencode($slug) . '&preview=1';
        }
    }
    $sep = str_contains($href, '?') ? '&' : '?';

    return $href . $sep . 'preview=1';
}

function public_page_blocks_visitor(PDO $pdo, string $pageKey): bool {
    if (public_staff_can_bypass_page_maintenance()) {
        return false;
    }

    return public_page_is_under_update($pdo, $pageKey);
}

function public_page_set_under_update(PDO $pdo, string $pageKey, bool $underUpdate, ?string $notice = null): bool {
    if (!isset(public_pages_registry()[$pageKey])) {
        return false;
    }
    $now = date('c');
    if ($notice !== null) {
        $pdo->prepare('UPDATE public_pages SET under_update = ?, notice = ?, updated_at = ? WHERE page_key = ?')
            ->execute([$underUpdate ? 1 : 0, trim($notice), $now, $pageKey]);
    } else {
        $pdo->prepare('UPDATE public_pages SET under_update = ?, updated_at = ? WHERE page_key = ?')
            ->execute([$underUpdate ? 1 : 0, $now, $pageKey]);
    }

    return true;
}

function site_content_seed_if_empty(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    if ($count === 0) {
        foreach (site_content_projects_seed_catalog() as $row) {
            site_content_insert_catalog_project($pdo, $row);
        }
    }

    $slideCount = (int) $pdo->query('SELECT COUNT(*) FROM hero_slides')->fetchColumn();
    if ($slideCount === 0) {
        $defs = [
            ['assets/slideshow/slide-01-portrait.jpg', 'Hon. Mavis Kuukua Bissue — leadership for Ahanta West'],
            ['assets/slideshow/slide-02-community-outreach.jpg', 'Community outreach and engagement in Ahanta West'],
            ['assets/slideshow/slide-03-elders.jpg', 'Connecting with elders — care and respect'],
            ['assets/slideshow/slide-04-open-welcoming.jpg', 'Open and welcoming — connecting with constituents'],
            ['assets/slideshow/slide-05-kids-young-ones.jpg', 'Love for kids and young ones in Ahanta West'],
        ];
        $now = date('c');
        $n = 0;
        foreach ($defs as [$path, $alt]) {
            if (!is_file(BASE_DIR . '/' . $path)) {
                continue;
            }
            $n++;
            $pdo->prepare(
                'INSERT INTO hero_slides (image_path, alt_text, sort_order, published, created_at, updated_at) VALUES (?,?,?,1,?,?)'
            )->execute([$path, $alt, $n, $now, $now]);
        }
    }

    site_content_seed_public_pages($pdo);
}

function site_content_run_migrations(PDO $pdo): void {
    site_content_seed_missing_projects($pdo);
    site_content_seed_public_pages($pdo);
}

function site_content_public_path(string $rel): string {
    return str_replace('\\', '/', ltrim($rel, '/'));
}

function site_content_file_exists(string $rel): bool {
    $rel = site_content_public_path($rel);

    return $rel !== '' && is_file(BASE_DIR . '/' . $rel);
}

function site_content_delete_public_file(?string $relPath, array $allowedPrefixes): void {
    if ($relPath === null || $relPath === '') {
        return;
    }
    $relPath = site_content_public_path($relPath);
    $ok = false;
    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with($relPath, $prefix)) {
            $ok = true;
            break;
        }
    }
    if (!$ok) {
        return;
    }
    $full = BASE_DIR . '/' . $relPath;
    $realBase = realpath(BASE_DIR);
    $realFile = realpath($full);
    if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase) && is_file($realFile)) {
        @unlink($realFile);
    }
}

/**
 * @return array{0: string, 1: string}|null
 */
function site_content_image_upload_allowed(string $filename): ?array {
    $allowed = news_upload_allowed($filename, true);
    if ($allowed === null) {
        return null;
    }

    return $allowed;
}

function site_content_save_uploaded_image(array $file, string $destDirRel, string $basename = ''): ?string {
    if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        return null;
    }
    $name = (string) ($file['name'] ?? 'upload.jpg');
    $check = site_content_image_upload_allowed($name);
    if ($check === null || (int) ($file['size'] ?? 0) > 12 * 1024 * 1024) {
        return null;
    }
    [$ext] = $check;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $det = $finfo->file((string) $file['tmp_name']);
    $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if ($det === false || !in_array($det, $imageMimes, true)) {
        return null;
    }
    $destDirRel = site_content_public_path($destDirRel);
    $fsDir = BASE_DIR . '/' . $destDirRel;
    if (!is_dir($fsDir) && !@mkdir($fsDir, 0775, true)) {
        return null;
    }
    $base = $basename !== '' ? preg_replace('/[^a-z0-9_-]+/i', '-', $basename) : bin2hex(random_bytes(8));
    $base = trim((string) $base, '-') ?: bin2hex(random_bytes(8));
    $filename = $base . '.' . $ext;
    $destFs = $fsDir . '/' . $filename;
    $n = 2;
    while (is_file($destFs)) {
        $filename = $base . '-' . $n . '.' . $ext;
        $destFs = $fsDir . '/' . $filename;
        $n++;
    }
    if (!move_uploaded_file((string) $file['tmp_name'], $destFs)) {
        return null;
    }

    return $destDirRel . '/' . $filename;
}

/** @return list<array<string, mixed>> */
function projects_list_published(PDO $pdo): array {
    $rows = $pdo->query(
        'SELECT * FROM projects WHERE published = 1 ORDER BY sort_order ASC, title ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
        $out[] = site_content_project_row_to_public($pdo, $row);
    }

    return $out;
}

/** @return list<array<string, mixed>> */
function projects_list_all_admin(PDO $pdo): array {
    return $pdo->query('SELECT * FROM projects ORDER BY sort_order ASC, title ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function project_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function site_content_project_by_slug(PDO $pdo, string $slug, bool $publishedOnly = true): ?array {
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }
    if ($publishedOnly) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE slug = ? AND published = 1');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE slug = ?');
    }
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return site_content_project_row_to_public($pdo, $row);
}

/** @return array<string, mixed> */
function site_content_project_row_to_public(PDO $pdo, array $row): array {
    $id = (int) ($row['id'] ?? 0);
    $images = [];
    $stmt = $pdo->prepare('SELECT image_path FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $images[] = ['path' => (string) $img['image_path'], 'caption' => ''];
    }
    $videos = [];
    $vstmt = $pdo->prepare('SELECT * FROM project_videos WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $vstmt->execute([$id]);
    foreach ($vstmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $type = (string) ($v['video_type'] ?? 'file');
        if ($type === 'youtube') {
            $videos[] = [
                'type' => 'youtube',
                'youtube_id' => (string) ($v['youtube_id'] ?? ''),
                'title' => (string) ($v['title'] ?? 'Video'),
            ];
        } else {
            $videos[] = [
                'type' => 'file',
                'src' => (string) ($v['src_path'] ?? ''),
                'title' => (string) ($v['title'] ?? 'Video'),
            ];
        }
    }

    return [
        'id' => $id,
        'slug' => (string) ($row['slug'] ?? ''),
        'title' => (string) ($row['title'] ?? ''),
        'short_title' => (string) ($row['short_title'] ?? ''),
        'tagline' => (string) ($row['tagline'] ?? ''),
        'icon' => (string) ($row['icon'] ?? 'fa-folder-open'),
        'featured_image' => (string) ($row['featured_image_path'] ?? ''),
        'excerpt' => (string) ($row['excerpt'] ?? ''),
        'body_html' => (string) ($row['body_html'] ?? ''),
        'images' => $images,
        'videos' => $videos,
        'published' => (int) ($row['published'] ?? 0),
        'sort_order' => (int) ($row['sort_order'] ?? 0),
    ];
}

/** @return list<array{src: string, alt: string}> */
function hero_slides_list_public(PDO $pdo): array {
    $rows = $pdo->query(
        'SELECT image_path, alt_text FROM hero_slides WHERE published = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
        $path = site_content_public_path((string) ($row['image_path'] ?? ''));
        if ($path !== '' && site_content_file_exists($path)) {
            $out[] = ['src' => $path, 'alt' => (string) ($row['alt_text'] ?? '')];
        }
    }

    return $out;
}

/** @return list<array<string, mixed>> */
function hero_slides_list_all(PDO $pdo): array {
    return $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function hero_slide_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function hero_slide_delete(PDO $pdo, int $id): bool {
    $row = hero_slide_by_id($pdo, $id);
    if (!$row) {
        return false;
    }
    site_content_delete_public_file((string) ($row['image_path'] ?? ''), ['assets/slideshow/']);
    $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$id]);

    return true;
}

function hero_slide_reorder(PDO $pdo, int $id, string $direction): void {
    $rows = hero_slides_list_all($pdo);
    $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
    $pos = array_search($id, $ids, true);
    if ($pos === false) {
        return;
    }
    if ($direction === 'up' && $pos > 0) {
        [$ids[$pos - 1], $ids[$pos]] = [$ids[$pos], $ids[$pos - 1]];
    } elseif ($direction === 'down' && $pos < count($ids) - 1) {
        [$ids[$pos + 1], $ids[$pos]] = [$ids[$pos], $ids[$pos + 1]];
    } else {
        return;
    }
    $now = date('c');
    $upd = $pdo->prepare('UPDATE hero_slides SET sort_order = ?, updated_at = ? WHERE id = ?');
    foreach ($ids as $i => $slideId) {
        $upd->execute([$i + 1, $now, $slideId]);
    }
}

function project_image_delete(PDO $pdo, int $imageId): bool {
    $stmt = $pdo->prepare('SELECT * FROM project_images WHERE id = ?');
    $stmt->execute([$imageId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    site_content_delete_public_file((string) ($row['image_path'] ?? ''), ['assets/projects/']);
    $pdo->prepare('DELETE FROM project_images WHERE id = ?')->execute([$imageId]);

    return true;
}

function project_video_delete(PDO $pdo, int $videoId): bool {
    $stmt = $pdo->prepare('SELECT * FROM project_videos WHERE id = ?');
    $stmt->execute([$videoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    if ((string) ($row['video_type'] ?? '') === 'file') {
        site_content_delete_public_file((string) ($row['src_path'] ?? ''), ['assets/projects/', 'storage/projects/']);
    }
    $pdo->prepare('DELETE FROM project_videos WHERE id = ?')->execute([$videoId]);

    return true;
}
