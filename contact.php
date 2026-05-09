<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/contact.php');
if (is_admin()) {
    redirect('admin.php');
}
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('Contact | Mavis Kuukua Bissue', 'contact', 'Contact the office of Mavis Kuukua Bissue — Ahanta West.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
      <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="reveal">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Contact Us</p>
          <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Contact the office.</h1>
          <p class="mt-5 text-base leading-8 text-slate-600">Use the contact cards or form below for official enquiries. The form is front-end only and can be connected to a backend later.</p>

          <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="simple-card p-5">
              <i class="fa-solid fa-building mb-3 text-xl text-emerald-700" aria-hidden="true"></i>
              <h2 class="font-bold text-slate-950">Office</h2>
              <p class="mt-1 text-sm text-slate-600">Official Office</p>
            </div>
            <div class="simple-card p-5">
              <i class="fa-solid fa-phone mb-3 text-xl text-emerald-700" aria-hidden="true"></i>
              <h2 class="font-bold text-slate-950">Phone</h2>
              <p class="mt-1 text-sm text-slate-600">+233 XX XXX XXXX</p>
            </div>
            <div class="simple-card p-5">
              <i class="fa-solid fa-envelope mb-3 text-xl text-emerald-700" aria-hidden="true"></i>
              <h2 class="font-bold text-slate-950">Email</h2>
              <p class="mt-1 text-sm text-slate-600">info@example.com</p>
            </div>
            <div class="simple-card p-5">
              <i class="fa-solid fa-location-dot mb-3 text-xl text-emerald-700" aria-hidden="true"></i>
              <h2 class="font-bold text-slate-950">Location</h2>
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
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
          </button>
        </form>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
