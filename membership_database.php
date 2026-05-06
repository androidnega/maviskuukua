<?php
require 'layout.php';
require_admin();
require_branch_executive_section();
$pdo = db();
$members = $pdo->query('SELECT * FROM members WHERE deleted_at IS NULL ORDER BY id DESC')->fetchAll();
$notice = flash('admin_notice');

function member_photo_url(array $member): ?string {
    $path = trim((string)($member['photo_path'] ?? ''));
    if ($path !== '' && is_file(BASE_DIR . '/' . ltrim($path, '/'))) {
        return $path;
    }
    $fallback = 'storage/photos/photo_' . (int)$member['id'] . '.jpg';
    if (is_file(BASE_DIR . '/' . ltrim($fallback, '/'))) {
        return $fallback;
    }
    return null;
}
?>
<?php render_layout_start('Branch Executive', 'branch_executive'); ?>
<div class="max-w-7xl mx-auto">
  <h1 class="text-3xl font-black">Branch Executive</h1>
  <p class="text-slate-500 mt-1">Minimal member directory with quick management actions.</p>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>
  <div class="mt-4 flex flex-wrap gap-2">
    <a href="export_executives.php?format=csv" class="px-3 py-2 rounded-lg border bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-csv mr-1 text-emerald-600"></i>Export CSV</a>
    <a href="export_executives.php?format=excel" class="px-3 py-2 rounded-lg border bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Export Excel</a>
    <a href="export_executives.php?format=pdf" class="px-3 py-2 rounded-lg border bg-white text-slate-700 text-sm font-semibold"><i class="fa-solid fa-file-pdf mr-1 text-emerald-600"></i>Export PDF</a>
  </div>

  <div class="mt-5">
    <input type="text" id="liveSearchInput" placeholder="Live search by name, Membership ID, or phone number" class="w-full md:w-2/3 rounded-xl border p-3">
    <p class="text-xs text-slate-500 mt-2">Results update as you type.</p>
  </div>

  <div class="bg-white rounded-3xl border mt-6 overflow-hidden hidden md:block">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="text-left p-4">Photo</th>
            <th class="text-left p-4">Member</th>
            <th class="text-left p-4">Membership ID</th>
            <th class="text-left p-4">Branch</th>
            <th class="text-left p-4">Action</th>
          </tr>
        </thead>
        <tbody id="membersTableBody">
          <?php foreach ($members as $m): ?>
            <?php $photoUrl = member_photo_url($m); ?>
            <tr class="border-t member-row" data-search="<?=h(strtolower($m['firstname'] . ' ' . $m['surname'] . ' ' . $m['membership_id'] . ' ' . $m['phone_no'] . ' ' . $m['branch']))?>">
              <td class="p-4">
                <?php if($photoUrl): ?>
                  <img src="<?=h($photoUrl)?>" alt="Photo" class="w-10 h-10 rounded-lg object-cover border">
                <?php else: ?>
                  <div class="w-10 h-10 rounded-lg border bg-slate-100 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
              </td>
              <td class="p-4">
                <p class="font-semibold"><?=h($m['firstname'] . ' ' . $m['surname'])?></p>
                <p class="text-slate-500 text-xs"><?=h($m['phone_no'])?></p>
              </td>
              <td class="p-4 font-bold"><?=h($m['membership_id'])?></td>
              <td class="p-4"><?=h($m['branch'])?></td>
              <td class="p-4">
                <div class="flex flex-wrap gap-2">
                  <a class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold" href="member.php?id=<?=$m['id']?>">View</a>
                  <?php if(!empty($m['pdf_path'])): ?><a class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 font-semibold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a><?php endif; ?>
                  <?php if (can_delete_members()): ?>
                  <form method="post" action="delete_member.php" class="inline" onsubmit="return confirm('Archive this member record? Files are kept for audit.');">
                    <input type="hidden" name="id" value="<?=$m['id']?>">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 font-semibold">Delete</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$members): ?>
            <tr><td colspan="5" class="p-8 text-center text-slate-500">No member records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="mobileCards" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3 md:hidden">
    <?php foreach ($members as $m): ?>
      <?php $photoUrl = member_photo_url($m); ?>
      <div class="member-card bg-white rounded-2xl border p-4" data-search="<?=h(strtolower($m['firstname'] . ' ' . $m['surname'] . ' ' . $m['membership_id'] . ' ' . $m['phone_no'] . ' ' . $m['branch']))?>">
        <div class="flex items-center gap-3">
          <?php if($photoUrl): ?>
            <img src="<?=h($photoUrl)?>" alt="Photo" class="w-12 h-12 rounded-lg object-cover border">
          <?php else: ?>
            <div class="w-12 h-12 rounded-lg border bg-slate-100 text-slate-500 flex items-center justify-center"><i class="fa-solid fa-user"></i></div>
          <?php endif; ?>
          <div>
            <p class="font-semibold leading-tight"><?=h($m['firstname'] . ' ' . $m['surname'])?></p>
            <p class="text-xs text-slate-500"><?=h($m['membership_id'])?></p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mt-3"><?=h($m['branch'])?> • <?=h($m['phone_no'])?></p>
        <div class="mt-3 flex gap-2">
          <a class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold text-sm" href="member.php?id=<?=$m['id']?>">View</a>
          <?php if(!empty($m['pdf_path'])): ?><a class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 font-semibold text-sm" target="_blank" href="view_pdf.php?id=<?=$m['id']?>">PDF</a><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$members): ?>
      <div class="bg-white rounded-2xl border p-6 text-center text-slate-500">No member records found.</div>
    <?php endif; ?>
  </div>
</div>
<script>
const liveSearchInput = document.getElementById('liveSearchInput');
const memberRows = Array.from(document.querySelectorAll('.member-row'));
const memberCards = Array.from(document.querySelectorAll('.member-card'));
const membersTableBody = document.getElementById('membersTableBody');
const mobileCards = document.getElementById('mobileCards');

function ensureEmptyState(show) {
  const existing = document.getElementById('live-empty-state');
  if (show && !existing) {
    const row = document.createElement('tr');
    row.id = 'live-empty-state';
    row.innerHTML = '<td colspan="7" class="p-8 text-center text-slate-500">No matching members found.</td>';
    membersTableBody.appendChild(row);
  }
  if (!show && existing) {
    existing.remove();
  }
}

liveSearchInput.addEventListener('input', () => {
  const term = liveSearchInput.value.trim().toLowerCase();
  let visibleCount = 0;
  memberRows.forEach((row) => {
    const match = term === '' || row.dataset.search.includes(term);
    row.style.display = match ? '' : 'none';
    if (match) visibleCount += 1;
  });
  memberCards.forEach((card) => {
    const match = term === '' || card.dataset.search.includes(term);
    card.style.display = match ? '' : 'none';
  });
  ensureEmptyState(visibleCount === 0);
  const noMobile = document.getElementById('live-empty-state-mobile');
  const mobileVisible = memberCards.some((card) => card.style.display !== 'none');
  if (!mobileVisible && !noMobile) {
    const empty = document.createElement('div');
    empty.id = 'live-empty-state-mobile';
    empty.className = 'bg-white rounded-2xl border p-6 text-center text-slate-500 md:hidden';
    empty.textContent = 'No matching members found.';
    mobileCards.appendChild(empty);
  }
  if (mobileVisible && noMobile) noMobile.remove();
});
</script>
<?php render_layout_end(); ?>
