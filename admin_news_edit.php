<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_news_management();
require_once __DIR__ . '/news_lib.php';

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = null;
if ($id > 0) {
    $post = news_post_by_id($pdo, $id);
    if (!$post) {
        flash('admin_notice', 'Post not found.');
        redirect('admin_news.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? 'General')) ?: 'General';
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $bodyRaw = (string) ($_POST['body_html'] ?? '');
    $body = news_sanitize_html($bodyRaw);
    $published = isset($_POST['published']) ? 1 : 0;
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $removeFeatured = isset($_POST['remove_featured']);

    if ($title === '') {
        flash('admin_notice', 'Title is required.');
        redirect($id > 0 ? 'admin_news_edit.php?id=' . $id : 'admin_news_edit.php');
    }

    $baseSlug = $slugInput !== '' ? news_slugify($slugInput) : news_slugify($title);
    $slug = news_ensure_unique_slug($pdo, $baseSlug, $id > 0 ? $id : null);

    $now = date('c');
    $actorId = (int) ($_SESSION['admin_id'] ?? 0);

    $featuredPath = $post ? ($post['featured_image_path'] ?? null) : null;
    if (!is_string($featuredPath) || $featuredPath === '') {
        $featuredPath = null;
    }

    if ($removeFeatured) {
        if ($featuredPath !== null) {
            news_delete_file_if_in_news_dir($featuredPath);
        }
        $featuredPath = null;
    }

    if (!empty($_FILES['featured_image']['tmp_name']) && is_uploaded_file((string) $_FILES['featured_image']['tmp_name'])) {
        $fi = $_FILES['featured_image'];
        $allowed = news_upload_allowed((string) ($fi['name'] ?? ''), true);
        if ($allowed === null || (int) ($fi['size'] ?? 0) > 12 * 1024 * 1024) {
            flash('admin_notice', 'Featured image must be JPG, PNG, WebP, or GIF under 12MB.');
            redirect($id > 0 ? 'admin_news_edit.php?id=' . $id : 'admin_news_edit.php');
        }
        [$ext, $expectedMime] = $allowed;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $det = $finfo->file((string) $fi['tmp_name']);
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($det === false || !in_array($det, $imageMimes, true)) {
            flash('admin_notice', 'Featured file must be a valid image.');
            redirect($id > 0 ? 'admin_news_edit.php?id=' . $id : 'admin_news_edit.php');
        }
        if ($featuredPath !== null) {
            news_delete_file_if_in_news_dir($featuredPath);
        }
        $base = bin2hex(random_bytes(12)) . '.' . $ext;
        $destFs = NEWS_DIR . '/featured/' . $base;
        $featuredPath = 'storage/news/featured/' . $base;
        if (!move_uploaded_file((string) $fi['tmp_name'], $destFs)) {
            flash('admin_notice', 'Failed to save featured image.');
            redirect($id > 0 ? 'admin_news_edit.php?id=' . $id : 'admin_news_edit.php');
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE news_posts SET slug=?, title=?, excerpt=?, body_html=?, featured_image_path=?, category=?, published=?, updated_at=? WHERE id=?');
        $stmt->execute([$slug, $title, $excerpt, $body, $featuredPath, $category, $published, $now, $id]);
        log_admin_action($pdo, 'news_post_update', 'news_post', $id, ['title' => $title]);
        flash('admin_notice', 'Post updated.');
        redirect('admin_news_edit.php?id=' . $id);
    }

    $stmt = $pdo->prepare('INSERT INTO news_posts (slug, title, excerpt, body_html, featured_image_path, category, published, author_admin_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$slug, $title, $excerpt, $body, $featuredPath, $category, $published, $actorId, $now, $now]);
    $newId = (int) $pdo->lastInsertId();
    log_admin_action($pdo, 'news_post_create', 'news_post', $newId, ['title' => $title]);
    flash('admin_notice', 'Post created.');
    redirect('admin_news_edit.php?id=' . $newId);
}

$notice = flash('admin_notice');
$pageTitle = $post ? 'Edit news post' : 'New news post';
$p = $post ?? [];

$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', (string) $scriptDir);
$pathPrefix = ($scriptDir === '/' || $scriptDir === '') ? '/' : rtrim($scriptDir, '/') . '/';
$documentBase = $proto . '://' . $host . $pathPrefix;

render_layout_start($pageTitle, 'news_admin');
?>
<div class="w-full max-w-5xl">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <a href="admin_news.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900"><i class="fa-solid fa-arrow-left"></i> All posts</a>
    <?php if ($post && !empty($post['published']) && !empty($post['slug'])): ?>
      <a href="news_post.php?slug=<?= h(urlencode((string) $post['slug'])) ?>" target="_blank" rel="noopener" class="text-sm font-semibold text-emerald-700 hover:underline">View on site</a>
    <?php endif; ?>
  </div>

  <h1 class="text-2xl font-black text-slate-900 mt-4"><?= h($pageTitle) ?></h1>
  <p class="text-sm text-slate-500 mt-1">Use the editor for rich text. Upload images from the toolbar; use <strong>Media</strong> or the file picker to add video or audio files.</p>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 text-amber-950 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="mt-8 space-y-6 bg-white border border-slate-200 p-6 md:p-8">
    <label class="block">
      <span class="text-sm font-bold text-slate-700">Title</span>
      <input name="title" required class="mt-1 w-full border border-slate-300 px-3 py-2.5 text-slate-900" value="<?= h((string) ($p['title'] ?? '')) ?>">
    </label>

    <div class="grid md:grid-cols-2 gap-6">
      <label class="block">
        <span class="text-sm font-bold text-slate-700">Category</span>
        <input name="category" class="mt-1 w-full border border-slate-300 px-3 py-2.5 text-slate-900" value="<?= h((string) ($p['category'] ?? 'General')) ?>" placeholder="Community, Youth, Office…">
      </label>
      <label class="block">
        <span class="text-sm font-bold text-slate-700">URL slug <span class="font-normal text-slate-500">(optional)</span></span>
        <input name="slug" class="mt-1 w-full border border-slate-300 px-3 py-2.5 text-slate-900 font-mono text-sm" value="<?= h((string) ($p['slug'] ?? '')) ?>" placeholder="auto from title">
      </label>
    </div>

    <label class="block">
      <span class="text-sm font-bold text-slate-700">Excerpt <span class="font-normal text-slate-500">(short summary for listings)</span></span>
      <textarea name="excerpt" rows="3" class="mt-1 w-full border border-slate-300 px-3 py-2.5 text-slate-900"><?= h((string) ($p['excerpt'] ?? '')) ?></textarea>
    </label>

    <div>
      <span class="text-sm font-bold text-slate-700">Featured image</span>
      <p class="text-xs text-slate-500 mt-1">Shown on the news grid and at the top of the article. Separate from images inside the story.</p>
      <?php
        $feat = $p['featured_image_path'] ?? '';
      if (is_string($feat) && $feat !== ''): ?>
        <div class="mt-3 flex flex-wrap items-center gap-4">
          <img src="<?= h($feat) ?>" alt="" class="h-28 w-auto rounded border border-slate-200 object-cover">
          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remove_featured" value="1"> Remove featured image
          </label>
        </div>
      <?php endif; ?>
      <input type="file" name="featured_image" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-3 block w-full text-sm text-slate-600">
    </div>

    <div>
      <span class="text-sm font-bold text-slate-700">Article body</span>
      <textarea id="news-body" name="body_html" rows="16" class="mt-1 w-full border border-slate-300 px-3 py-2.5 text-slate-900 font-mono text-sm"><?= h((string) ($p['body_html'] ?? '')) ?></textarea>
    </div>

    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
      <input type="checkbox" name="published" value="1" <?= !empty($p['published']) ? 'checked' : '' ?>>
      Published (visible on public news)
    </label>

    <div class="flex flex-wrap gap-3 pt-2">
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold">
        <i class="fa-solid fa-floppy-disk"></i> Save post
      </button>
      <a href="admin_news.php" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-800 font-semibold hover:bg-slate-50">Cancel</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
<script>
(function () {
  var base = <?= json_encode($documentBase, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  tinymce.init({
    selector: '#news-body',
    height: 520,
    menubar: false,
    license_key: 'gpl',
    base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7',
    suffix: '.min',
    plugins: 'link lists image media autoresize code fullscreen',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link image media | removeformat | code fullscreen',
    branding: false,
    promotion: false,
    relative_urls: false,
    document_base_url: base,
    content_style: 'body { font-family: system-ui,-apple-system,sans-serif; font-size:17px; line-height:1.6; color:#1e293b; } img, video, audio { max-width:100%; height:auto; } video { border-radius:12px; background:#0f172a; }',
    images_upload_handler: function (blobInfo) {
      return new Promise(function (resolve, reject) {
        var fd = new FormData();
        fd.append('file', blobInfo.blob(), blobInfo.filename());
        fetch('news_upload.php?images_only=1', { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (j.location) resolve(j.location);
            else reject(j.error || 'Upload failed');
          })
          .catch(function () { reject('Upload failed'); });
      });
    },
    file_picker_callback: function (cb, value, meta) {
      var input = document.createElement('input');
      input.type = 'file';
      if (meta.filetype === 'image') {
        input.accept = 'image/*';
      } else {
        input.accept = 'video/*,audio/*,.mp4,.webm,.mp3,.wav,.ogg,.m4a';
      }
      input.onchange = function () {
        var f = input.files && input.files[0];
        if (!f) return;
        var fd = new FormData();
        fd.append('file', f);
        var q = meta.filetype === 'image' ? '?images_only=1' : '';
        fetch('news_upload.php' + q, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (j.location) cb(j.location, { title: f.name });
            else alert(j.error || 'Upload failed');
          })
          .catch(function () { alert('Upload failed'); });
      };
      input.click();
    }
  });
})();
</script>
<?php render_layout_end(); ?>
