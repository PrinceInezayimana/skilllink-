<?php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

$success = $error = "";

if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $user['password']))   $error = "Current password is incorrect.";
    elseif (strlen($new_pw) < 8)                         $error = "New password must be at least 8 characters.";
    elseif ($new_pw !== $confirm)                         $error = "New passwords do not match.";
    else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id); $stmt->execute(); $stmt->close();
        $success = "Password changed successfully!";
    }
}

$nav_user_id = $user_id; $nav_role = $role; $nav_name = $user['name'];
$nav_pic = $user['profile_pic'] ?? null; $nav_base = './'; $nav_active = '';
$nav_link_prefix = ($role==='student') ? 'student/' : (($role==='employer') ? 'employer/' : (($role==='admin') ? 'admin/' : ''));
if ($role === 'employer') {
    $ep = $conn->prepare("SELECT profile_pic FROM employers WHERE user_id=?");
    $ep->bind_param("i", $user_id); $ep->execute();
    $er = $ep->get_result()->fetch_assoc(); $ep->close();
    $nav_pic = $er['profile_pic'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Change Password — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include 'partials/navbar.php'; ?>
<div class="sl-page-wrap-sm">
  <h1 class="sl-page-title sl-anim sl-d0">Change Password</h1>
  <p class="sl-page-sub sl-anim sl-d0">Choose a strong password to keep your account secure.</p>

  <?php if($success): ?><div class="sl-alert sl-alert-ok sl-anim">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="sl-alert sl-alert-err sl-anim">✕ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST">
    <div class="sl-card sl-anim sl-d1" style="margin-bottom:1.5rem">
      <div class="sl-card-body">
        <h2 class="sl-card-title">Update Password</h2>

        <div class="sl-form-group">
          <input class="sl-input" type="password" name="current_password" id="cur_pw" placeholder=" " required autocomplete="current-password">
          <label class="floating" for="cur_pw">Current Password</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        </div>

        <div class="sl-form-group">
          <input class="sl-input" type="password" name="new_password" id="new_pw" placeholder=" " required autocomplete="new-password" oninput="checkStrength(this.value)">
          <label class="floating" for="new_pw">New Password</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <div class="sl-strength-bar"><div class="sl-strength-fill" id="str-fill" style="width:0%"></div></div>
          <div class="sl-strength-label" id="str-label">Enter a password</div>
          <ul class="sl-req-list">
            <li id="r-len">At least 8 characters</li>
            <li id="r-upper">One uppercase letter</li>
            <li id="r-num">One number</li>
            <li id="r-special">One special character</li>
          </ul>
        </div>

        <div class="sl-form-group" style="margin-bottom:0">
          <input class="sl-input" type="password" name="confirm_password" id="confirm_pw" placeholder=" " required autocomplete="new-password" oninput="checkMatch()">
          <label class="floating" for="confirm_pw">Confirm New Password</label>
          <span class="sl-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <p id="match-msg" style="font-size:.72rem;margin-top:4px;display:none"></p>
        </div>
      </div>
    </div>

    <div class="sl-anim sl-d2" style="display:flex;align-items:center;gap:1rem">
      <button type="submit" name="change_password" class="sl-btn">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Update Password
      </button>
      <?php
        $back = './';
        if ($role==='student') $back='student/profile.php';
        elseif ($role==='employer') $back='employer/profile.php';
        elseif ($role==='admin') $back='admin/profile.php';
      ?>
      <a href="<?= htmlspecialchars($back) ?>" class="sl-btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<script>
function checkStrength(val){
  const fill=document.getElementById('str-fill'),label=document.getElementById('str-label');
  const checks={'r-len':val.length>=8,'r-upper':/[A-Z]/.test(val),'r-num':/[0-9]/.test(val),'r-special':/[^A-Za-z0-9]/.test(val)};
  let score=Object.values(checks).filter(Boolean).length;
  const colors=['#EF4444','#F59E0B','#3B82F6','#10B981'],labels=['Too weak','Fair','Good','Strong'],widths=['25%','50%','75%','100%'];
  fill.style.width=score?widths[score-1]:'0%'; fill.style.background=score?colors[score-1]:'';
  label.textContent=score?labels[score-1]:'Enter a password'; label.style.color=score?colors[score-1]:'var(--text-muted,#666)';
  for(const[id,met] of Object.entries(checks)) document.getElementById(id).classList.toggle('met',met);
  checkMatch();
}
function checkMatch(){
  const n=document.getElementById('new_pw').value,c=document.getElementById('confirm_pw').value,m=document.getElementById('match-msg');
  if(!c){m.style.display='none';return;} m.style.display='block';
  if(n===c){m.textContent='✓ Passwords match';m.style.color='#10b981';}
  else{m.textContent='✗ Passwords do not match';m.style.color='#ef4444';}
}
document.querySelectorAll('.sl-form-group .sl-input').forEach(inp=>{
  const ic=inp.closest('.sl-form-group').querySelector('.sl-input-icon');
  inp.addEventListener('focus',()=>{if(ic)ic.style.color='var(--input-focus,#e05030)';});
  inp.addEventListener('blur', ()=>{if(ic)ic.style.color='';});
});
</script>
</body>
</html>
