<?php
require 'config.php';
require 'pdf.php';
$errors = [];
$old = [];
$languageOptions = ['FANTE', 'AHANTA', 'NZEMA', 'ENGLISH', 'FRENCH', 'EWE', 'GA', 'DAGOMBA', 'WASA', 'TWI', 'HAUSA', 'DANGME', 'GONJA', 'KUSAAL', 'SISSALI', 'KASEM', 'DAGAARE'];

function validate_required(array $data, string $field, string $label, array &$errors): void {
    if (trim($data[$field] ?? '') === '') $errors[$field] = "$label is required.";
}

function normalize_post_value($value) {
    if (is_array($value)) {
        $normalized = [];
        foreach ($value as $item) {
            $normalized[] = normalize_post_value($item);
        }
        return $normalized;
    }
    return trim((string)$value);
}

function validate_required_selection(array $data, string $field, string $label, array &$errors): void {
    $value = $data[$field] ?? [];
    if (!is_array($value) || count($value) === 0) {
        $errors[$field] = "$label is required.";
    }
}

function has_selected_language(array $old, string $language): bool {
    $langs = $old['languages'] ?? [];
    return is_array($langs) && in_array($language, $langs, true);
}

function normalize_ghana_card(string $value): ?string {
    $compact = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($value)));
    if (preg_match('/^GHA\d{9}\d$/', $compact) !== 1) {
        return null;
    }

    return 'GHA-' . substr($compact, 3, 9) . '-' . substr($compact, 12, 1);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [];
    foreach ($_POST as $key => $value) {
        $old[$key] = normalize_post_value($value);
    }
    foreach (['firstname', 'surname', 'place_of_birth', 'branch', 'voter_id_no', 'membership_id', 'positions_held', 'profession', 'proposer_name', 'proposer_party_id'] as $field) {
        if (isset($old[$field]) && is_string($old[$field])) {
            $old[$field] = strtoupper($old[$field]);
        }
    }
    $selectedLanguages = [];
    if (isset($old['languages']) && is_array($old['languages'])) {
        foreach ($old['languages'] as $lang) {
            $lang = strtoupper(trim((string)$lang));
            if ($lang !== '') {
                $selectedLanguages[] = $lang;
            }
        }
        $selectedLanguages = array_values(array_unique($selectedLanguages));
    }
    $old['languages'] = $selectedLanguages;
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
    validate_required_selection($old, 'languages', 'Languages', $errors);
    validate_required($old, 'profession', 'Profession', $errors);
    validate_required($old, 'proposer_name', 'Proposer name', $errors);
    validate_required($old, 'proposer_party_id', 'Proposer party ID', $errors);
    validate_required($old, 'proposer_phone_no', 'Proposer phone number', $errors);
    validate_required($old, 'membership_id', 'Membership ID', $errors);

    if (!isset($errors['phone_no']) && !preg_match('/^(\+233|0)[235]\d{8}$/', $old['phone_no'])) $errors['phone_no'] = 'Enter a valid Ghana phone number.';
    if (!isset($errors['proposer_phone_no']) && !preg_match('/^(\+233|0)[235]\d{8}$/', $old['proposer_phone_no'])) $errors['proposer_phone_no'] = 'Enter a valid proposer phone number.';
    if (!isset($errors['year_joined']) && !preg_match('/^\d{4}$/', $old['year_joined'])) $errors['year_joined'] = 'Year joined must be a 4-digit year.';
    if (!isset($errors['voter_id_no']) && !preg_match('/^[A-Z0-9]{8,15}$/i', $old['voter_id_no'])) $errors['voter_id_no'] = 'Voter ID format should be letters/numbers only (8-15 chars).';
    if (!isset($errors['membership_id']) && !preg_match('/^[A-Z0-9]{10,}$/i', $old['membership_id'])) $errors['membership_id'] = 'Membership ID must be at least 10 letters/numbers.';
    if (!isset($errors['ghana_card_no'])) {
        $normalizedGhanaCard = normalize_ghana_card($old['ghana_card_no']);
        if ($normalizedGhanaCard === null) {
            $errors['ghana_card_no'] = 'Ghana Card format should be GHA-123456789-1.';
        } else {
            $old['ghana_card_no'] = $normalizedGhanaCard;
        }
    }
    if (($old['date_of_birth'] ?? '') && strtotime($old['date_of_birth']) > strtotime('-15 years')) $errors['date_of_birth'] = 'Applicant must be at least 15 years old.';
    $photoPath = null;
    $hasPhotoUpload = isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK;
    if ($hasPhotoUpload) {
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
                if ($hasPhotoUpload) {
                    $mime = mime_content_type($_FILES['photo']['tmp_name']);
                    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                    $photoName = 'photo_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                    if (!@move_uploaded_file($_FILES['photo']['tmp_name'], PHOTO_DIR . '/' . $photoName)) {
                        throw new RuntimeException('Unable to save uploaded photo.');
                    }
                    $photoPath = 'storage/photos/' . $photoName;
                }
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
                    implode(', ', $old['languages']),
                    $old['profession'],
                    '',
                    '',
                    '',
                    strtoupper($old['membership_id']),
                    $photoPath,
                    $createdAt
                ]);
                $id = (int)$pdo->lastInsertId();
                $member = $pdo->query('SELECT * FROM members WHERE id = ' . $id)->fetch();
                $pdfOverrides = [
                    'firstname' => $old['firstname'],
                    'surname' => $old['surname'],
                    'place_of_birth' => $old['place_of_birth'],
                    'date_of_birth' => $old['date_of_birth'],
                    'branch' => $old['branch'],
                    'phone_no' => $old['phone_no'],
                    'year_joined' => $old['year_joined'],
                    'voter_id_no' => strtoupper($old['voter_id_no']),
                    'ghana_card_no' => strtoupper($old['ghana_card_no']),
                    'positions_held' => $old['positions_held'],
                    'languages' => implode(', ', $old['languages']),
                    'profession' => $old['profession'],
                    'proposer_name' => $old['proposer_name'],
                    'proposer_party_id' => strtoupper($old['proposer_party_id']),
                    'proposer_phone_no' => $old['proposer_phone_no'],
                    'membership_id' => strtoupper($old['membership_id']),
                    'created_at' => $createdAt,
                ];
                $_SESSION['pdf_overrides'][$id] = $pdfOverrides;
                save_member_pdf_payload($id, $pdfOverrides);
                try {
                    $pdfPath = create_member_pdf($member, $pdfOverrides);
                    $update = $pdo->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
                    $update->execute([$pdfPath, $id]);
                } catch (Throwable $pdfError) {
                    error_log('PDF generation failed for member ' . $id . ': ' . $pdfError->getMessage());
                }
                redirect('success.php?id=' . $id);
            }
        } catch (Throwable $e) {
            error_log('Registration submission failed: ' . $e->getMessage());
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                $errors['general'] = 'A registration already exists with one of the unique IDs, phone number, or membership ID.';
            } else {
                $errors['general'] = 'Submission failed due to a system issue. Please review your entries and try again. If it continues, contact admin.';
            }
        }
    }
}
function val($key, $old) { return h(is_array($old[$key] ?? null) ? '' : ($old[$key] ?? '')); }
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
      <div class="flex items-center gap-2 text-sm flex-wrap">
        <span id="stepBadge1" class="px-3 py-1 rounded-full bg-slate-950 text-white">Step 1</span>
        <span id="stepBadge2" class="px-3 py-1 rounded-full bg-slate-200 text-slate-600">Step 2</span>
        <span id="stepBadge3" class="px-3 py-1 rounded-full bg-slate-200 text-slate-600">Step 3</span>
      </div>
      <section id="step1"><h2 class="font-bold text-lg mb-4">Personal Details</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label>Firstname *<input name="firstname" value="<?=val('firstname',$old)?>" placeholder="Enter first name" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('firstname',$errors)?></label>
        <label>Surname *<input name="surname" value="<?=val('surname',$old)?>" placeholder="Enter surname" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('surname',$errors)?></label>
        <label>Place of birth *<input name="place_of_birth" value="<?=val('place_of_birth',$old)?>" placeholder="e.g. Takoradi" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('place_of_birth',$errors)?></label>
        <label>Date of birth *<input type="date" name="date_of_birth" value="<?=val('date_of_birth',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('date_of_birth',$errors)?></label>
        <label>Branch *<input name="branch" value="<?=val('branch',$old)?>" placeholder="Enter branch name" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('branch',$errors)?></label>
        <label>Phone no *<input name="phone_no" value="<?=val('phone_no',$old)?>" placeholder="0241234567" class="w-full mt-1 rounded-xl border p-3" required><?=err('phone_no',$errors)?></label>
      </div>
      <div class="mt-6">
        <button type="button" id="nextBtn1" class="w-full sm:w-auto px-8 py-3 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Next</button>
      </div>
      </section>
      <section id="step2" class="hidden"><h2 class="font-bold text-lg mb-4">Membership Details</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label>Year joined *<input name="year_joined" value="<?=val('year_joined',$old)?>" placeholder="2026" class="w-full mt-1 rounded-xl border p-3" required><?=err('year_joined',$errors)?></label>
        <label>Voters ID no *<input name="voter_id_no" value="<?=val('voter_id_no',$old)?>" placeholder="BC12345678" pattern="[A-Za-z0-9]{8,15}" title="Use 8-15 letters/numbers only." class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('voter_id_no',$errors)?></label>
        <label>Ghana card no *<input id="ghanaCardInput" name="ghana_card_no" value="<?=val('ghana_card_no',$old)?>" placeholder="GHA-123456789-1" pattern="GHA-[0-9]{9}-[0-9]" title="Use Ghana Card format: GHA-123456789-1" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('ghana_card_no',$errors)?></label>
        <label>Membership ID *<input name="membership_id" value="<?=val('membership_id',$old)?>" placeholder="K041503121" minlength="10" pattern="[A-Za-z0-9]{10,}" title="Use at least 10 letters/numbers." class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('membership_id',$errors)?></label>
        <label>Positions held *<input name="positions_held" value="<?=val('positions_held',$old)?>" placeholder="e.g. Branch Organizer" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('positions_held',$errors)?></label>
        <div class="sm:col-span-2">
          <p class="font-medium">Languages *</p>
          <?=err('languages',$errors)?>
          <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-2">
            <?php foreach($languageOptions as $language): ?>
              <label class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                <input type="checkbox" name="languages[]" value="<?=h($language)?>" <?=has_selected_language($old, $language) ? 'checked' : ''?>>
                <span><?=$language?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <label>Profession *<input name="profession" value="<?=val('profession',$old)?>" placeholder="Enter profession" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('profession',$errors)?></label>
      </div>
      <div class="mt-6 flex flex-col sm:flex-row gap-3">
        <button type="button" id="backBtn2" class="w-full sm:w-auto px-8 py-3 bg-white border rounded-xl font-bold">Back</button>
        <button type="button" id="nextBtn2" class="w-full sm:w-auto px-8 py-3 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Next</button>
      </div>
      </section>
      <section id="step3" class="hidden"><h2 class="font-bold text-lg mb-4">Proposer Information</h2><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <label>Proposer's name *<input name="proposer_name" value="<?=val('proposer_name',$old)?>" placeholder="Enter proposer's full name" class="js-uppercase w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('proposer_name',$errors)?></label>
        <label>Proposer's party ID *<input name="proposer_party_id" value="<?=val('proposer_party_id',$old)?>" placeholder="Enter proposer party ID" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('proposer_party_id',$errors)?></label>
        <label>Proposer's phone no *<input name="proposer_phone_no" value="<?=val('proposer_phone_no',$old)?>" placeholder="0241234567" class="w-full mt-1 rounded-xl border p-3" required><?=err('proposer_phone_no',$errors)?></label>
      </div>
      <div class="mt-6">
        <label class="block">Photo (optional)<input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" class="w-full mt-1 rounded-xl border p-3 bg-white"><?=err('photo',$errors)?></label>
        <p class="text-xs text-slate-500 mt-2">Accepted formats: JPG, PNG, WEBP. Max size: 3MB.</p>
        <img id="photoPreview" class="mt-3 w-36 h-36 rounded-xl object-cover border hidden" alt="Preview">
      </div>
      <div class="mt-6 flex flex-col sm:flex-row gap-3">
        <button type="button" id="backBtn3" class="w-full sm:w-auto px-8 py-3 bg-white border rounded-xl font-bold">Back</button>
        <button class="w-full sm:w-auto px-8 py-3 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Submit Registration</button>
      </div>
      </section>
    </form>
  </div>
</main>
<script>
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');
const nextBtn1 = document.getElementById('nextBtn1');
const nextBtn2 = document.getElementById('nextBtn2');
const backBtn2 = document.getElementById('backBtn2');
const backBtn3 = document.getElementById('backBtn3');
const stepBadge1 = document.getElementById('stepBadge1');
const stepBadge2 = document.getElementById('stepBadge2');
const stepBadge3 = document.getElementById('stepBadge3');
const photoInput = document.getElementById('photoInput');
const photoPreview = document.getElementById('photoPreview');
const ghanaCardInput = document.getElementById('ghanaCardInput');
const uppercaseFields = document.querySelectorAll('.js-uppercase');
const registrationForm = document.getElementById('registrationForm');
const DRAFT_KEY = 'mavis_registration_draft_v1';
const STEP_KEY = 'mavis_registration_step_v1';

function formatGhanaCard(value) {
  const cleaned = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  let remainder = cleaned;
  if (remainder.startsWith('GHA')) {
    remainder = remainder.slice(3);
  } else {
    remainder = remainder.replace(/[^0-9]/g, '');
  }

  const digits = remainder.replace(/[^0-9]/g, '').slice(0, 10);
  let formatted = 'GHA';
  if (digits.length > 0) {
    formatted += '-' + digits.slice(0, 9);
  }
  if (digits.length === 10) {
    formatted += '-' + digits.slice(9);
  }
  return formatted;
}

function setStep(step) {
  step1.classList.toggle('hidden', step !== 1);
  step2.classList.toggle('hidden', step !== 2);
  step3.classList.toggle('hidden', step !== 3);
  stepBadge1.className = step === 1 ? 'px-3 py-1 rounded-full bg-slate-950 text-white' : 'px-3 py-1 rounded-full bg-slate-200 text-slate-600';
  stepBadge2.className = step === 2 ? 'px-3 py-1 rounded-full bg-slate-950 text-white' : 'px-3 py-1 rounded-full bg-slate-200 text-slate-600';
  stepBadge3.className = step === 3 ? 'px-3 py-1 rounded-full bg-slate-950 text-white' : 'px-3 py-1 rounded-full bg-slate-200 text-slate-600';
  localStorage.setItem(STEP_KEY, String(step));
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function saveDraft() {
  const fields = registrationForm.querySelectorAll('input[name]');
  const draft = {};
  fields.forEach((field) => {
    if (field.type === 'file') return;
    if (field.type === 'checkbox') {
      if (!draft[field.name]) draft[field.name] = [];
      if (field.checked) draft[field.name].push(field.value);
      return;
    }
    draft[field.name] = field.value;
  });
  localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
}

function restoreDraft() {
  const rawDraft = localStorage.getItem(DRAFT_KEY);
  if (!rawDraft) return;
  try {
    const draft = JSON.parse(rawDraft);
    const fields = registrationForm.querySelectorAll('input[name]');
    fields.forEach((field) => {
      if (field.type === 'file') return;
      if (field.type === 'checkbox') {
        const savedList = draft[field.name];
        if (Array.isArray(savedList)) {
          field.checked = savedList.includes(field.value);
        }
        return;
      }
      if (field.value.trim() !== '') return;
      const savedValue = draft[field.name];
      if (typeof savedValue === 'string') {
        field.value = savedValue;
      }
    });
  } catch (error) {
    localStorage.removeItem(DRAFT_KEY);
  }
}

nextBtn1.addEventListener('click', () => {
  const requiredFields = step1.querySelectorAll('input[required]');
  for (const field of requiredFields) {
    if (!field.checkValidity()) {
      field.reportValidity();
      return;
    }
  }
  setStep(2);
});

nextBtn2.addEventListener('click', () => {
  const requiredFields = step2.querySelectorAll('input[required]');
  for (const field of requiredFields) {
    if (!field.checkValidity()) {
      field.reportValidity();
      return;
    }
  }
  const languageChecks = step2.querySelectorAll('input[name="languages[]"]:checked');
  if (languageChecks.length === 0) {
    alert('Please select at least one language.');
    return;
  }
  setStep(3);
});

backBtn2.addEventListener('click', () => setStep(1));
backBtn3.addEventListener('click', () => setStep(2));

if (ghanaCardInput) {
  ghanaCardInput.addEventListener('input', () => {
    ghanaCardInput.value = formatGhanaCard(ghanaCardInput.value);
  });
  ghanaCardInput.addEventListener('blur', () => {
    ghanaCardInput.value = formatGhanaCard(ghanaCardInput.value);
  });
  ghanaCardInput.value = formatGhanaCard(ghanaCardInput.value);
}

uppercaseFields.forEach((field) => {
  field.addEventListener('input', () => {
    field.value = field.value.toUpperCase();
  });
});

restoreDraft();
const savedStep = parseInt(localStorage.getItem(STEP_KEY) || '1', 10);
if ([1, 2, 3].includes(savedStep)) {
  setStep(savedStep);
}

registrationForm.querySelectorAll('input[name]').forEach((field) => {
  if (field.type === 'file') return;
  field.addEventListener('input', saveDraft);
  field.addEventListener('change', saveDraft);
});

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
