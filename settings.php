<?php
require 'layout.php';
require_admin();
require_settings_access();

$pdo = db();
$notice = flash('admin_notice');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['settings_action'] ?? 'save'));

    if ($action === 'test_otp') {
        $phoneRaw = trim((string)($_POST['test_phone'] ?? ''));
        $pn = sms_normalize_ghana_phone($phoneRaw);
        if ($pn === null) {
            flash('admin_notice', 'Enter a valid Ghana mobile number (e.g. 0241234567).');
            redirect('settings.php');
        }
        $msg = 'Mavis test OTP. Your code: %otp_code% — If you received this, Arkesel OTP is configured correctly.';
        $result = arkesel_otp_generate($pdo, $pn, $msg);
        if ($result['ok']) {
            $del = $result['delivery'] ?? 'otp_api';
            flash(
                'admin_notice',
                $del === 'sms_fallback'
                    ? 'Test SMS sent via standard SMS API (managed OTP endpoint failed first; message includes a generated code). Check your phone.'
                    : 'Test submitted successfully. Check the phone for the OTP SMS (may take a minute).'
            );
        } else {
            flash('admin_notice', 'OTP test failed: ' . ($result['error'] ?? 'unknown'));
        }
        redirect('settings.php');
    }

    $keys = [
        'arkasel_api_key',
        'arkasel_sender_id',
        'arkasel_api_url',
        'arkasel_otp_generate_url',
        'arkasel_otp_expiry',
        'arkasel_otp_length',
    ];
    foreach ($keys as $key) {
        $val = trim((string)($_POST[$key] ?? ''));
        set_setting($pdo, $key, $val);
    }
    set_setting($pdo, 'arkasel_otp_disable_sms_fallback', isset($_POST['arkasel_otp_disable_sms_fallback']) ? '1' : '');
    log_admin_action($pdo, 'settings_update', 'app_settings', null, ['keys' => array_merge($keys, ['arkasel_otp_disable_sms_fallback'])]);
    flash('admin_notice', 'Settings saved.');
    redirect('settings.php');
}

$values = [
    'arkasel_api_key' => get_setting($pdo, 'arkasel_api_key'),
    'arkasel_sender_id' => get_setting($pdo, 'arkasel_sender_id', 'MavisHub'),
    'arkasel_api_url' => get_setting($pdo, 'arkasel_api_url', 'https://sms.arkesel.com/sms/api'),
    'arkasel_otp_generate_url' => get_setting($pdo, 'arkasel_otp_generate_url', 'https://sms.arkesel.com/api/otp/generate'),
    'arkasel_otp_expiry' => get_setting($pdo, 'arkasel_otp_expiry', '5'),
    'arkasel_otp_length' => get_setting($pdo, 'arkasel_otp_length', '6'),
    'arkasel_otp_disable_sms_fallback' => get_setting($pdo, 'arkasel_otp_disable_sms_fallback'),
];
?>
<?php render_layout_start('SMS / API Settings', 'settings'); ?>
<div class="max-w-3xl mx-auto">
  <h1 class="text-3xl font-black text-slate-900">Arkesel SMS &amp; OTP</h1>
  <p class="text-slate-500 mt-1 text-sm">Configure outbound SMS and OTP for staff onboarding. Keys are stored locally in SQLite.</p>
  <?php if ($notice): ?><div class="mt-4 p-4  bg-emerald-50 text-emerald-800 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <div class="mt-6 p-4 bg-amber-50 border border-amber-200 text-amber-950 text-sm space-y-2">
    <p class="font-semibold">OTP requires your <strong>Main SMS API key</strong></p>
    <p class="text-amber-900/90">Sub-keys (“multiple API keys”) often return <strong>Invalid key</strong> for OTP — paste the Main SMS key from the Arkesel dashboard.</p>
    <p class="text-amber-900/90">If the <strong>managed OTP API</strong> returns errors (e.g. code 1007), this app automatically retries using the <strong>standard SMS API</strong> with the same message and a generated code — the same route most integrations use when sending SMS.</p>
  </div>

  <form method="post" class="mt-8 bg-white  border border-slate-200 p-6 md:p-8 space-y-5">
    <input type="hidden" name="settings_action" value="save">
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">API key (Main SMS)</span>
      <input name="arkasel_api_key" type="password" autocomplete="off" value="<?=h($values['arkasel_api_key'])?>" class="mt-1 w-full  border border-slate-200 p-3 font-mono text-sm" placeholder="Paste Main SMS API key from Arkesel dashboard">
      <span class="text-xs text-slate-500 mt-1 block">Plain SMS uses <code class="bg-slate-100 px-1">Authorization: Bearer</code>. OTP uses <code class="bg-slate-100 px-1">api-key</code> (same key value); if that returns 401, the code retries with Bearer.</span>
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">Sender ID</span>
      <input name="arkasel_sender_id" value="<?=h($values['arkasel_sender_id'])?>" class="mt-1 w-full  border border-slate-200 p-3" maxlength="11" required>
      <span class="text-xs text-slate-500 mt-1 block">Max 11 characters (Arkesel requirement).</span>
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">SMS API URL</span>
      <input name="arkasel_api_url" value="<?=h($values['arkasel_api_url'])?>" class="mt-1 w-full  border border-slate-200 p-3 font-mono text-sm">
    </label>
    <label class="block">
      <span class="text-sm font-semibold text-slate-700">OTP generate URL</span>
      <input name="arkasel_otp_generate_url" value="<?=h($values['arkasel_otp_generate_url'])?>" class="mt-1 w-full  border border-slate-200 p-3 font-mono text-sm">
      <span class="text-xs text-slate-500 mt-1 block">POST JSON with <code class="bg-slate-100 px-1">number</code>, <code class="bg-slate-100 px-1">sender_id</code>, <code class="bg-slate-100 px-1">expiry</code>, <code class="bg-slate-100 px-1">length</code>, <code class="bg-slate-100 px-1">medium</code>, <code class="bg-slate-100 px-1">type</code>, and message containing <code class="bg-slate-100 px-1">%otp_code%</code>.</span>
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">OTP expiry (minutes)</span>
        <input name="arkasel_otp_expiry" type="number" min="1" max="10" value="<?=h($values['arkasel_otp_expiry'])?>" class="mt-1 w-full border border-slate-200 p-3">
      </label>
      <label class="block">
        <span class="text-sm font-semibold text-slate-700">OTP length (digits)</span>
        <input name="arkasel_otp_length" type="number" min="6" max="15" value="<?=h($values['arkasel_otp_length'])?>" class="mt-1 w-full border border-slate-200 p-3">
      </label>
    </div>
    <label class="flex items-start gap-3 cursor-pointer">
      <input type="checkbox" name="arkasel_otp_disable_sms_fallback" value="1" class="mt-1" <?= trim((string)$values['arkasel_otp_disable_sms_fallback']) === '1' ? 'checked' : '' ?>>
      <span class="text-sm text-slate-700"><strong>Disable SMS fallback</strong> — only use Arkesel’s OTP generate endpoint (no automatic retry via plain SMS).</span>
    </label>
    <button type="submit" class="w-full md:w-auto px-6 py-3  bg-slate-950 text-white font-bold hover:bg-slate-800">Save settings</button>
  </form>

  <div class="mt-8 bg-white border border-slate-200 p-6 md:p-8">
    <h2 class="font-bold text-lg text-slate-900">Test OTP delivery</h2>
    <p class="text-sm text-slate-500 mt-1">Sends a real OTP SMS via Arkesel using your saved API key and URLs (save settings first if you changed them).</p>
    <form method="post" class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-end">
      <input type="hidden" name="settings_action" value="test_otp">
      <label class="block flex-1 min-w-0">
        <span class="text-sm font-semibold text-slate-700">Ghana mobile number</span>
        <input name="test_phone" type="tel" required placeholder="0241234567" class="mt-1 w-full border border-slate-200 p-3" autocomplete="off">
      </label>
      <button type="submit" class="px-6 py-3 bg-emerald-700 text-white font-bold hover:bg-emerald-800 shrink-0">Send test OTP</button>
    </form>
  </div>
</div>
<?php render_layout_end(); ?>
