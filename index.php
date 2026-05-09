<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/');
if (is_admin()) {
    redirect('admin.php');
}

$heroCandidates = [
    'assets/hero-campaign.png',
    'assets/hero-campaign.jpg',
    'assets/hero.jpg',
    'Screenshot 2026-05-06 at 3.33.08 AM.png',
];
$heroSrc = '';
foreach ($heroCandidates as $rel) {
    if (is_file(BASE_DIR . '/' . $rel)) {
        $heroSrc = $rel;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Official website of Mavis Kuukua Bissue: leadership, service, community development, and progress." />
  <title>Mavis Kuukua Bissue | Official Website</title>

  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            display: ['Playfair Display', 'serif']
          },
          colors: {
            official: '#087F5B',
            officialDark: '#065F46',
            line: '#E5E7EB'
          }
        }
      }
    };
  </script>

  <style>
    :root { --nav-h: 76px; }

    html,
    body,
    main,
    section,
    header,
    footer {
      background: #ffffff !important;
    }

    body {
      font-family: 'Inter', sans-serif;
      color: #334155;
      overflow-x: hidden;
    }

    .section-padding {
      padding-top: 5rem;
      padding-bottom: 5rem;
    }

    .section-title {
      letter-spacing: -0.035em;
    }

    .simple-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 1.25rem;
      transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
    }

    .simple-card:hover {
      transform: translateY(-4px);
      border-color: rgba(8, 127, 91, 0.35);
      box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .nav-link {
      color: #475569;
      font-weight: 600;
      font-size: 0.92rem;
      transition: color 180ms ease;
    }

    .nav-link:hover,
    .nav-link.active {
      color: #087F5B;
    }

    .btn-primary {
      background: #087F5B;
      color: #ffffff;
      border: 1px solid #087F5B;
      transition: background 180ms ease, transform 180ms ease;
    }

    .btn-primary:hover {
      background: #065F46;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #ffffff;
      color: #065F46;
      border: 1px solid rgba(8, 127, 91, 0.35);
      transition: border-color 180ms ease, transform 180ms ease;
    }

    .btn-secondary:hover {
      border-color: #087F5B;
      transform: translateY(-2px);
    }

    .reveal {
      opacity: 0;
      transform: translateY(16px);
      transition: opacity 500ms ease, transform 500ms ease;
    }

    .reveal.show {
      opacity: 1;
      transform: translateY(0);
    }

    .float-menu-item {
      opacity: 0;
      transform: translateY(12px) scale(0.96);
      pointer-events: none;
      transition: opacity 180ms ease, transform 180ms ease;
    }

    .float-menu.open .float-menu-item {
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
    }

    .float-menu.open #plusIcon {
      transform: rotate(45deg);
    }

    #plusIcon {
      transition: transform 180ms ease;
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        scroll-behavior: auto !important;
        transition: none !important;
        animation: none !important;
      }

      .reveal {
        opacity: 1;
        transform: none;
      }
    }
  </style>
</head>
<body>
  <header class="fixed left-0 right-0 top-0 z-50 border-b border-line bg-white/95 backdrop-blur">
    <nav class="mx-auto flex h-[var(--nav-h)] max-w-7xl items-center justify-between px-5 lg:px-8" aria-label="Main navigation">
      <a href="#home" class="flex items-center gap-3 section-link" aria-label="Mavis Kuukua Bissue home">
        <span class="grid h-10 w-10 place-items-center rounded-full border border-emerald-700 text-emerald-700">
          <i class="fa-solid fa-leaf"></i>
        </span>
        <span class="leading-tight">
          <span class="block text-sm font-bold text-slate-900">Mavis Kuukua Bissue</span>
          <span class="block text-xs font-medium text-slate-500">Official Website</span>
        </span>
      </a>

      <div class="hidden items-center gap-6 lg:flex">
        <a class="nav-link section-link active" href="#home">Home</a>
        <a class="nav-link section-link" href="#about">About Us</a>
        <a class="nav-link section-link" href="#vision">Vision</a>
        <a class="nav-link section-link" href="#projects">Projects</a>
        <a class="nav-link section-link" href="#news">News</a>
        <a class="nav-link" href="register.php">Membership</a>
        <a class="nav-link section-link" href="#contact">Contact Us</a>
      </div>

      <button id="mobileMenuBtn" class="grid h-10 w-10 place-items-center rounded-lg border border-line text-slate-700 lg:hidden" type="button" aria-label="Open mobile menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
      </button>
    </nav>

    <div id="mobileMenu" class="hidden border-t border-line bg-white px-5 py-4 lg:hidden">
      <div class="mx-auto grid max-w-7xl gap-2">
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#home">Home</a>
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#about">About Us</a>
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#vision">Vision</a>
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#projects">Projects</a>
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#news">News</a>
        <a class="nav-link rounded-lg px-3 py-3" href="register.php">Membership registration</a>
        <a class="nav-link section-link rounded-lg px-3 py-3" href="#contact">Contact Us</a>
      </div>
    </div>
  </header>

  <main>
    <section id="home" class="section-padding min-h-screen pt-32">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
        <div class="reveal">
          <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-line px-4 py-2 text-sm font-semibold text-emerald-700">
            <i class="fa-solid fa-circle-check"></i>
            Official public website
          </p>
          <h1 class="section-title font-display text-4xl font-bold leading-tight text-slate-950 md:text-6xl">
            Mavis Kuukua Bissue
          </h1>
          <p class="mt-5 max-w-2xl text-xl font-semibold leading-relaxed text-slate-800">
            Leadership rooted in service, community progress, and shared development.
          </p>
          <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 md:text-lg">
            Hon. Mavis Kuukua Bissue is the Member of Parliament for Ahanta West. This site shares vision, projects, and updates — and links to secure membership registration for administrative records.
          </p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="#vision" class="section-link btn-primary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
              Explore Vision
              <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="#contact" class="section-link btn-secondary inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold">
              Contact Office
              <i class="fa-regular fa-envelope"></i>
            </a>
            <a href="register.php" class="inline-flex items-center justify-center gap-2 rounded-full border border-emerald-700 bg-white px-6 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50">
              Register for membership
              <i class="fa-solid fa-id-card"></i>
            </a>
          </div>
        </div>

        <div class="reveal">
          <div class="rounded-[2rem] border border-line bg-white p-3">
            <?php if ($heroSrc !== ''): ?>
              <img src="<?= h($heroSrc) ?>" alt="Mavis Kuukua Bissue official portrait" class="h-[420px] w-full rounded-[1.5rem] object-cover object-center md:h-[560px]" />
            <?php else: ?>
              <div class="flex h-[420px] w-full flex-col items-center justify-center gap-3 rounded-[1.5rem] bg-slate-100 text-slate-500 md:h-[560px]">
                <i class="fa-solid fa-image text-4xl text-slate-400"></i>
                <p class="max-w-xs px-4 text-center text-sm">Add <code class="rounded bg-white px-1.5 py-0.5 text-xs">assets/hero-campaign.png</code> for the hero image.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section id="about" class="section-padding border-t border-line">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div class="reveal">
            <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">About Us</p>
            <h2 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Community-focused leadership with a clear sense of duty.</h2>
            <p class="mt-5 text-base leading-8 text-slate-600">
              Mavis Kuukua Bissue is presented as a community-focused leader committed to service, integrity, unity, and practical development. This official website shares her public vision, community work, project updates, and contact information.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <article class="simple-card reveal p-6">
              <i class="fa-solid fa-hand-holding-heart mb-5 text-2xl text-emerald-700"></i>
              <h3 class="text-lg font-bold text-slate-950">Service</h3>
              <p class="mt-2 text-sm leading-7 text-slate-600">Putting people first through consistent community engagement and responsive leadership.</p>
            </article>
            <article class="simple-card reveal p-6">
              <i class="fa-solid fa-shield-halved mb-5 text-2xl text-emerald-700"></i>
              <h3 class="text-lg font-bold text-slate-950">Integrity</h3>
              <p class="mt-2 text-sm leading-7 text-slate-600">Promoting honest, accountable, and transparent public service.</p>
            </article>
            <article class="simple-card reveal p-6">
              <i class="fa-solid fa-people-group mb-5 text-2xl text-emerald-700"></i>
              <h3 class="text-lg font-bold text-slate-950">Unity</h3>
              <p class="mt-2 text-sm leading-7 text-slate-600">Bringing people together around shared values and common progress.</p>
            </article>
            <article class="simple-card reveal p-6">
              <i class="fa-solid fa-chart-line mb-5 text-2xl text-emerald-700"></i>
              <h3 class="text-lg font-bold text-slate-950">Development</h3>
              <p class="mt-2 text-sm leading-7 text-slate-600">Supporting practical projects that improve lives and strengthen communities.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section id="vision" class="section-padding border-t border-line">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="mx-auto max-w-3xl text-center reveal">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Vision</p>
          <h2 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">A clear vision for people, opportunity, and local progress.</h2>
          <p class="mt-5 text-base leading-8 text-slate-600">The vision is centered on stronger communities, better support systems, and opportunities that help people grow with dignity.</p>
        </div>

        <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <div class="simple-card reveal p-6 text-center">
            <i class="fa-solid fa-graduation-cap text-2xl text-emerald-700"></i>
            <h3 class="mt-4 font-bold text-slate-950">Education</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Support for learning, mentorship, and access.</p>
          </div>
          <div class="simple-card reveal p-6 text-center">
            <i class="fa-solid fa-person-dress text-2xl text-emerald-700"></i>
            <h3 class="mt-4 font-bold text-slate-950">Women</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Empowerment, dignity, and opportunity.</p>
          </div>
          <div class="simple-card reveal p-6 text-center">
            <i class="fa-solid fa-users text-2xl text-emerald-700"></i>
            <h3 class="mt-4 font-bold text-slate-950">Youth</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Skills, leadership, and job readiness.</p>
          </div>
          <div class="simple-card reveal p-6 text-center">
            <i class="fa-solid fa-briefcase-medical text-2xl text-emerald-700"></i>
            <h3 class="mt-4 font-bold text-slate-950">Healthcare</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Community wellness and public health support.</p>
          </div>
          <div class="simple-card reveal p-6 text-center">
            <i class="fa-solid fa-seedling text-2xl text-emerald-700"></i>
            <h3 class="mt-4 font-bold text-slate-950">Development</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Local growth and community improvement.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="projects" class="section-padding border-t border-line">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
          <div class="reveal max-w-2xl">
            <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Projects</p>
            <h2 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Community work and development priorities.</h2>
          </div>
          <p class="reveal max-w-md text-sm leading-7 text-slate-600">Project cards below are prepared as official examples and can be replaced with real office updates.</p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-handshake-angle text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Community Outreach</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Engagement programs focused on listening, support, and practical community needs.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-lightbulb text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Youth Empowerment</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Mentorship, skills training, and leadership opportunities for young people.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-ribbon text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Women Support</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Programs that promote women’s welfare, enterprise, confidence, and inclusion.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-book-open-reader text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Education Support</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Learning resources, school support, and initiatives that help students progress.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-road text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Local Development</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Community improvement efforts built around local priorities and shared progress.</p>
          </article>
          <article class="simple-card reveal p-6">
            <i class="fa-solid fa-people-arrows text-2xl text-emerald-700"></i>
            <h3 class="mt-5 text-xl font-bold text-slate-950">Public Engagement</h3>
            <p class="mt-3 text-sm leading-7 text-slate-600">Open communication between the office and the people it serves.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="news" class="section-padding border-t border-line">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="mx-auto max-w-3xl text-center reveal">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">News</p>
          <h2 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Latest official updates.</h2>
          <p class="mt-5 text-base leading-8 text-slate-600">Clean update cards for announcements, community work, and public notices.</p>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-3">
          <article class="simple-card reveal overflow-hidden">
            <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Community</div>
            <div class="p-6">
              <p class="text-sm text-slate-500">Official Update</p>
              <h3 class="mt-3 text-xl font-bold text-slate-950">Community engagement visit completed</h3>
              <p class="mt-3 text-sm leading-7 text-slate-600">A public engagement session focused on listening to community priorities and strengthening local collaboration.</p>
            </div>
          </article>
          <article class="simple-card reveal overflow-hidden">
            <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Youth</div>
            <div class="p-6">
              <p class="text-sm text-slate-500">Official Update</p>
              <h3 class="mt-3 text-xl font-bold text-slate-950">Youth skills support initiative announced</h3>
              <p class="mt-3 text-sm leading-7 text-slate-600">A planned initiative aimed at improving access to mentorship, training, and youth development opportunities.</p>
            </div>
          </article>
          <article class="simple-card reveal overflow-hidden">
            <div class="border-b border-line px-6 py-4 text-xs font-bold uppercase tracking-[0.15em] text-emerald-700">Office</div>
            <div class="p-6">
              <p class="text-sm text-slate-500">Public Notice</p>
              <h3 class="mt-3 text-xl font-bold text-slate-950">Office contact channels updated</h3>
              <p class="mt-3 text-sm leading-7 text-slate-600">Members of the public can use the official contact details below for enquiries and correspondence.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="contact" class="section-padding border-t border-line">
      <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
          <div class="reveal">
            <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Contact Us</p>
            <h2 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Contact the office.</h2>
            <p class="mt-5 text-base leading-8 text-slate-600">Use the contact cards or form below for official enquiries. The form is front-end only and can be connected to a backend later.</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
              <div class="simple-card p-5">
                <i class="fa-solid fa-building mb-3 text-xl text-emerald-700"></i>
                <h3 class="font-bold text-slate-950">Office</h3>
                <p class="mt-1 text-sm text-slate-600">Official Office</p>
              </div>
              <div class="simple-card p-5">
                <i class="fa-solid fa-phone mb-3 text-xl text-emerald-700"></i>
                <h3 class="font-bold text-slate-950">Phone</h3>
                <p class="mt-1 text-sm text-slate-600">+233 XX XXX XXXX</p>
              </div>
              <div class="simple-card p-5">
                <i class="fa-solid fa-envelope mb-3 text-xl text-emerald-700"></i>
                <h3 class="font-bold text-slate-950">Email</h3>
                <p class="mt-1 text-sm text-slate-600">info@example.com</p>
              </div>
              <div class="simple-card p-5">
                <i class="fa-solid fa-location-dot mb-3 text-xl text-emerald-700"></i>
                <h3 class="font-bold text-slate-950">Location</h3>
                <p class="mt-1 text-sm text-slate-600">Ghana</p>
              </div>
            </div>
          </div>

          <form class="simple-card reveal p-6 md:p-8" action="#" method="get" onsubmit="return false;">
            <div class="grid gap-5 sm:grid-cols-2">
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Full Name</span>
                <input type="text" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="Your name" autocomplete="name" />
              </label>
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Email Address</span>
                <input type="email" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="you@example.com" autocomplete="email" />
              </label>
            </div>
            <label class="mt-5 block">
              <span class="text-sm font-bold text-slate-700">Subject</span>
              <input type="text" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="How can we help?" />
            </label>
            <label class="mt-5 block">
              <span class="text-sm font-bold text-slate-700">Message</span>
              <textarea rows="5" class="mt-2 w-full resize-none rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="Write your message..."></textarea>
            </label>
            <button type="button" class="btn-primary mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold sm:w-auto">
              Send Message
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="border-t border-line py-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 text-sm text-slate-500 md:flex-row md:items-center md:justify-between lg:px-8">
      <p>&copy; <span id="year"></span> Mavis Kuukua Bissue. All rights reserved.</p>
      <div class="flex flex-wrap gap-4">
        <a href="#home" class="section-link hover:text-emerald-700">Home</a>
        <a href="register.php" class="hover:text-emerald-700">Membership</a>
        <a href="#contact" class="section-link hover:text-emerald-700">Contact</a>
        <a href="login.php" class="hover:text-emerald-700">Staff login</a>
      </div>
    </div>
  </footer>

  <div id="floatMenu" class="float-menu fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
    <div class="flex flex-col items-end gap-2">
      <a href="#home" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">Home Page</a>
      <a href="#about" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">About Us</a>
      <a href="#vision" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">Vision</a>
      <a href="#projects" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">Projects</a>
      <a href="#news" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">News</a>
      <a href="#contact" class="float-menu-item section-link rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-slate-700">Contact Us</a>
      <a href="register.php" class="float-menu-item rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">Membership</a>
    </div>
    <button id="floatToggle" class="grid h-14 w-14 place-items-center rounded-full bg-emerald-700 text-white" type="button" aria-label="Open page shortcuts" aria-expanded="false">
      <i id="plusIcon" class="fa-solid fa-plus text-lg"></i>
    </button>
  </div>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const floatMenu = document.getElementById('floatMenu');
    const floatToggle = document.getElementById('floatToggle');
    const sectionLinks = document.querySelectorAll('.section-link');
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('main section[id]');

    mobileMenuBtn.addEventListener('click', () => {
      const isOpen = !mobileMenu.classList.contains('hidden');
      mobileMenu.classList.toggle('hidden');
      mobileMenuBtn.setAttribute('aria-expanded', String(!isOpen));
      mobileMenuBtn.innerHTML = isOpen ? '<i class="fa-solid fa-bars"></i>' : '<i class="fa-solid fa-xmark"></i>';
    });

    floatToggle.addEventListener('click', () => {
      const isOpen = floatMenu.classList.toggle('open');
      floatToggle.setAttribute('aria-expanded', String(isOpen));
    });

    sectionLinks.forEach((link) => {
      link.addEventListener('click', (event) => {
        const targetId = link.getAttribute('href');
        if (!targetId || !targetId.startsWith('#')) return;

        const target = document.querySelector(targetId);
        if (!target) return;

        event.preventDefault();
        const navHeight = document.querySelector('header').offsetHeight;
        const top = target.getBoundingClientRect().top + window.scrollY - navHeight + 2;

        window.scrollTo({ top, behavior: 'smooth' });
        mobileMenu.classList.add('hidden');
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
        mobileMenuBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
        floatMenu.classList.remove('open');
        floatToggle.setAttribute('aria-expanded', 'false');
      });
    });

    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.14 });

    document.querySelectorAll('.reveal').forEach((item) => revealObserver.observe(item));

    const activeObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        const id = entry.target.getAttribute('id');
        navLinks.forEach((link) => {
          const href = link.getAttribute('href');
          link.classList.toggle('active', href === '#' + id);
        });
      });
    }, {
      rootMargin: '-35% 0px -55% 0px',
      threshold: 0
    });

    sections.forEach((section) => activeObserver.observe(section));
  </script>
</body>
</html>
