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

tracking_public_hit('success');
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
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Success</title><link rel="icon" type="image/svg+xml" href="assets/favicon.svg"><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100"><main class="min-h-screen flex items-center justify-center px-4"><div class="bg-white max-w-lg w-full rounded-3xl border p-8 text-center"><div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl">✓</div><h1 class="text-3xl font-black mt-6">Registration Submitted</h1><p class="text-slate-500 mt-2">Your registration has been received successfully.</p><div class="mt-6 flex justify-center gap-3"><?php if(!empty($m['pdf_path'])): ?><a target="_blank" class="px-4 py-2 rounded-xl bg-slate-950 text-white" href="view_pdf.php?id=<?=$id?>">View PDF</a><a class="px-4 py-2 rounded-xl bg-white border" href="view_pdf.php?id=<?=$id?>&download=1">Download PDF</a><?php endif; ?></div><a href="index.php" class="block mt-6 text-sm text-slate-500">Back home</a></div></main><script>(function(){try{localStorage.removeItem('mavis_registration_draft_v1');localStorage.removeItem('mavis_registration_step_v1');}catch(e){}})();</script></body></html>
