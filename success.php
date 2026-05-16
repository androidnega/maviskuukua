<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
require 'pdf.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) {
    redirect('register.php');
}

tracking_success_page_view($id);

$pendingId = isset($_SESSION['pending_pdf_member_id']) ? (int)$_SESSION['pending_pdf_member_id'] : 0;
if ($pendingId === $id) {
    unset($_SESSION['pending_pdf_member_id']);
    @set_time_limit(120);
    $pdfOverrides = load_member_pdf_payload($id);
    if (isset($_SESSION['pdf_overrides'][$id]) && is_array($_SESSION['pdf_overrides'][$id])) {
        $pdfOverrides = array_merge($pdfOverrides, $_SESSION['pdf_overrides'][$id]);
    }
    try {
        $pdfPath = create_member_pdf($m, $pdfOverrides);
        $update = db()->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
        $update->execute([$pdfPath, $id]);
        $m['pdf_path'] = $pdfPath;
    } catch (Throwable $pdfError) {
        error_log('PDF generation failed for member ' . $id . ': ' . $pdfError->getMessage());
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thank you — Registration received</title>
  <?php require_once __DIR__ . '/public_header.php'; site_favicon_links(); ?>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100 text-slate-900 antialiased">
  <main class="flex min-h-screen flex-col items-center justify-center px-4 py-16 sm:py-20">
    <div class="w-full max-w-lg">
      <div class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-sm sm:p-10">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-500/10">
          <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <h1 class="mt-8 text-center text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
          Thank you
        </h1>
        <p class="mt-3 text-center text-base leading-relaxed text-slate-600">
          Your registration has been received successfully.
        </p>

        <?php if (!empty($m['pdf_path'])): ?>
          <div class="mt-8 flex flex-col items-stretch gap-3 sm:flex-row sm:justify-center">
            <a
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-slate-800"
              href="view_pdf.php?id=<?= (int)$id ?>"
            >
              View PDF
            </a>
            <a
              class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50"
              href="view_pdf.php?id=<?= (int)$id ?>&download=1"
            >
              Download PDF
            </a>
          </div>
        <?php endif; ?>

        <div class="mt-10 border-t border-slate-100 pt-8 text-center">
          <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-slate-400">
            Sponsored by
          </p>
          <p class="mt-2 text-sm font-semibold text-slate-700">
            Mavis Kuukua Bissue
          </p>
        </div>
      </div>

      <p class="mt-8 text-center">
        <a href="index.php" class="text-sm font-medium text-slate-500 underline-offset-4 transition hover:text-slate-800 hover:underline">
          Back to home
        </a>
      </p>
    </div>
  </main>
  <script>(function(){try{localStorage.removeItem('mavis_registration_draft_v1');localStorage.removeItem('mavis_registration_step_v1');}catch(e){}})();</script>
</body>
</html>
