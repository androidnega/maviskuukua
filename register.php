<?php
require 'config.php';
require 'pdf.php';
$errors = [];
$old = [];

function validate_required(array $data, string $field, string $label, array &$errors): void {
    if (trim($data[$field] ?? '') === '') $errors[$field] = "$label is required.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_map('trim', $_POST);
    validate_required($old, 'firstname', 'Firstname', $errors);
    validate_required($old, 'surname', 'Surname', $errors);
    validate_required($old, 'place_of_birth', 'Place of birth', $errors);
    validate_required($old, 'date_of_birth', 'Date of birth', $errors);
    validate_required($old, 'branch', 'Branch', $errors);
    validate_required($old, 'phone_no', 'Phone number', $errors);
    validate_required($old, 'year_joined', 'Year joined', $errors);
    validate_required($old, 'voter_id_no', 'Voter ID number', $errors);
    validate_required($old, 'ghana_card_no', 'Ghana Card number', $errors);
    validate_required($old, 'positions_held', 'Positions held', $errors);
    validate_required($old, 'languages', 'Languages', $errors);
    validate_required($old, 'profession', 'Profession', $errors);
    validate_required($old, 'proposer_name', 'Proposer name', $errors);
    validate_required($old, 'proposer_party_id', 'Proposer party ID', $errors);
    validate_required($old, 'proposer_phone_no', 'Proposer phone number', $errors);
    validate_required($old, 'membership_id', 'Membership ID', $errors);

    if (!isset($errors['phone_no']) && !preg_match('/^(\+233|0)[235]\d{8}$/', $old['phone_no'])) $errors['phone_no'] = 'Enter a valid Ghana phone number.';
    if (!isset($errors['proposer_phone_no']) && !preg_match('/^(\+233|0)[235]\d{8}$/', $old['proposer_phone_no'])) $errors['proposer_phone_no'] = 'Enter a valid proposer phone number.';
    if (!isset($errors['year_joined']) && !preg_match('/^\d{4}$/', $old['year_joined'])) $errors['year_joined'] = 'Year joined must be a 4-digit year.';
    if (($old['date_of_birth'] ?? '') && strtotime($old['date_of_birth']) > strtotime('-15 years')) $errors['date_of_birth'] = 'Applicant must be at least 15 years old.';
    $photoPath = null;
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors['photo'] = 'Photo is required.';
    } else {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['photo']['tmp_name']);
        if (!isset($allowed[$mime])) {
            $errors['photo'] = 'Photo must be JPG, PNG, or WEBP.';
        } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
            $errors['photo'] = 'Photo must not be larger than 3MB.';
        }
    }

    if (!$errors) {
        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id FROM members WHERE phone_no = ? OR voter_id_no = ? OR ghana_card_no = ? OR membership_id = ?');
            $stmt->execute([$old['phone_no'], $old['voter_id_no'], $old['ghana_card_no'], strtoupper($old['membership_id'])]);
            if ($stmt->fetch()) {
                $errors['general'] = 'A registration already exists with this phone number, IDs, or membership ID.';
            } else {
                $mime = mime_content_type($_FILES['photo']['tmp_name']);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                $photoName = 'photo_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                if (!@move_uploaded_file($_FILES['photo']['tmp_name'], PHOTO_DIR . '/' . $photoName)) {
                    throw new RuntimeException('Unable to save uploaded photo.');
                }
                $photoPath = 'storage/photos/' . $photoName;
                $createdAt = date('c');
                $insert = $pdo->prepare('INSERT INTO members (firstname,surname,place_of_birth,date_of_birth,branch,phone_no,year_joined,voter_id_no,ghana_card_no,positions_held,languages,profession,proposer_name,proposer_party_id,proposer_phone_no,membership_id,photo_path,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $insert->execute([
                    $old['firstname'],
                    $old['surname'],
                    $old['place_of_birth'],
                    $old['date_of_birth'],
                    $old['branch'],
                    $old['phone_no'],
                    $old['year_joined'],
                    strtoupper($old['voter_id_no']),
                    strtoupper($old['ghana_card_no']),
                    $old['positions_held'],
                    $old['languages'],
                    $old['profession'],
                    $old['proposer_name'],
                    strtoupper($old['proposer_party_id']),
                    $old['proposer_phone_no'],
                    strtoupper($old['membership_id']),
                    $photoPath,
                    $createdAt
                ]);
                $id = (int)$pdo->lastInsertId();
                $member = $pdo->query('SELECT * FROM members WHERE id = ' . $id)->fetch();
                $pdfPath = create_member_pdf($member);
                $update = $pdo->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
                $update->execute([$pdfPath, $id]);
                redirect('success.php?id=' . $id);
            }
        } catch (Throwable $e) {
            $errors['general'] = 'Submission failed due to a system issue. Please review your entries and try again. If it continues, contact admin.';
        }
    }
}
function val($key, $old) { return h($old[$key] ?? ''); }
function err($key, $errors) { return isset($errors[$key]) ? '<p class="text-red-600 text-sm mt-1">'.h($errors[$key]).'</p>' : ''; }
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register</title><link rel="icon" type="image/svg+xml" href="assets/favicon.svg"><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 text-slate-900">
<main class="max-w-4xl mx-auto px-3 sm:px-4 py-6 sm:py-8">
  <a href="index.php" class="text-sm text-slate-600">← Home</a>
  <div class="bg-white rounded-3xl shadow-sm border p-4 sm:p-6 md:p-8 mt-4">
    <h1 class="text-2xl sm:text-3xl font-black">Membership Registration</h1>
    <p class="text-slate-500 mt-2">Complete the form below. Fields marked required must be filled.</p>
    <?php if(isset($errors['general'])): ?><div class="mt-4 p-4 rounded-xl bg-red-50 text-red-700 border border-red-200"><?=h($errors['general'])?></div><?php endif; ?>
    <?php if($errors && !isset($errors['general'])): ?><div class="mt-4 p-4 rounded-xl bg-amber-50 text-amber-800 border border-amber-200">Please correct the highlighted fields before continuing.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mt-6 sm:mt-8 space-y-6 sm:space-y-8" id="registrationForm">
      <div class="flex items-center gap-2 text-sm">
        <span id="stepBadge1" class="px-3 py-1 rounded-full bg-slate-950 text-white">Step 1</span>
        <span id="stepBadge2" class="px-3 py-1 rounded-full bg-slate-200 text-slate-600">Step 2</span>
      </div>
      <section id="step1"><h2 class="font-bold text-lg mb-4">Member Information</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label>Firstname *<input name="firstname" value="<?=val('firstname',$old)?>" placeholder="Enter first name" class="w-full mt-1 rounded-xl border p-3" required><?=err('firstname',$errors)?></label>
        <label>Surname *<input name="surname" value="<?=val('surname',$old)?>" placeholder="Enter surname" class="w-full mt-1 rounded-xl border p-3" required><?=err('surname',$errors)?></label>
        <label>Place of birth *<input name="place_of_birth" value="<?=val('place_of_birth',$old)?>" placeholder="e.g. Takoradi" class="w-full mt-1 rounded-xl border p-3" required><?=err('place_of_birth',$errors)?></label>
        <label>Date of birth *<input type="date" name="date_of_birth" value="<?=val('date_of_birth',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('date_of_birth',$errors)?></label>
        <label>Branch *<input name="branch" value="<?=val('branch',$old)?>" placeholder="Enter branch name" class="w-full mt-1 rounded-xl border p-3" required><?=err('branch',$errors)?></label>
        <label>Phone no *<input name="phone_no" value="<?=val('phone_no',$old)?>" placeholder="0241234567" class="w-full mt-1 rounded-xl border p-3" required><?=err('phone_no',$errors)?></label>
        <label>Year joined *<input name="year_joined" value="<?=val('year_joined',$old)?>" placeholder="2026" class="w-full mt-1 rounded-xl border p-3" required><?=err('year_joined',$errors)?></label>
        <label>Voters ID no *<input name="voter_id_no" value="<?=val('voter_id_no',$old)?>" placeholder="Enter voter ID number" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('voter_id_no',$errors)?></label>
        <label>Ghana card no *<input name="ghana_card_no" value="<?=val('ghana_card_no',$old)?>" placeholder="Enter Ghana card number" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('ghana_card_no',$errors)?></label>
        <label>Membership ID *<input name="membership_id" value="<?=val('membership_id',$old)?>" placeholder="e.g. MKB-2026-ABCD1234" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('membership_id',$errors)?></label>
        <label>Positions held *<input name="positions_held" value="<?=val('positions_held',$old)?>" placeholder="e.g. Branch Organizer" class="w-full mt-1 rounded-xl border p-3" required><?=err('positions_held',$errors)?></label>
        <label>Languages *<input name="languages" value="<?=val('languages',$old)?>" placeholder="e.g. English, Fante" class="w-full mt-1 rounded-xl border p-3" required><?=err('languages',$errors)?></label>
        <label>Profession *<input name="profession" value="<?=val('profession',$old)?>" placeholder="Enter profession" class="w-full mt-1 rounded-xl border p-3" required><?=err('profession',$errors)?></label>
      </div>
      <div class="mt-6">
        <button type="button" id="nextBtn" class="w-full sm:w-auto px-8 py-3 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Next</button>
      </div>
      </section>
      <section id="step2" class="hidden"><h2 class="font-bold text-lg mb-4">Proposer Information</h2><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <label>Proposer's name *<input name="proposer_name" value="<?=val('proposer_name',$old)?>" placeholder="Enter proposer's full name" class="w-full mt-1 rounded-xl border p-3" required><?=err('proposer_name',$errors)?></label>
        <label>Proposer's party ID *<input name="proposer_party_id" value="<?=val('proposer_party_id',$old)?>" placeholder="Enter proposer party ID" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('proposer_party_id',$errors)?></label>
        <label>Proposer's phone no *<input name="proposer_phone_no" value="<?=val('proposer_phone_no',$old)?>" placeholder="0241234567" class="w-full mt-1 rounded-xl border p-3" required><?=err('proposer_phone_no',$errors)?></label>
      </div>
      <div class="mt-6">
        <label class="block">Photo *<input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" class="w-full mt-1 rounded-xl border p-3 bg-white" required><?=err('photo',$errors)?></label>
        <p class="text-xs text-slate-500 mt-2">Accepted formats: JPG, PNG, WEBP. Max size: 3MB.</p>
        <img id="photoPreview" class="mt-3 w-36 h-36 rounded-xl object-cover border hidden" alt="Preview">
      </div>
      <div class="mt-6 flex flex-col sm:flex-row gap-3">
        <button type="button" id="backBtn" class="w-full sm:w-auto px-8 py-3 bg-white border rounded-xl font-bold">Back</button>
        <button class="w-full sm:w-auto px-8 py-3 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Submit Registration</button>
      </div>
      </section>
    </form>
  </div>
</main>
<script>
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const nextBtn = document.getElementById('nextBtn');
const backBtn = document.getElementById('backBtn');
const stepBadge1 = document.getElementById('stepBadge1');
const stepBadge2 = document.getElementById('stepBadge2');
const photoInput = document.getElementById('photoInput');
const photoPreview = document.getElementById('photoPreview');

function setStep(step) {
  const onStep1 = step === 1;
  step1.classList.toggle('hidden', !onStep1);
  step2.classList.toggle('hidden', onStep1);
  stepBadge1.className = onStep1 ? 'px-3 py-1 rounded-full bg-slate-950 text-white' : 'px-3 py-1 rounded-full bg-slate-200 text-slate-600';
  stepBadge2.className = onStep1 ? 'px-3 py-1 rounded-full bg-slate-200 text-slate-600' : 'px-3 py-1 rounded-full bg-slate-950 text-white';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

nextBtn.addEventListener('click', () => {
  const requiredFields = step1.querySelectorAll('input[required]');
  for (const field of requiredFields) {
    if (!field.checkValidity()) {
      field.reportValidity();
      return;
    }
  }
  setStep(2);
});

backBtn.addEventListener('click', () => setStep(1));

photoInput.addEventListener('change', (event) => {
  const file = event.target.files[0];
  if (!file) {
    photoPreview.classList.add('hidden');
    photoPreview.removeAttribute('src');
    return;
  }
  const reader = new FileReader();
  reader.onload = (e) => {
    photoPreview.src = e.target.result;
    photoPreview.classList.remove('hidden');
  };
  reader.readAsDataURL(file);
});
</script>
</body></html>
