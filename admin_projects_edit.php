<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_admin();
require_site_content_management();
require_once __DIR__ . '/site_content_lib.php';

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$projectRow = null;
if ($id > 0) {
    $projectRow = project_by_id($pdo, $id);
    if (!$projectRow) {
        flash('admin_notice', 'Project not found.');
        redirect('admin_projects.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete_project' && $id > 0) {
        $row = project_by_id($pdo, $id);
        if ($row) {
            $imgs = $pdo->prepare('SELECT image_path FROM project_images WHERE project_id = ?');
            $imgs->execute([$id]);
            foreach ($imgs->fetchAll(PDO::FETCH_ASSOC) as $im) {
                site_content_delete_public_file((string) $im['image_path'], ['assets/projects/']);
            }
            site_content_delete_public_file((string) ($row['featured_image_path'] ?? ''), ['assets/projects/']);
            $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            log_admin_action($pdo, 'project_delete', 'project', $id, ['slug' => $row['slug'] ?? '']);
            flash('admin_notice', 'Project deleted.');
        }
        redirect('admin_projects.php');
    }

    if ($action === 'delete_image') {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        if ($imageId > 0) {
            project_image_delete($pdo, $imageId);
            flash('admin_notice', 'Gallery image removed.');
        }
        redirect('admin_projects_edit.php?id=' . $id);
    }

    if ($action === 'delete_video') {
        $videoId = (int) ($_POST['video_id'] ?? 0);
        if ($videoId > 0) {
            project_video_delete($pdo, $videoId);
            flash('admin_notice', 'Video removed.');
        }
        redirect('admin_projects_edit.php?id=' . $id);
    }

    if ($action === 'add_video' && $id > 0) {
        $vTitle = trim((string) ($_POST['video_title'] ?? 'Video'));
        $vType = (string) ($_POST['video_type'] ?? 'youtube');
        $now = date('c');
        if ($vType === 'youtube') {
            $yt = trim((string) ($_POST['youtube_id'] ?? ''));
            if ($yt !== '') {
                $pdo->prepare('INSERT INTO project_videos (project_id, video_type, youtube_id, title, sort_order) VALUES (?,?,?,?,?)')
                    ->execute([$id, 'youtube', $yt, $vTitle, 999]);
                flash('admin_notice', 'YouTube video added.');
            }
        } elseif (!empty($_FILES['video_file']['tmp_name'])) {
            $slug = (string) ($projectRow['slug'] ?? 'project');
            $path = site_content_save_uploaded_image($_FILES['video_file'], 'assets/projects/' . $slug);
            if ($path === null) {
                $allowed = news_upload_allowed((string) ($_FILES['video_file']['name'] ?? ''), false);
                if ($allowed !== null) {
                    $slugDir = 'assets/projects/' . $slug;
                    $fsDir = BASE_DIR . '/' . $slugDir;
                    if (!is_dir($fsDir)) {
                        @mkdir($fsDir, 0775, true);
                    }
                    [$ext] = $allowed;
                    $base = bin2hex(random_bytes(8));
                    $filename = $base . '.' . $ext;
                    $destFs = $fsDir . '/' . $filename;
                    if (move_uploaded_file((string) $_FILES['video_file']['tmp_name'], $destFs)) {
                        $path = $slugDir . '/' . $filename;
                    }
                }
            }
            if ($path !== null) {
                $pdo->prepare('INSERT INTO project_videos (project_id, video_type, src_path, title, sort_order) VALUES (?,?,?,?,?)')
                    ->execute([$id, 'file', $path, $vTitle, 999]);
                flash('admin_notice', 'Video file uploaded.');
            } else {
                flash('admin_notice', 'Video upload failed. Use MP4/WebM under 50MB.');
            }
        }
        redirect('admin_projects_edit.php?id=' . $id);
    }

    if ($action === 'upload_gallery' && $id > 0) {
        $slug = (string) ($projectRow['slug'] ?? 'project');
        $uploaded = 0;
        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $count = count($_FILES['gallery_images']['name']);
            $now = date('c');
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM project_images WHERE project_id = ' . (int) $id)->fetchColumn();
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$i] ?? '',
                    'type' => $_FILES['gallery_images']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i] ?? '',
                    'error' => $_FILES['gallery_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['gallery_images']['size'][$i] ?? 0,
                ];
                if ((int) ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $path = site_content_save_uploaded_image($file, 'assets/projects/' . $slug);
                if ($path !== null) {
                    $maxSort++;
                    $pdo->prepare('INSERT INTO project_images (project_id, image_path, sort_order, created_at) VALUES (?,?,?,?)')
                        ->execute([$id, $path, $maxSort, $now]);
                    $uploaded++;
                }
            }
        }
        flash('admin_notice', $uploaded > 0 ? "Added $uploaded image(s) to gallery." : 'No images were uploaded.');
        redirect('admin_projects_edit.php?id=' . $id);
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $shortTitle = trim((string) ($_POST['short_title'] ?? ''));
    $tagline = trim((string) ($_POST['tagline'] ?? ''));
    $icon = trim((string) ($_POST['icon'] ?? 'fa-folder-open')) ?: 'fa-folder-open';
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $body = site_content_sanitize_html((string) ($_POST['body_html'] ?? ''));
    $published = isset($_POST['published']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $removeFeatured = isset($_POST['remove_featured']);

    if ($title === '' || $shortTitle === '') {
        flash('admin_notice', 'Title and short title are required.');
        redirect($id > 0 ? 'admin_projects_edit.php?id=' . $id : 'admin_projects_edit.php');
    }

    $baseSlug = $slugInput !== '' ? site_content_slugify($slugInput) : site_content_slugify($title);
    $slug = site_content_ensure_unique_project_slug($pdo, $baseSlug, $id > 0 ? $id : null);
    $now = date('c');

    $featuredPath = $projectRow ? ($projectRow['featured_image_path'] ?? null) : null;
    if (!is_string($featuredPath) || $featuredPath === '') {
        $featuredPath = null;
    }

    if ($removeFeatured && $featuredPath !== null) {
        site_content_delete_public_file($featuredPath, ['assets/projects/']);
        $featuredPath = null;
    }

    if (!empty($_FILES['featured_image']['tmp_name']) && is_uploaded_file((string) $_FILES['featured_image']['tmp_name'])) {
        $destDir = 'assets/projects/' . $slug;
        $newPath = site_content_save_uploaded_image($_FILES['featured_image'], $destDir, 'featured');
        if ($newPath === null) {
            flash('admin_notice', 'Featured image must be JPG, PNG, WebP, or GIF under 12MB.');
            redirect($id > 0 ? 'admin_projects_edit.php?id=' . $id : 'admin_projects_edit.php');
        }
        if ($featuredPath !== null) {
            site_content_delete_public_file($featuredPath, ['assets/projects/']);
        }
        $featuredPath = $newPath;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE projects SET slug=?, title=?, short_title=?, tagline=?, icon=?, excerpt=?, body_html=?, featured_image_path=?, sort_order=?, published=?, updated_at=? WHERE id=?'
        );
        $stmt->execute([$slug, $title, $shortTitle, $tagline, $icon, $excerpt, $body, $featuredPath, $sortOrder, $published, $now, $id]);
        log_admin_action($pdo, 'project_update', 'project', $id, ['title' => $title]);
        flash('admin_notice', 'Project saved.');
        redirect('admin_projects_edit.php?id=' . $id);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO projects (slug, title, short_title, tagline, icon, excerpt, body_html, featured_image_path, sort_order, published, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([$slug, $title, $shortTitle, $tagline, $icon, $excerpt, $body, $featuredPath, $sortOrder, $published, $now, $now]);
    $newId = (int) $pdo->lastInsertId();
    log_admin_action($pdo, 'project_create', 'project', $newId, ['title' => $title]);
    flash('admin_notice', 'Project created. You can add gallery images below.');
    redirect('admin_projects_edit.php?id=' . $newId);
}

$notice = flash('admin_notice');
$pageTitle = $projectRow ? 'Edit project' : 'New project';
$p = $projectRow ?? [];
$galleryImages = [];
$projectVideos = [];
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    $galleryImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $vstmt = $pdo->prepare('SELECT * FROM project_videos WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $vstmt->execute([$id]);
    $projectVideos = $vstmt->fetchAll(PDO::FETCH_ASSOC);
}

$iconOptions = [
    'fa-language', 'fa-hand-holding-heart', 'fa-landmark', 'fa-futbol', 'fa-truck-medical', 'fa-hammer',
    'fa-users', 'fa-graduation-cap', 'fa-seedling', 'fa-heart', 'fa-book',
];

render_layout_start($pageTitle, 'projects_admin');
?>
<div class="w-full max-w-5xl">
  <a href="admin_projects.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900"><i class="fa-solid fa-arrow-left"></i> All projects</a>
  <?php if ($projectRow && !empty($projectRow['published']) && !empty($projectRow['slug'])): ?>
    <a href="project_detail.php?slug=<?= h(urlencode((string) $projectRow['slug'])) ?>" target="_blank" rel="noopener" class="ml-4 text-sm font-semibold text-emerald-700 hover:underline">View on site</a>
  <?php endif; ?>

  <h1 class="text-2xl font-black text-slate-900 mt-4"><?= h($pageTitle) ?></h1>

  <?php if ($notice): ?>
    <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium"><?= h($notice) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="mt-6 space-y-5 bg-white border border-slate-200 p-6">
    <input type="hidden" name="action" value="save">

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block sm:col-span-2">
        <span class="text-sm font-semibold text-slate-700">Full title</span>
        <input name="title" required class="mt-1 w-full border border-slate-300 px-3 py-2" value="<?= h((string) ($p['title'] ?? '')) ?>">
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">Card title (short)</span>
        <input name="short_title" required class="mt-1 w-full border border-slate-300 px-3 py-2" value="<?= h((string) ($p['short_title'] ?? '')) ?>">
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">URL slug</span>
        <input name="slug" class="mt-1 w-full border border-slate-300 px-3 py-2 font-mono text-sm" value="<?= h((string) ($p['slug'] ?? '')) ?>" placeholder="auto from title">
      </label>
      <label class="block sm:col-span-2">
        <span class="text-sm font-semibold text-slate-700">Tagline</span>
        <input name="tagline" class="mt-1 w-full border border-slate-300 px-3 py-2" value="<?= h((string) ($p['tagline'] ?? '')) ?>">
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">Icon (Font Awesome class)</span>
        <select name="icon" class="mt-1 w-full border border-slate-300 px-3 py-2">
          <?php foreach ($iconOptions as $ic): ?>
            <option value="<?= h($ic) ?>" <?= ($p['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= h($ic) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">Sort order</span>
        <input type="number" name="sort_order" class="mt-1 w-full border border-slate-300 px-3 py-2" value="<?= (int) ($p['sort_order'] ?? 0) ?>">
      </label>
      <label class="block sm:col-span-2">
        <span class="text-sm font-semibold text-slate-700">Excerpt</span>
        <textarea name="excerpt" rows="3" class="mt-1 w-full border border-slate-300 px-3 py-2"><?= h((string) ($p['excerpt'] ?? '')) ?></textarea>
      </label>
      <label class="block sm:col-span-2">
        <span class="text-sm font-semibold text-slate-700">Body (HTML)</span>
        <textarea name="body_html" rows="12" class="mt-1 w-full border border-slate-300 px-3 py-2 font-mono text-sm"><?= h((string) ($p['body_html'] ?? '')) ?></textarea>
      </label>
    </div>

    <div class="border-t border-slate-200 pt-5">
      <p class="text-sm font-bold text-slate-800">Featured image</p>
      <?php
      $feat = (string) ($p['featured_image_path'] ?? '');
      if ($feat !== '' && site_content_file_exists($feat)):
      ?>
        <img src="<?= h($feat) ?>" alt="" class="mt-2 max-h-40 rounded border border-slate-200 object-cover">
      <?php endif; ?>
      <label class="mt-3 block">
        <span class="text-sm text-slate-600">Upload new featured image</span>
        <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full text-sm">
      </label>
      <?php if ($feat !== ''): ?>
        <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="remove_featured" value="1"> Remove current featured image
        </label>
      <?php endif; ?>
    </div>

    <label class="flex items-center gap-2">
      <input type="checkbox" name="published" value="1" <?= !isset($p['published']) || !empty($p['published']) ? 'checked' : '' ?>>
      <span class="text-sm font-semibold text-slate-700">Published on public site</span>
    </label>

    <div class="flex flex-wrap gap-3 pt-2">
      <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold">Save project</button>
    </div>
  </form>

  <?php if ($id > 0): ?>
    <section class="mt-10 bg-white border border-slate-200 p-6">
      <h2 class="text-lg font-bold text-slate-900">Photo gallery</h2>
      <p class="text-sm text-slate-500 mt-1">Images appear in the sticky gallery on the project page (3 per row).</p>

      <?php if (count($galleryImages) > 0): ?>
        <div class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
          <?php foreach ($galleryImages as $img): ?>
            <?php $path = (string) ($img['image_path'] ?? ''); ?>
            <div class="relative group border border-slate-200 rounded overflow-hidden">
              <?php if ($path !== '' && site_content_file_exists($path)): ?>
                <img src="<?= h($path) ?>" alt="" class="aspect-square w-full object-cover">
              <?php endif; ?>
              <form method="post" class="absolute top-1 right-1" onsubmit="return confirm('Remove this image?');">
                <input type="hidden" name="action" value="delete_image">
                <input type="hidden" name="image_id" value="<?= (int) ($img['id'] ?? 0) ?>">
                <button type="submit" class="h-7 w-7 rounded-full bg-red-600 text-white text-xs shadow" title="Delete"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="mt-3 text-sm text-slate-500">No gallery images yet.</p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="mt-6 border-t border-slate-100 pt-5">
        <input type="hidden" name="action" value="upload_gallery">
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Upload gallery images</span>
          <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="mt-1 block w-full text-sm">
        </label>
        <button type="submit" class="mt-3 px-4 py-2 bg-slate-800 text-white text-sm font-semibold">Upload to gallery</button>
      </form>
    </section>

    <section class="mt-8 bg-white border border-slate-200 p-6">
      <h2 class="text-lg font-bold text-slate-900">Videos</h2>
      <?php if (count($projectVideos) > 0): ?>
        <ul class="mt-4 space-y-3">
          <?php foreach ($projectVideos as $v): ?>
            <li class="flex items-center justify-between gap-3 border border-slate-100 p-3">
              <div>
                <p class="font-semibold text-slate-900"><?= h((string) ($v['title'] ?? 'Video')) ?></p>
                <p class="text-xs text-slate-500"><?= h((string) ($v['video_type'] ?? '')) ?><?php if (($v['video_type'] ?? '') === 'youtube'): ?> · <?= h((string) ($v['youtube_id'] ?? '')) ?><?php endif; ?></p>
              </div>
              <form method="post" onsubmit="return confirm('Remove this video?');">
                <input type="hidden" name="action" value="delete_video">
                <input type="hidden" name="video_id" value="<?= (int) ($v['id'] ?? 0) ?>">
                <button type="submit" class="text-red-600 text-sm font-semibold">Delete</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="mt-3 text-sm text-slate-500">No videos yet.</p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="mt-6 border-t border-slate-100 pt-5 space-y-3">
        <input type="hidden" name="action" value="add_video">
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">Video title</span>
          <input name="video_title" class="mt-1 w-full border border-slate-300 px-3 py-2" value="Highlight">
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-slate-700">YouTube video ID</span>
          <input name="youtube_id" class="mt-1 w-full border border-slate-300 px-3 py-2 font-mono text-sm" placeholder="dQw4w9WgXcQ">
        </label>
        <p class="text-xs text-slate-500">Or upload MP4/WebM:</p>
        <input type="file" name="video_file" accept="video/mp4,video/webm" class="block w-full text-sm">
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm font-semibold">Add video</button>
      </form>
    </section>

    <form method="post" class="mt-8" onsubmit="return confirm('Delete this entire project permanently?');">
      <input type="hidden" name="action" value="delete_project">
      <button type="submit" class="text-red-700 font-semibold text-sm hover:underline">Delete project</button>
    </form>
  <?php endif; ?>
</div>
<?php render_layout_end(); ?>
