<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}
$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $student_id); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $student_id); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$student) {
    $ins = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
    $ins->bind_param("i", $student_id); $ins->execute(); $ins->close();
    $student = ['user_id'=>$student_id,'skills'=>null,'projects'=>null,'resume_link'=>null];
}

$success = $error = "";

if (isset($_POST['update_profile'])) {
    $new_name = sanitize($_POST['name'] ?? $user['name']);

    $profile_pic = $user['profile_pic'] ?? null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = 'avatar_' . uniqid() . '.' . $ext;
            $dir = '../uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $fname)) {
                if ($profile_pic && file_exists($dir . $profile_pic)) @unlink($dir . $profile_pic);
                $profile_pic = $fname;
            } else { $error = "Failed to upload profile picture."; }
        } else { $error = "Invalid image type."; }
    }

    $skills = array_filter(array_map('trim', explode(',', $_POST['skills'] ?? '')));
    $skills_json = json_encode(array_values($skills));

    $projects = [];
    if (!empty($_POST['project_title'])) {
        foreach ($_POST['project_title'] as $i => $title) {
            $link = $_POST['project_link'][$i] ?? '';
            if (trim($title) !== '') $projects[] = ['title'=>trim($title),'link'=>trim($link)];
        }
    }
    $projects_json = json_encode($projects);

    $resume_link = $student['resume_link'] ?? '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $rname = time().'_'.basename($_FILES['resume']['name']);
        $rdir = '../uploads/resumes/';
        if (!is_dir($rdir)) mkdir($rdir, 0755, true);
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $rdir.$rname)) {
            $resume_link = 'uploads/resumes/'.$rname;
        } else { $error = "Failed to upload resume."; }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE users SET name=?, profile_pic=? WHERE id=?");
        $stmt->bind_param("ssi", $new_name, $profile_pic, $student_id); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare("UPDATE students SET skills=?, projects=?, resume_link=? WHERE user_id=?");
        $stmt->bind_param("sssi", $skills_json, $projects_json, $resume_link, $student_id); $stmt->execute(); $stmt->close();
        $success = "Profile updated successfully!";
        $user['name'] = $new_name; $user['profile_pic'] = $profile_pic;
        $student['skills'] = $skills_json; $student['projects'] = $projects_json; $student['resume_link'] = $resume_link;
    }
}

$nav_user_id=$student_id; $nav_role='student'; $nav_name=$user['name'];
$nav_pic=$user['profile_pic']??null; $nav_base='../'; $nav_active='profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Edit Profile — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap-sm">
  <h1 class="sl-page-title sl-anim sl-d0">Edit Your Profile</h1>
  <p class="sl-page-sub sl-anim sl-d0">Keep your profile up to date to attract the best opportunities.</p>

  <?php if($success): ?><div class="sl-alert sl-alert-ok sl-anim">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="sl-alert sl-alert-err sl-anim">✕ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- Profile Picture -->
    <div class="sl-card sl-anim sl-d1" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Profile Picture</h2>
        <label class="sl-upload-area" for="profile_pic_input">
          <img id="avatar-preview" src="<?= avatar_url($user['profile_pic']??null,$user['name'],'../') ?>"
               alt="avatar" class="sl-upload-preview">
          <div class="sl-upload-info">
            <p>Click to upload a new photo</p>
            <span>JPG, PNG, WEBP or GIF · Max 5MB</span>
          </div>
          <input type="file" id="profile_pic_input" name="profile_pic" accept=".jpg,.jpeg,.png,.gif,.webp">
        </label>
      </div>
    </div>

    <!-- Basic Info -->
    <div class="sl-card sl-anim sl-d2" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Basic Information</h2>
        <div class="sl-form-group">
          <input class="sl-input" type="text" name="name" id="s-name" placeholder=" " required value="<?= htmlspecialchars($user['name']) ?>">
          <label class="floating" for="s-name">Full Name</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        </div>
        <div class="sl-form-group" style="margin-bottom:.75rem">
          <input class="sl-input" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.45;cursor:not-allowed" placeholder=" ">
          <label class="floating" style="top:-2px;font-size:.72rem;color:var(--text-muted)">Email Address</label>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted);margin-bottom:1rem">Email cannot be changed here.</p>
        <a href="../change_password.php" class="sl-btn-ghost">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Change Password
        </a>
      </div>
    </div>

    <!-- Skills -->
    <div class="sl-card sl-anim sl-d3" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Skills</h2>
        <?php $existing_skills = json_decode($student['skills']??'[]',true)??[]; ?>
        <div class="sl-form-group" style="margin-bottom:0">
          <input class="sl-input" type="text" name="skills" id="s-skills" placeholder=" "
                 value="<?= htmlspecialchars(implode(', ', $existing_skills)) ?>">
          <label class="floating" for="s-skills">Skills (comma separated)</label>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted);margin-top:8px">e.g. PHP, JavaScript, Python, MySQL</p>
      </div>
    </div>

    <!-- Projects -->
    <div class="sl-card sl-anim sl-d4" style="margin-bottom:1.25rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Projects</h2>
        <div id="projects-container">
          <?php $projects = json_decode($student['projects']??'[]',true)??[];
          if(empty($projects)) $projects=[['title'=>'','link'=>'']];
          foreach($projects as $proj): ?>
          <div class="proj-row" style="display:flex;gap:.5rem;margin-bottom:.75rem;align-items:center">
            <input class="sl-input-box" type="text" name="project_title[]" placeholder="Project Title"
                   value="<?= htmlspecialchars($proj['title']??'') ?>" style="flex:1">
            <input class="sl-input-box" type="text" name="project_link[]" placeholder="https://..."
                   value="<?= htmlspecialchars($proj['link']??'') ?>" style="flex:1">
            <button type="button" onclick="this.parentElement.remove()"
              style="width:32px;height:32px;border-radius:8px;background:var(--red-dim);color:#ef4444;border:none;cursor:pointer;font-size:1rem;flex-shrink:0;display:flex;align-items:center;justify-content:center">×</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addProject()" class="sl-btn-ghost" style="margin-top:.5rem">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Add Project
        </button>
      </div>
    </div>

    <!-- Resume -->
    <div class="sl-card sl-anim sl-d5" style="margin-bottom:1.5rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Resume</h2>
        <?php if(!empty($student['resume_link'])): ?>
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:var(--green-dim);border-radius:10px;margin-bottom:1rem;border:1px solid rgba(16,185,129,0.2)">
          <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span style="font-size:.85rem;color:var(--green);font-weight:500;flex:1">Resume uploaded</span>
          <a href="../<?= htmlspecialchars($student['resume_link']) ?>" target="_blank" class="sl-section-link">View</a>
        </div>
        <?php endif; ?>
        <label class="sl-label">Upload New Resume <span style="font-weight:400;color:var(--text-muted)">(PDF, DOC, DOCX)</span></label>
        <input class="sl-input-box" type="file" name="resume" accept=".pdf,.doc,.docx" style="padding:.5rem">
      </div>
    </div>

    <div class="sl-anim sl-d5" style="display:flex;align-items:center;gap:1rem">
      <button type="submit" name="update_profile" class="sl-btn">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
      <a href="dashboard.php" class="sl-btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<script>
function addProject() {
    const c = document.getElementById('projects-container');
    const d = document.createElement('div'); d.className='proj-row';
    d.style.cssText='display:flex;gap:.5rem;margin-bottom:.75rem;align-items:center';
    d.innerHTML='<input class="sl-input-box" type="text" name="project_title[]" placeholder="Project Title" style="flex:1"><input class="sl-input-box" type="text" name="project_link[]" placeholder="https://..." style="flex:1"><button type="button" onclick="this.parentElement.remove()" style="width:32px;height:32px;border-radius:8px;background:var(--red-dim);color:#ef4444;border:none;cursor:pointer;font-size:1rem;flex-shrink:0;display:flex;align-items:center;justify-content:center">×</button>';
    c.appendChild(d);
}
document.getElementById('profile_pic_input').addEventListener('change', function(e) {
    const f = e.target.files[0]; if(!f) return;
    const r = new FileReader(); r.onload = ev => document.getElementById('avatar-preview').src = ev.target.result; r.readAsDataURL(f);
});
document.querySelectorAll('.sl-form-group .sl-input').forEach(inp => {
    const ic = inp.closest('.sl-form-group').querySelector('.sl-input-icon');
    inp.addEventListener('focus', () => { if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur',  () => { if(ic) ic.style.color=''; });
});
</script>
</body>
</html>
