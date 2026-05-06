<?php
require 'layout.php';
require_admin();
$pdo = db();
$members = $pdo->query('SELECT * FROM members ORDER BY id DESC')->fetchAll();
$notice = flash('admin_notice');
?>
<?php render_layout_start('Branch Executive', 'branch_executive'); ?>
<div class="max-w-7xl mx-auto">
  <h1 class="text-3xl font-black">Branch Executive</h1>
  <p class="text-slate-500 mt-1">Executive/member database with full management privileges and quick access to details.</p>
  <?php if ($notice): ?><div class="mt-4 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200"><?=h($notice)?></div><?php endif; ?>

  <div class="mt-5">
    <input type="text" id="liveSearchInput" placeholder="Live search by name, Membership ID, or phone number" class="w-full md:w-2/3 rounded-xl border p-3">
    <p class="text-xs text-slate-500 mt-2">Results update as you type.</p>
  </div>

  <div class="bg-white rounded-3xl border mt-6 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="text-left p-4">Member</th>
            <th class="text-left p-4">Membership ID</th>
            <th class="text-left p-4">Phone</th>
            <th class="text-left p-4">Branch</th>
            <th class="text-left p-4">Profession</th>
            <th class="text-left p-4">Joined</th>
            <th class="text-left p-4">Action</th>
          </tr>
        </thead>
        <tbody id="membersTableBody">
          <?php foreach ($members as $m): ?>
            <tr class="border-t member-row" data-search="<?=h(strtolower($m['firstname'] . ' ' . $m['surname'] . ' ' . $m['membership_id'] . ' ' . $m['phone_no'] . ' ' . $m['branch']))?>">
              <td class="p-4">
                <p class="font-bold"><?=h($m['firstname'] . ' ' . $m['surname'])?></p>
                <p class="text-slate-500 text-xs"><?=h($m['place_of_birth'])?></p>
              </td>
              <td class="p-4 font-bold"><?=h($m['membership_id'])?></td>
              <td class="p-4"><?=h($m['phone_no'])?></td>
              <td class="p-4"><?=h($m['branch'])?></td>
              <td class="p-4"><?=h($m['profession'])?></td>
              <td class="p-4"><?=h($m['year_joined'])?></td>
              <td class="p-4 space-x-2">
                <a class="text-emerald-700 font-bold" href="member.php?id=<?=$m['id']?>"><i class="fa-solid fa-circle-info mr-1"></i>View</a>
                <?php if(!empty($m['photo_path'])): ?><a class="text-pink-700 font-bold" target="_blank" href="<?=h($m['photo_path'])?>"><i class="fa-solid fa-image mr-1"></i>Photo</a><?php endif; ?>
                <?php if(!empty($m['pdf_path'])): ?><a class="text-blue-700 font-bold" target="_blank" href="view_pdf.php?id=<?=$m['id']?>"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</a><?php endif; ?>
                <form method="post" action="regenerate_pdf.php" class="inline">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="text-indigo-700 font-bold"><i class="fa-solid fa-rotate mr-1"></i>Regenerate</button>
                </form>
                <form method="post" action="delete_member.php" class="inline" onsubmit="return confirm('Delete this member and related files?');">
                  <input type="hidden" name="id" value="<?=$m['id']?>">
                  <button type="submit" class="text-red-700 font-bold"><i class="fa-solid fa-trash mr-1"></i>Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$members): ?>
            <tr><td colspan="7" class="p-8 text-center text-slate-500">No member records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
const liveSearchInput = document.getElementById('liveSearchInput');
const memberRows = Array.from(document.querySelectorAll('.member-row'));
const membersTableBody = document.getElementById('membersTableBody');

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
  ensureEmptyState(visibleCount === 0);
});
</script>
<?php render_layout_end(); ?>
