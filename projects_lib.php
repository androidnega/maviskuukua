<?php
declare(strict_types=1);

require_once __DIR__ . '/site_content_lib.php';

/** @return list<array<string, mixed>> */
function projects_list(): array {
    return projects_list_published(db());
}

/** @return array<string, mixed>|null */
function project_by_slug(string $slug): ?array {
    return site_content_project_by_slug(db(), $slug, true);
}

function project_public_path(string $rel): string {
    return site_content_public_path($rel);
}

function project_file_exists(string $rel): bool {
    return site_content_file_exists($rel);
}

function project_featured_src(array $project): string {
    $feat = project_public_path((string) ($project['featured_image'] ?? ''));
    if ($feat !== '' && project_file_exists($feat)) {
        return $feat;
    }
    foreach ($project['images'] ?? [] as $img) {
        $p = project_public_path((string) ($img['path'] ?? ''));
        if ($p !== '' && project_file_exists($p)) {
            return $p;
        }
    }

    return '';
}

/** @return list<array{path: string, caption: string}> */
function project_gallery_images(array $project): array {
    $out = [];
    foreach ($project['images'] ?? [] as $img) {
        $p = project_public_path((string) ($img['path'] ?? ''));
        if ($p === '' || !project_file_exists($p)) {
            continue;
        }
        $out[] = [
            'path' => $p,
            'caption' => (string) ($img['caption'] ?? ''),
        ];
    }

    return $out;
}

/** @return list<array{type: string, src: string, title: string, youtube_id: string}> */
function project_videos(array $project): array {
    $out = [];
    foreach ($project['videos'] ?? [] as $v) {
        $type = (string) ($v['type'] ?? 'file');
        $title = (string) ($v['title'] ?? 'Video');
        if ($type === 'youtube') {
            $id = trim((string) ($v['youtube_id'] ?? ''));
            if ($id !== '') {
                $out[] = ['type' => 'youtube', 'src' => '', 'title' => $title, 'youtube_id' => $id];
            }
            continue;
        }
        $src = project_public_path((string) ($v['src'] ?? ''));
        if ($src !== '' && project_file_exists($src)) {
            $out[] = ['type' => 'file', 'src' => $src, 'title' => $title, 'youtube_id' => ''];
        }
    }

    return $out;
}
