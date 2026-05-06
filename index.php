<?php
require 'config.php';
if (is_admin()) {
    redirect('admin.php');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mavis Kuukua Bissue</title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-[#0a542d] text-white min-h-screen">
  <header class="border-b border-white/10">
    <div class="max-w-6xl mx-auto px-4 py-2 text-xs text-emerald-100 flex flex-wrap gap-4 justify-between">
      <span><span class="font-semibold text-yellow-300">Support:</span> 0504651541 | 0504661670 | 0504667115</span>
      <span class="text-white/80">NDC Membership Platform</span>
    </div>
  </header>
  <main class="max-w-6xl mx-auto px-4 py-8 md:py-12">
    <div class="grid lg:grid-cols-2 gap-8 items-center">
      <section>
        <div class="inline-flex items-center px-4 py-1 rounded-full bg-white/10 border border-white/20 text-sm font-semibold mb-6">NDC</div>
        <h1 class="text-4xl md:text-6xl font-black leading-tight text-yellow-300">NDC Nomination Forms</h1>
        <p class="mt-6 text-emerald-100 max-w-lg leading-8">Welcome to the NDC Nomination Forms page. Complete your membership registration form quickly and securely.</p>
        <div class="mt-8 space-y-3 max-w-md">
          <a href="register.php" class="flex items-center justify-between px-5 py-4 rounded-full bg-gradient-to-r from-yellow-600 to-yellow-300 text-slate-900 font-black">
            <span>Buy BRANCH nomination form</span>
            <i class="fa-solid fa-cart-shopping"></i>
          </a>
          <a href="register.php" class="flex items-center justify-between px-5 py-4 rounded-full bg-gradient-to-r from-yellow-600 to-yellow-300 text-slate-900 font-black">
            <span>Claim BRANCH nomination form</span>
            <i class="fa-solid fa-circle-check"></i>
          </a>
        </div>
      </section>
      <section class="relative">
        <div class="rounded-3xl border border-yellow-500/40 bg-emerald-900/40 p-4">
          <img src="Screenshot 2026-05-06 at 3.33.08 AM.png" alt="Nomination campaign" class="w-full h-[420px] object-cover object-center rounded-2xl border border-white/10">
        </div>
        <div class="mt-4 rounded-2xl border border-yellow-500/40 bg-emerald-900/60 p-4 text-center">
          <div class="grid grid-cols-4 gap-2">
            <div class="bg-white/10 rounded-lg p-2"><p class="text-2xl font-black text-yellow-300">10</p><p class="text-xs">Days</p></div>
            <div class="bg-white/10 rounded-lg p-2"><p class="text-2xl font-black text-yellow-300">5</p><p class="text-xs">Hours</p></div>
            <div class="bg-white/10 rounded-lg p-2"><p class="text-2xl font-black text-yellow-300">43</p><p class="text-xs">Minutes</p></div>
            <div class="bg-white/10 rounded-lg p-2"><p class="text-2xl font-black text-yellow-300">6</p><p class="text-xs">Seconds</p></div>
          </div>
          <p class="mt-3 text-sm text-emerald-100">Picking ends on 16 May 2026</p>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
