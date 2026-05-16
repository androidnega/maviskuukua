<?php
declare(strict_types=1);

require 'config.php';
require_once __DIR__ . '/contact_lib.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/contact.php');

$errors = [];
$old = [
    'full_name' => '',
    'email' => '',
    'subject' => '',
    'body' => '',
];
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $honeypot = trim((string) ($_POST['company_website'] ?? ''));
    if ($honeypot !== '') {
        redirect('contact.php?sent=1');
    }

    $old = [
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'body' => trim((string) ($_POST['body'] ?? '')),
    ];

    $check = contact_validate_submission($_POST);
    if (!$check['ok']) {
        $errors = $check['errors'];
    } else {
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
        contact_insert_message(db(), $old['full_name'], $old['email'], $old['subject'], $old['body'], $ip);
        redirect('contact.php?sent=1');
    }
}

$publicEmail = contact_public_email();
require_once __DIR__ . '/public_layout.php';
render_public_layout_start('Contact | Mavis Kuukua Bissue', 'contact', 'Contact the office of Mavis Kuukua Bissue — Ahanta West.');
?>
<main class="public-main">
  <section class="section-padding border-t border-line">
    <div class="<?= h(public_page_container_class()) ?>">
      <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="reveal">
          <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Contact Us</p>
          <h1 class="section-title font-display text-3xl font-bold text-slate-950 md:text-5xl">Contact the office.</h1>
          <p class="mt-5 text-base leading-8 text-slate-600">Send a message using the form. Your enquiry is delivered to the team and appears in the staff dashboard for follow-up.</p>

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
              <p class="mt-1 text-sm text-slate-600">
                <a href="mailto:<?= h($publicEmail) ?>" class="font-semibold text-emerald-800 hover:underline"><?= h($publicEmail) ?></a>
              </p>
            </div>
            <div class="simple-card p-5">
              <i class="fa-solid fa-location-dot mb-3 text-xl text-emerald-700" aria-hidden="true"></i>
              <h2 class="font-bold text-slate-950">Location</h2>
              <p class="mt-1 text-sm text-slate-600">Ghana</p>
            </div>
          </div>
        </div>

        <div class="simple-card reveal p-6 md:p-8">
          <?php if ($sent && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
              <i class="fa-solid fa-circle-check mr-2"></i> Thank you. Your message has been sent. We will get back to you as soon as we can.
            </div>
          <?php endif; ?>

          <?php if ($errors !== []): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
              <p class="font-bold">Please fix the following:</p>
              <ul class="mt-2 list-disc pl-5">
                <?php foreach ($errors as $msg): ?>
                  <li><?= h($msg) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="contact.php" class="space-y-5">
            <input type="text" name="company_website" value="" tabindex="-1" autocomplete="off" class="absolute left-[-9999px] h-0 w-0 opacity-0" aria-hidden="true">

            <div class="grid gap-5 sm:grid-cols-2">
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Full Name</span>
                <input type="text" name="full_name" required maxlength="200" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="Your name" autocomplete="name" value="<?= h($old['full_name']) ?>" />
              </label>
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Email Address</span>
                <input type="email" name="email" required maxlength="320" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="you@example.com" autocomplete="email" value="<?= h($old['email']) ?>" />
              </label>
            </div>
            <label class="block">
              <span class="text-sm font-bold text-slate-700">Subject</span>
              <input type="text" name="subject" required maxlength="400" class="mt-2 w-full rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="How can we help?" value="<?= h($old['subject']) ?>" />
            </label>
            <label class="block">
              <span class="text-sm font-bold text-slate-700">Message</span>
              <textarea name="body" required rows="5" maxlength="8000" class="mt-2 w-full resize-y rounded-xl border border-line px-4 py-3 outline-none transition focus:border-emerald-700" placeholder="Write your message..."><?= h($old['body']) ?></textarea>
            </label>
            <button type="submit" class="btn-primary inline-flex w-full items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold sm:w-auto">
              Send Message
              <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</main>
<?php render_public_layout_end(); ?>
