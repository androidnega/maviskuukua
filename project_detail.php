<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/projects_lib.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: projects.php');
    exit;
}

$project = project_by_slug($slug);
if ($project === null) {
    header('Location: projects.php');
    exit;
}

tracking_public_hit('/project_detail.php');

require_once __DIR__ . '/public_layout.php';

$title = (string) ($project['title'] ?? 'Project');
$pageTitle = $title . ' | Projects';
$desc = trim((string) ($project['excerpt'] ?? ''));
$featSrc = project_featured_src($project);
$gallery = project_gallery_images($project);
$videos = project_videos($project);
$icon = (string) ($project['icon'] ?? 'fa-folder-open');
$tagline = trim((string) ($project['tagline'] ?? ''));
$excerpt = trim((string) ($project['excerpt'] ?? ''));

$galleryImages = [];
if ($featSrc !== '') {
    $galleryImages[] = ['path' => $featSrc, 'caption' => ''];
}
foreach ($gallery as $img) {
    if ($featSrc !== '' && $img['path'] === $featSrc) {
        continue;
    }
    $galleryImages[] = $img;
}
$hasGallery = count($galleryImages) > 0;

/**
 * @param list<array{path: string, caption: string}> $images
 */
function render_project_photo_grid(array $images, string $pageTitle): void {
    $total = count($images);
    ?>
    <div class="project-photo-grid grid grid-cols-3 gap-2 sm:gap-2.5">
      <?php foreach ($images as $i => $img): ?>
        <button
          type="button"
          class="project-photo-thumb group block w-full overflow-hidden rounded-xl border border-line bg-slate-50 p-0 shadow-sm cursor-zoom-in"
          data-lightbox="project"
          data-lightbox-src="<?= h($img['path']) ?>"
          data-lightbox-index="<?= (int) $i ?>"
          aria-label="View image <?= (int) $i + 1 ?> of <?= $total ?>"
        >
          <span class="block aspect-square overflow-hidden">
            <img
              src="<?= h($img['path']) ?>"
              alt="<?= h($pageTitle) ?>"
              class="h-full w-full object-cover object-center transition duration-300 group-hover:scale-[1.05]"
              loading="lazy"
            />
          </span>
        </button>
      <?php endforeach; ?>
    </div>
    <?php
}

render_public_layout_start($pageTitle, 'projects', $desc, 'project_detail');
?>
<style>
  /* Desktop: left text scrolls; right gallery stays pinned (does not move with page scroll) */
  @media (min-width: 1024px) {
    .project-detail-layout--split {
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
      align-items: start;
      column-gap: 3rem;
    }
    .project-detail-gallery {
      position: relative;
      height: 0;
      overflow: visible;
    }
    .project-detail-gallery-inner {
      position: fixed;
      top: calc(var(--nav-h) + 1.5rem);
      width: min(28rem, calc(42vw - 2rem));
      max-height: calc(100vh - var(--nav-h) - 2.5rem);
      overflow-y: auto;
      overscroll-behavior: contain;
      z-index: 30;
    }
    .project-detail-shell {
      position: relative;
    }
    @media (min-width: 1280px) {
      .project-detail-gallery-inner {
        width: min(32rem, calc((min(80rem, 100vw - 2.5rem) - 3rem) / 2 - 1rem));
        right: max(1.25rem, calc((100vw - min(80rem, 100vw - 2.5rem)) / 2 + 1.25rem));
      }
    }
    @media (min-width: 1024px) and (max-width: 1279px) {
      .project-detail-gallery-inner {
        right: 1.25rem;
        width: min(28rem, calc(46vw - 2rem));
      }
    }
  }

  .project-lightbox {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    opacity: 0;
    visibility: hidden;
    transition: opacity 200ms ease, visibility 200ms ease;
  }
  .project-lightbox.is-open {
    opacity: 1;
    visibility: visible;
  }
  .project-lightbox-backdrop {
    position: absolute;
    inset: 0;
    border: 0;
    background: rgba(15, 23, 42, 0.88);
    cursor: pointer;
  }
  .project-lightbox-panel {
    position: relative;
    z-index: 1;
    display: flex;
    max-width: min(96vw, 72rem);
    max-height: min(92vh, 52rem);
    align-items: center;
    justify-content: center;
  }
  .project-lightbox-img {
    max-width: 100%;
    max-height: min(92vh, 52rem);
    width: auto;
    height: auto;
    border-radius: 0.75rem;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
    object-fit: contain;
  }
  .project-lightbox-close,
  .project-lightbox-prev,
  .project-lightbox-next {
    position: absolute;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.95);
    color: #0f172a;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
    transition: background 150ms ease, transform 150ms ease;
  }
  .project-lightbox-close:hover,
  .project-lightbox-prev:hover,
  .project-lightbox-next:hover {
    background: #ffffff;
    transform: scale(1.05);
  }
  .project-lightbox-close {
    top: -0.5rem;
    right: -0.5rem;
    width: 2.5rem;
    height: 2.5rem;
    font-size: 1.1rem;
  }
  .project-lightbox-prev,
  .project-lightbox-next {
    top: 50%;
    width: 2.75rem;
    height: 2.75rem;
    transform: translateY(-50%);
  }
  .project-lightbox-prev { left: -3.25rem; }
  .project-lightbox-next { right: -3.25rem; }
  .project-lightbox-prev:hover,
  .project-lightbox-next:hover {
    transform: translateY(-50%) scale(1.05);
  }
  @media (max-width: 640px) {
    .project-lightbox-prev { left: 0.25rem; }
    .project-lightbox-next { right: 0.25rem; }
    .project-lightbox-close { top: 0.25rem; right: 0.25rem; }
  }
  body.project-lightbox-open {
    overflow: hidden;
  }
</style>
<main class="public-main">
  <article class="section-padding border-t border-line">
    <div class="project-detail-shell <?= h(public_page_container_class()) ?>">
      <p class="reveal">
        <a href="projects.php" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:underline">
          <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
          All projects
        </a>
      </p>

      <div class="project-detail-layout mt-8 <?= $hasGallery ? 'project-detail-layout--split' : '' ?>">
        <div class="min-w-0">
          <div class="reveal">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
              <i class="fa-solid <?= h($icon) ?> mr-2" aria-hidden="true"></i>
              Ahanta West initiative
            </p>
            <h1 class="section-title font-display mt-3 text-3xl font-bold leading-tight text-slate-950 md:text-4xl lg:text-5xl"><?= h($title) ?></h1>
            <?php if ($tagline !== ''): ?>
              <p class="mt-4 text-lg font-medium leading-relaxed text-slate-700 md:text-xl"><?= h($tagline) ?></p>
            <?php endif; ?>
            <?php if ($excerpt !== ''): ?>
              <p class="mt-5 text-base leading-8 text-slate-600 md:text-lg"><?= h($excerpt) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($hasGallery): ?>
            <section class="mt-8 lg:hidden reveal" aria-labelledby="project-gallery-mobile">
              <h2 id="project-gallery-mobile" class="font-display text-xl font-bold text-slate-950">Photo gallery</h2>
              <div class="mt-4">
                <?php render_project_photo_grid($galleryImages, $title); ?>
              </div>
            </section>
          <?php endif; ?>

          <div class="news-body mt-10 reveal lg:mt-12">
            <?= $project['body_html'] ?? '' ?>
          </div>

          <?php if (count($videos) > 0): ?>
            <section class="mt-14 reveal" aria-labelledby="project-videos-heading">
              <h2 id="project-videos-heading" class="font-display text-2xl font-bold text-slate-950">Videos</h2>
              <p class="mt-2 text-sm text-slate-600">Highlights and coverage from events and programs.</p>
              <div class="mt-8 flex flex-col gap-8">
                <?php foreach ($videos as $video): ?>
                  <div>
                    <h3 class="mb-3 text-base font-bold text-slate-900"><?= h($video['title']) ?></h3>
                    <?php if ($video['type'] === 'youtube'): ?>
                      <div class="aspect-video overflow-hidden rounded-2xl border border-line bg-slate-900 shadow-sm">
                        <iframe
                          class="h-full w-full"
                          src="https://www.youtube-nocookie.com/embed/<?= h($video['youtube_id']) ?>"
                          title="<?= h($video['title']) ?>"
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                          allowfullscreen
                          loading="lazy"
                        ></iframe>
                      </div>
                    <?php else: ?>
                      <video class="w-full rounded-2xl border border-line bg-slate-900 shadow-sm" controls playsinline preload="metadata">
                        <source src="<?= h($video['src']) ?>" type="video/mp4">
                        Your browser does not support embedded video.
                      </video>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <p class="mt-14 reveal pb-4">
            <a href="projects.php" class="btn-secondary inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
              All projects
            </a>
          </p>
        </div>

        <?php if ($hasGallery): ?>
          <aside class="project-detail-gallery hidden lg:block" aria-labelledby="project-gallery-desktop">
            <div class="project-detail-gallery-inner">
              <h2 id="project-gallery-desktop" class="font-display text-lg font-bold text-slate-950">Photo gallery</h2>
              <p class="mt-1 text-sm text-slate-500">Moments from this initiative</p>
              <div class="mt-4">
                <?php render_project_photo_grid($galleryImages, $title); ?>
              </div>
            </div>
          </aside>
        <?php endif; ?>
      </div>
    </div>
  </article>

  <?php if ($hasGallery): ?>
  <div id="projectLightbox" class="project-lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
    <button type="button" class="project-lightbox-backdrop" aria-label="Close"></button>
    <div class="project-lightbox-panel">
      <button type="button" class="project-lightbox-close" aria-label="Close">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
      <button type="button" class="project-lightbox-prev" aria-label="Previous image">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </button>
      <img id="projectLightboxImg" class="project-lightbox-img" src="" alt="">
      <button type="button" class="project-lightbox-next" aria-label="Next image">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </button>
    </div>
  </div>
  <script>
  (function () {
    var lb = document.getElementById('projectLightbox');
    var img = document.getElementById('projectLightboxImg');
    if (!lb || !img) return;

    var items = [];
    var index = 0;
    var triggers = document.querySelectorAll('[data-lightbox="project"]');
    var byIdx = new Map();
    triggers.forEach(function (el) {
      var i = parseInt(el.getAttribute('data-lightbox-index') || '0', 10);
      var src = el.getAttribute('data-lightbox-src') || '';
      if (src && !byIdx.has(i)) byIdx.set(i, src);
    });
    byIdx.forEach(function (src, i) { items[+i] = src; });
    items = items.filter(Boolean);
    if (!items.length) return;

    function show(i) {
      index = (i + items.length) % items.length;
      img.src = items[index];
      img.alt = <?= json_encode($title, JSON_THROW_ON_ERROR) ?> + ' — image ' + (index + 1);
    }
    function open(i) {
      show(i);
      lb.hidden = false;
      lb.classList.add('is-open');
      document.body.classList.add('project-lightbox-open');
      lb.querySelector('.project-lightbox-close').focus();
    }
    function close() {
      lb.classList.remove('is-open');
      lb.hidden = true;
      document.body.classList.remove('project-lightbox-open');
      img.removeAttribute('src');
    }

    triggers.forEach(function (el) {
      el.addEventListener('click', function () {
        open(parseInt(el.getAttribute('data-lightbox-index') || '0', 10));
      });
    });
    lb.querySelector('.project-lightbox-backdrop').addEventListener('click', close);
    lb.querySelector('.project-lightbox-close').addEventListener('click', close);
    lb.querySelector('.project-lightbox-prev').addEventListener('click', function () { show(index - 1); });
    lb.querySelector('.project-lightbox-next').addEventListener('click', function () { show(index + 1); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(index - 1);
      if (e.key === 'ArrowRight') show(index + 1);
    });
  })();
  </script>
  <?php endif; ?>
</main>
<?php render_public_layout_end(); ?>
