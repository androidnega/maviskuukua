<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/news_lib.php';

require_admin();
require_news_management();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_news.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('admin_notice', 'Invalid news post.');
    redirect('admin_news.php');
}

$pdo = db();
$row = news_post_by_id($pdo, $id);
if (!$row) {
    flash('admin_notice', 'Post not found.');
    redirect('admin_news.php');
}

$feat = $row['featured_image_path'] ?? null;
news_delete_file_if_in_news_dir(is_string($feat) ? $feat : null);

$pdo->prepare('DELETE FROM news_posts WHERE id = ?')->execute([$id]);
log_admin_action($pdo, 'news_post_delete', 'news_post', $id, ['title' => $row['title'] ?? '']);

flash('admin_notice', 'News post deleted.');
redirect('admin_news.php');
