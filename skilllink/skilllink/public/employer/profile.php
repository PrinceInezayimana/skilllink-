<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employer') {
    header("Location: ../login.php"); exit();
}
$employer_user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT users.*, employers.id AS emp_id, employers.company_name, employers.description AS company_desc, employers.company_logo, employers.profile_pic AS emp_pic FROM users JOIN employers ON users.id=employers.user_id WHERE users.id=?");
$stmt->bind_param("i", $employer_user_id); $stmt->execute();
$profile = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$profile) die("Employer profile not found.");

$success = $error = "";
if (isset($_POST['update_profile'])) {
    $company_name = sanitize($_POST['company_name'] ?? '');
    $description  = sanitize($_POST['description']  ?? '');
    $new_name     = sanitize($_POST['name']          ?? '');

    $profile_pic = $profile['emp_pic'] ?? null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = 'avatar_'.uniqid().'.'.$ext;
            $dir   = '../uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir.$fname)) {
                if ($profile_pic && file_exists($dir.$profile_pic)) @unlink($dir.$profile_pic);
                $profile_pic = $fname;
            } else { $error = "Failed to upload profile picture."; }
        } else { $error = "Invalid image type for profile picture."; }
    }

    $company_logo = $profile['company_logo'];
    if (!$error && isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $lname = 'logo_'.uniqid().'.'.$ext;
            $ldir  = '../uploads/logos/';
            if (!is_dir($ldir)) mkdir($ldir, 0755, true);
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $ldir.$lname)) {
                if ($company_logo && file_exists($ldir.$company_logo)) @unlink($ldir.$company_logo);
                $company_logo = $lname;
            } else { $error = "Failed to upload company logo."; }
        } else { $error = "Invalid image type for company logo."; }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE employers SET company_name=?, description=?, company_logo=?, profile_pic=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $company_name, $description, $company_logo, $profile_pic, $employer_user_id); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->bind_param("si", $new_name, $employer_user_id); $stmt->execute(); $stmt->close();
        $success = "Profile updated successfully!";
        $profile['company_name']=$company_name; $profile['company_desc']=$description;
        $profile['company_logo']=$company_logo; $profile['emp_pic']=$profile_pic; $profile['name']=$new_name;
    }
}

$nav_user_id=$employer_user_id; $nav_role='employer'; $nav_name=$profile['name'];
$nav_pic=$profile['emp_pic']??null; $nav_base='../'; $nav_active='profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Company Profile — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap-sm">
  <h1 class="sl-page-title sl-anim sl-d0">Company Profile</h1>
  <p class="sl-page-sub sl-anim sl-d0">Update your information visible to students browsing jobs.</p>
  <?php if($success): ?><div class="sl-alert sl-alert-ok sl-anim">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="sl-alert sl-alert-err sl-anim">✕ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- Personal avatar -->
    <div class="sl-card sl-anim sl-d1" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Your Profile Picture</h2>
        <label class="sl-upload-area" for="profile_pic_input">
          <img id="avatar-preview" src="<?= avatar_url($profile['emp_pic']??null,$profile['name'],'../') ?>"
               alt="avatar" class="sl-upload-preview">
          <div class="sl-upload-info"><p>Click to upload your photo</p><span>JPG, PNG, WEBP · Personal avatar</span></div>
          <input type="file" id="profile_pic_input" name="profile_pic" accept=".jpg,.jpeg,.png,.gif,.webp">
        </label>
      </div>
    </div>

    <!-- Basic info -->
    <div class="sl-card sl-anim sl-d2" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Basic Information</h2>
        <div class="sl-form-group">
          <input class="sl-input" type="text" name="name" id="e-name" placeholder=" " required value="<?= htmlspecialchars($profile['name']??'') ?>">
          <label class="floating" for="e-name">Your Full Name</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        </div>
        <div class="sl-form-group" style="margin-bottom:.75rem">
          <input class="sl-input" type="email" value="<?= htmlspecialchars($profile['email']??'') ?>" disabled style="opacity:.45;cursor:not-allowed" placeholder=" ">
          <label class="floating" style="top:-2px;font-size:.72rem;color:var(--text-muted)">Email Address</label>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted);margin-bottom:1rem">Email cannot be changed here.</p>
        <a href="../change_password.php" class="sl-btn-ghost">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Change Password
        </a>
      </div>
    </div>

    <!-- Company details -->
    <div class="sl-card sl-anim sl-d3" style="margin-bottom:1.5rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Company Details</h2>
        <div class="sl-form-group">
          <input class="sl-input" type="text" name="company_name" id="e-co" placeholder=" " required value="<?= htmlspecialchars($profile['company_name']??'') ?>">
          <label class="floating" for="e-co">Company Name</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
        </div>
        <div class="sl-form-group">
          <textarea class="sl-textarea" name="description" id="e-desc" placeholder=" "><?= htmlspecialchars($profile['company_desc']??'') ?></textarea>
          <label class="floating" for="e-desc">About the Company</label>
        </div>
        <label class="sl-label">Company Logo <span style="font-weight:400;color:var(--text-muted)">(leave blank to keep current)</span></label>
        <label class="sl-upload-area" for="logo_input" style="margin-top:.5rem">
          <?php if($profile['company_logo']): ?>
            <img id="logo-preview" src="../uploads/logos/<?= htmlspecialchars($profile['company_logo']) ?>"
                 alt="logo" class="sl-upload-preview sq" style="object-fit:contain;background:rgba(255,255,255,0.05)">
          <?php else: ?>
            <div id="logo-preview" style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:1.5rem;flex-shrink:0">🏢</div>
          <?php endif; ?>
          <div class="sl-upload-info"><p>Upload company logo</p><span>JPG, PNG, GIF · Shown on job listings</span></div>
          <input type="file" id="logo_input" name="company_logo" accept=".jpg,.jpeg,.png,.gif">
        </label>
      </div>
    </div>

    <div class="sl-anim sl-d4" style="display:flex;align-items:center;gap:1rem">
      <button type="submit" name="update_profile" class="sl-btn">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
      <a href="dashboard.php" class="sl-btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<script>
function previewImg(inputId, previewId) {
    document.getElementById(inputId).addEventListener('change', function(e) {
        const f=e.target.files[0]; if(!f) return;
        const r=new FileReader(); r.onload=ev=>{ const el=document.getElementById(previewId); if(el.tagName==='IMG') el.src=ev.target.result; }; r.readAsDataURL(f);
    });
}
previewImg('profile_pic_input','avatar-preview'); previewImg('logo_input','logo-preview');
document.querySelectorAll('.sl-form-group .sl-input, .sl-form-group .sl-textarea').forEach(inp => {
    const ic = inp.closest('.sl-form-group').querySelector('.sl-input-icon');
    inp.addEventListener('focus', () => { if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur',  () => { if(ic) ic.style.color=''; });
});
</script>
</body>
</html>
