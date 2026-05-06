<?php
require 'config.php';
require_once __DIR__ . '/tracking.php';
tracking_public_hit('/');
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
<body class="bg-slate-950 text-white min-h-screen">
  <main class="max-w-6xl mx-auto px-4 pt-16 pb-8 md:pt-24 md:pb-12">
    <div class="grid lg:grid-cols-2 gap-8 items-center">
      <section>
        <h1 class="text-4xl md:text-6xl font-black leading-tight text-white">Mavis Kuukua Bissue Membership Registration</h1>
        <p class="mt-6 text-slate-300 max-w-lg leading-8">Hon. Mavis Kuukua Bissue is the Member of Parliament for Ahanta West and the first female MP for the constituency. This portal collects membership registration details securely for administrative records.</p>
        <div class="mt-8 max-w-md">
          <a href="register.php" class="flex items-center justify-between px-5 py-4  bg-emerald-600 hover:bg-emerald-500 text-white font-black border border-emerald-500">
            <span>Register</span>
            <i class="fa-solid fa-arrow-right"></i>
          </a>
          <p class="mt-3 text-emerald-300 text-sm font-semibold">Secure and fast registration process.</p>
        </div>
      </section>
      <section class="relative">
        <div class=" border border-emerald-600/40 bg-slate-900/40 p-4 flex justify-center">
          <img src="Screenshot 2026-05-06 at 3.33.08 AM.png" alt="Mavis campaign" class="h-[520px] w-auto max-w-full object-contain object-center  border border-white/10 bg-slate-950">
        </div>
      </section>
    </div>
  </main>
</body>
</html>
