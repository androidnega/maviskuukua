<?php
require 'layout.php';
require_admin();
require_settings_access();

$pdo = db();
$notice = flash('admin_notice');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'arkasel_api_key',
        'arkasel_sender_id',
        'arkasel_api_url',
        'arkasel_otp_generate_url',
    ];
    foreach ($keys as $key) {
        $val = trim((string)($_POST[$key] ?? ''));
        set_setting($pdo, $key, $val);
    }
    log_admin_action($pdo, 'settings_update', 'app_settings', null, ['keys' => $keys]);
    flash('admin_notice', 'Settings saved.');
    redirect('settings.php');
}

$values = [
    'arkasel_api_key' => get_setting($pdo, 'arkasel_api_key'),
    'arkasel_sender_id' => get_setting($pdo, 'arkasel_sender_id', 'MavisHub'),
    'arkasel_api_url' => get_setting($pdo, 'arkasel_api_url', 'https://sms.arkesel.com/sms/api'),
    'arkasel_otp_generate_url' => get_setting($pdo, 'arkasel_otp_generate_url', 'https://sms.arkesel.com/api/otp/generate'),
];
?>
<?php render_layout_start('SMS / API Settings', 'settings'); ?>
<div class="max-w-3xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Arkesel SMS &amp; OTP</h1>
  <p class="text-slate-500 mt-1 text-sm">Configure outbound SMS (registration confirmations) and OTP generation for staff onboarding. Keys are stored locally in SQLite.</p>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <form method="post" class="mt-8 bg-white rounded-3xl border border-slate-200 p-6 md:p-8 space-y-5">
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">API key</span>
      <input name="arkasel_api_key" type="password" autocomplete="off" value="<?=h($values['arkasel_api_key'])?>" class="mt-1 w-full rounded-xl border border-slate-200 p-3 font-mono text-sm" placeholder="Paste Arkesel API key">
      <span class="text-xs text-slate-500 mt-1 block">Used as Bearer token for standard SMS and as <code class="bg-slate-100 px-1 rounded">api-key</code> header for OTP endpoints.</span>
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">Sender ID</span>
      <input name="arkasel_sender_id" value="<?=h($values['arkasel_sender_id'])?>" class="mt-1 w-full rounded-xl border border-slate-200 p-3" required>
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">SMS API URL</span>
      <input name="arkasel_api_url" value="<?=h($values['arkasel_api_url'])?>" class="mt-1 w-full rounded-xl border border-slate-200 p-3 font-mono text-sm">
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">OTP generate URL</span>
      <input name="arkasel_otp_generate_url" value="<?=h($values['arkasel_otp_generate_url'])?>" class="mt-1 w-full rounded-xl border border-slate-200 p-3 font-mono text-sm">
      <span class="text-xs text-slate-500 mt-1 block">Must accept JSON with <code class="bg-slate-100 px-1 rounded">phone_number</code>, <code class="bg-slate-100 px-1 rounded">sender_id</code>, and message containing <code class="bg-slate-100 px-1 rounded">%otp_code%</code>.</span>
    </label>
    <button type="submit" class="w-full md:w-auto px-6 py-3 rounded-xl bg-slate-950 text-white font-bold hover:bg-slate-800">Save settings</button>
  </form>
</div>
<?php render_layout_end(); ?>
