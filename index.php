<?php require 'layout.php'; ?>
<?php render_layout_start('Mavis Kuukua Bissue', 'home'); ?>
<section class="max-w-5xl mx-auto grid lg:grid-cols-2 gap-8 items-center">
  <div class="bg-slate-950 text-white rounded-3xl p-8 md:p-12">
    <p class="text-sm uppercase tracking-[0.35em] text-emerald-300 mb-4">Ahanta West Constituency</p>
    <h1 class="text-4xl md:text-5xl font-black leading-tight">Membership Registration Portal</h1>
    <p class="mt-5 text-slate-300 leading-8">Hon. Mavis Kuukua Bissue is the Member of Parliament for Ahanta West and the first female MP for the constituency. This portal collects membership registration details securely for administrative records.</p>
    <div class="mt-8 flex flex-wrap gap-3">
      <a href="register.php" class="px-6 py-3 rounded-xl bg-emerald-400 text-slate-950 font-bold hover:bg-emerald-300"><i class="fa-solid fa-user-plus mr-2"></i>Register Now</a>
      <a href="login.php" class="px-6 py-3 rounded-xl border border-white/30 hover:bg-white/10"><i class="fa-solid fa-user-shield mr-2"></i>Admin Login</a>
    </div>
  </div>
  <div class="rounded-3xl bg-white border p-8 shadow-sm">
    <h2 class="text-2xl font-black mb-5">Simple Registration</h2>
    <ul class="space-y-4 text-slate-700">
      <li><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>Personal details</li>
      <li><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>Contact and constituency details</li>
      <li><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>Identification details</li>
      <li><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>Photo upload</li>
      <li><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>PDF document after submission</li>
    </ul>
  </div>
</section>
<?php render_layout_end(); ?>
