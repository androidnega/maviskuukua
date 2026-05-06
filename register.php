<?php
require 'layout.php';
require 'pdf.php';
$errors = [];
$old = [];

function validate_required(array $data, string $field, string $label, array &$errors): void {
    if (trim($data[$field] ?? '') === '') $errors[$field] = "$label is required.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_map('trim', $_POST);
    validate_required($old, 'first_name', 'First name', $errors);
    validate_required($old, 'last_name', 'Last name', $errors);
    validate_required($old, 'gender', 'Gender', $errors);
    validate_required($old, 'date_of_birth', 'Date of birth', $errors);
    validate_required($old, 'phone', 'Phone number', $errors);
    validate_required($old, 'community', 'Community', $errors);
    validate_required($old, 'voter_id', 'Voter ID', $errors);

    if (!isset($errors['phone']) && !preg_match('/^(\+233|0)[235]\d{8}$/', $old['phone'])) $errors['phone'] = 'Enter a valid Ghana phone number.';
    if (!isset($errors['voter_id']) && !preg_match('/^[A-Z0-9]{10,15}$/i', $old['voter_id'])) $errors['voter_id'] = 'Voter ID must be 10 to 15 letters or numbers.';
    if (($old['ghana_card'] ?? '') && !preg_match('/^GHA-\d{9}-\d$/i', $old['ghana_card'])) $errors['ghana_card'] = 'Ghana Card must match GHA-123456789-1.';
    if (($old['email'] ?? '') && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
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
            $stmt = $pdo->prepare('SELECT id FROM members WHERE phone = ? OR voter_id = ? OR ghana_card = ?');
            $stmt->execute([$old['phone'], $old['voter_id'], $old['ghana_card'] ?: '__empty__']);
            if ($stmt->fetch()) {
                $errors['general'] = 'A registration already exists with this phone, voter ID, or Ghana Card.';
            } else {
                $mime = mime_content_type($_FILES['photo']['tmp_name']);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                $photoName = 'photo_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], PHOTO_DIR . '/' . $photoName);
                $photoPath = 'storage/photos/' . $photoName;
                $membershipId = generate_membership_id();
                $createdAt = date('c');
                $insert = $pdo->prepare('INSERT INTO members (first_name,last_name,other_names,gender,date_of_birth,phone,email,community,electoral_area,voter_id,ghana_card,occupation,membership_id,photo_path,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $insert->execute([
                    $old['first_name'], $old['last_name'], $old['other_names'] ?? '', $old['gender'], $old['date_of_birth'], $old['phone'], $old['email'] ?? '',
                    $old['community'], $old['electoral_area'] ?? '', strtoupper($old['voter_id']), strtoupper($old['ghana_card'] ?? ''), $old['occupation'] ?? '', $membershipId, $photoPath, $createdAt
                ]);
                $id = (int)$pdo->lastInsertId();
                $member = $pdo->query('SELECT * FROM members WHERE id = ' . $id)->fetch();
                $pdfPath = create_member_pdf($member);
                $update = $pdo->prepare('UPDATE members SET pdf_path = ? WHERE id = ?');
                $update->execute([$pdfPath, $id]);
                redirect('success.php?id=' . $id);
            }
        } catch (Throwable $e) {
            $errors['general'] = 'Submission failed. Please try again.';
        }
    }
}
function val($key, $old) { return h($old[$key] ?? ''); }
function err($key, $errors) { return isset($errors[$key]) ? '<p class="text-red-600 text-sm mt-1">'.h($errors[$key]).'</p>' : ''; }
?>
<?php render_layout_start('Register', 'register'); ?>
<div class="max-w-4xl mx-auto">
  <div class="bg-white rounded-3xl shadow-sm border p-6 md:p-8">
    <h1 class="text-3xl font-black">Membership Registration</h1>
    <p class="text-slate-500 mt-2">Complete the form below. Fields marked required must be filled.</p>
    <?php if(isset($errors['general'])): ?><div class="mt-4 p-4 rounded-xl bg-red-50 text-red-700"><?=h($errors['general'])?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mt-8 space-y-8">
      <section><h2 class="font-bold text-lg mb-4">Personal Details</h2><div class="grid md:grid-cols-3 gap-4">
        <label>First name *<input name="first_name" value="<?=val('first_name',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('first_name',$errors)?></label>
        <label>Other names<input name="other_names" value="<?=val('other_names',$old)?>" class="w-full mt-1 rounded-xl border p-3"></label>
        <label>Last name *<input name="last_name" value="<?=val('last_name',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('last_name',$errors)?></label>
        <label>Gender *<select name="gender" class="w-full mt-1 rounded-xl border p-3" required><option value="">Select</option><option <?=val('gender',$old)==='Male'?'selected':''?>>Male</option><option <?=val('gender',$old)==='Female'?'selected':''?>>Female</option></select><?=err('gender',$errors)?></label>
        <label>Date of birth *<input type="date" name="date_of_birth" value="<?=val('date_of_birth',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('date_of_birth',$errors)?></label>
        <label>Occupation<input name="occupation" value="<?=val('occupation',$old)?>" class="w-full mt-1 rounded-xl border p-3"></label>
      </div></section>
      <section><h2 class="font-bold text-lg mb-4">Contact and Area Details</h2><div class="grid md:grid-cols-2 gap-4">
        <label>Phone *<input name="phone" value="<?=val('phone',$old)?>" placeholder="0241234567" class="w-full mt-1 rounded-xl border p-3" required><?=err('phone',$errors)?></label>
        <label>Email<input type="email" name="email" value="<?=val('email',$old)?>" class="w-full mt-1 rounded-xl border p-3"><?=err('email',$errors)?></label>
        <label>Community *<input name="community" value="<?=val('community',$old)?>" class="w-full mt-1 rounded-xl border p-3" required><?=err('community',$errors)?></label>
        <label>Electoral Area<input name="electoral_area" value="<?=val('electoral_area',$old)?>" class="w-full mt-1 rounded-xl border p-3"></label>
      </div></section>
      <section><h2 class="font-bold text-lg mb-4">Identification and Photo</h2><div class="grid md:grid-cols-2 gap-4">
        <label>Voter ID *<input name="voter_id" value="<?=val('voter_id',$old)?>" class="w-full mt-1 rounded-xl border p-3 uppercase" required><?=err('voter_id',$errors)?></label>
        <label>Ghana Card<input name="ghana_card" value="<?=val('ghana_card',$old)?>" placeholder="GHA-123456789-1" class="w-full mt-1 rounded-xl border p-3 uppercase"><?=err('ghana_card',$errors)?></label>
        <label class="md:col-span-2">Photo *<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full mt-1 rounded-xl border p-3 bg-white" required><?=err('photo',$errors)?></label>
      </div></section>
      <button class="w-full md:w-auto px-8 py-4 bg-slate-950 text-white rounded-xl font-bold hover:bg-slate-800">Submit Registration</button>
    </form>
  </div>
</div>
<?php render_layout_end(); ?>
