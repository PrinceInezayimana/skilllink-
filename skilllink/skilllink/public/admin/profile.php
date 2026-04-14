<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
$admin_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $admin_id); $stmt->execute();
$admin = $stmt->get_result()->fetch_assoc(); $stmt->close();

$success = $error = "";
if (isset($_POST['update_profile'])) {
    $new_name = sanitize($_POST['name'] ?? $admin['name']);
    $profile_pic = $admin['profile_pic'] ?? null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','jfif'])) {
            $fname = 'avatar_'.uniqid().'.'.$ext;
            $dir = '../uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir.$fname)) {
                if ($profile_pic && file_exists($dir.$profile_pic)) @unlink($dir.$profile_pic);
                $profile_pic = $fname;
            } else { $error = "Failed to upload profile picture."; }
        } else { $error = "Invalid image type."; }
    }
    if (!$error) {
        $stmt = $conn->prepare("UPDATE users SET name=?, profile_pic=? WHERE id=?");
        $stmt->bind_param("ssi", $new_name, $profile_pic, $admin_id); $stmt->execute(); $stmt->close();
        $success = "Profile updated successfully!";
        $admin['name'] = $new_name; $admin['profile_pic'] = $profile_pic;
    }
}

$nav_user_id=$admin_id; $nav_role='admin'; $nav_name=$admin['name'];
$nav_pic=$admin['profile_pic']??null; $nav_base='../'; $nav_active='profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Profile — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap-sm">
  <h1 class="sl-page-title sl-anim sl-d0">Admin Profile</h1>
  <p class="sl-page-sub sl-anim sl-d0">Update your display name and profile picture.</p>

  <?php if($success): ?><div class="sl-alert sl-alert-ok sl-anim">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="sl-alert sl-alert-err sl-anim">✕ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <div class="sl-card sl-anim sl-d1" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Profile Picture</h2>
        <label class="sl-upload-area" for="profile_pic_input">
          <img id="avatar-preview"
               src="<?= htmlspecialchars(avatar_url($admin['profile_pic']??null,$admin['name'],'../')) ?>"
               alt="avatar" class="sl-upload-preview">
          <div class="sl-upload-info"><p>Click to upload a new photo</p><span>JPG, PNG, WEBP, JFIF or GIF · Max 5MB</span></div>
          <input type="file" id="profile_pic_input" name="profile_pic" accept=".jpg,.jpeg,.png,.gif,.web,.jfif">
        </label>
      </div>
    </div>

    <div class="sl-card sl-anim sl-d2" style="margin-bottom:1.5rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Account Details</h2>
        <div class="sl-form-group">
          <input class="sl-input" type="text" name="name" id="a-name" placeholder=" " required value="<?= htmlspecialchars($admin['name']) ?>">
          <label class="floating" for="a-name">Display Name</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        </div>
        <div class="sl-form-group" style="margin-bottom:.75rem">
          <input class="sl-input" type="email" value="<?= htmlspecialchars($admin['email']) ?>" disabled style="opacity:.45;cursor:not-allowed" placeholder=" ">
          <label class="floating" style="top:-2px;font-size:.72rem;color:var(--text-muted)">Email Address</label>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted);margin-bottom:1rem">Email cannot be changed here.</p>
        <a href="../change_password.php" class="sl-btn-ghost">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Change Password
        </a>
      </div>
    </div>

    <div class="sl-anim sl-d3" style="display:flex;align-items:center;gap:1rem">
      <button type="submit" name="update_profile" class="sl-btn">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
      <a href="dashboard.php" class="sl-btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<script>
document.getElementById('profile_pic_input').addEventListener('change', function(e) {
    const f=e.target.files[0]; if(!f) return;
    const r=new FileReader(); r.onload=ev=>document.getElementById('avatar-preview').src=ev.target.result; r.readAsDataURL(f);
});
document.querySelectorAll('.sl-form-group .sl-input').forEach(inp => {
    const ic = inp.closest('.sl-form-group').querySelector('.sl-input-icon');
    inp.addEventListener('focus', () => { if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur',  () => { if(ic) ic.style.color=''; });
});
</script>
</body>
</html>
