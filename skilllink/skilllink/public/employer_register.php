<?php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

if (isset($_POST['register'])) {
    $name         = sanitize($_POST['name']);
    $email        = sanitize($_POST['email']);
    $password     = hash_password($_POST['password']);
    $company_name = sanitize($_POST['company_name']);
    $company_desc = sanitize($_POST['company_desc']);
    $role         = 'employer';

    $company_logo = null;
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['company_logo']['tmp_name'];
        $fileName    = basename($_FILES['company_logo']['name']);
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt  = ['jpg','jpeg','png','gif'];
        if (in_array($fileExt, $allowedExt)) {
            $newFileName = uniqid('logo_').'.'.$fileExt;
            $uploadDir   = '../uploads/logos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($fileTmpPath, $uploadDir.$newFileName)) {
                $company_logo = $newFileName;
            } else {
                $_SESSION['emp_reg_error'] = "Failed to upload company logo.";
                header("Location: employer_register.php"); exit();
            }
        } else {
            $_SESSION['emp_reg_error'] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            header("Location: employer_register.php"); exit();
        }
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email); $check->execute(); $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['emp_reg_error'] = "Email already registered. Please login or use a different email.";
        $check->close(); header("Location: employer_register.php"); exit();
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id; $stmt->close();
        $emp_stmt = $conn->prepare("INSERT INTO employers(user_id,company_name,description,company_logo) VALUES(?,?,?,?)");
        $emp_stmt->bind_param("isss", $user_id, $company_name, $company_desc, $company_logo);
        if ($emp_stmt->execute()) {
            $emp_stmt->close();
            push_notification($conn, $user_id, "Welcome to SkillLink Rwanda! Your employer account is ready. Start posting jobs.", "employer/dashboard.php");
            $_SESSION['reg_success'] = "Registration successful! Please login.";
            header("Location: login.php"); exit();
        } else {
            $emp_stmt->close();
            $_SESSION['emp_reg_error'] = "Failed to create employer profile. Please try again.";
            header("Location: employer_register.php"); exit();
        }
    } else {
        $stmt->close();
        $_SESSION['emp_reg_error'] = "Registration failed. Please try again.";
        header("Location: employer_register.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Employer | SkillLink Rwanda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --bg: #111111; --card-bg: #1a1a1a;
        --panel-red-1: #c03520; --panel-red-2: #8b1a0a; --panel-red-3: #2a0805;
        --accent: #d44525; --accent-light: #e05530;
        --input-focus: #e05030; --label-focus: #e05030;
        --text: #f0f0f0; --text-muted: rgba(255,255,255,0.42);
        --input-line: rgba(255,255,255,0.18);
        --font: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
    }
    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    .wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .brand-link {
        position: fixed; top: 22px; left: 28px;
        font-weight: 800; font-size: 1.05rem;
        background: linear-gradient(135deg,#d44525,#e07755);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        text-decoration: none; letter-spacing: -0.3px; z-index: 100;
    }

    /* ── Card ── */
    .card {
        width: 100%; max-width: 520px;
        background: var(--card-bg);
        border-radius: 18px;
        overflow: hidden;
        box-shadow:
            0 0 0 1.5px rgba(190,55,20,0.55),
            0 0 50px rgba(180,50,15,0.22),
            0 24px 70px rgba(0,0,0,0.75);
        padding: 0;
    }

    /* Red gradient header strip */
    .card-header {
        background: linear-gradient(135deg, var(--panel-red-1) 0%, var(--panel-red-2) 50%, var(--panel-red-3) 100%);
        padding: 32px 40px 28px;
        position: relative;
        overflow: hidden;
    }
    .card-header::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,0.07);
        border-radius: 50%;
    }
    .card-header::after {
        content: '';
        position: absolute; bottom: -50px; right: 60px;
        width: 110px; height: 110px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }
    .card-header-brand { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.45); margin-bottom: 10px; }
    .card-header-title { font-size: 1.75rem; font-weight: 800; color: #fff; line-height: 1.15; letter-spacing: 0.02em; margin-bottom: 6px; }
    .card-header-sub { font-size: 0.8rem; color: rgba(255,255,255,0.65); }

    /* ── Form body ── */
    .card-body { padding: 32px 40px; }

    /* Alert */
    .alert { padding: 10px 14px; border-radius: 8px; font-size: 0.78rem; font-weight: 500; margin-bottom: 20px; }
    .alert-err { background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.35); color: #fca5a5; }
    .alert-ok  { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }

    /* Input groups — same floating label style */
    .input-group { position: relative; margin-bottom: 24px; }
    .input-group input, .input-group textarea {
        width: 100%; padding: 14px 34px 6px 0;
        background: transparent; border: none; border-bottom: 1.5px solid var(--input-line);
        color: var(--text); font-size: 0.88rem; font-family: var(--font); outline: none;
        transition: border-color 0.3s ease; resize: none;
    }
    .input-group textarea { padding-top: 16px; padding-bottom: 8px; min-height: 72px; }
    .input-group label {
        position: absolute; left: 0; top: 14px;
        font-size: 0.88rem; color: var(--text-muted);
        pointer-events: none;
        transition: top 0.25s ease, font-size 0.25s ease, color 0.25s ease;
    }
    .input-group input:focus ~ label,
    .input-group input:not(:placeholder-shown) ~ label,
    .input-group textarea:focus ~ label,
    .input-group textarea:not(:placeholder-shown) ~ label { top: -2px; font-size: 0.72rem; color: var(--label-focus); }
    .input-group input:focus,
    .input-group textarea:focus { border-bottom-color: var(--input-focus); }
    .input-icon { position: absolute; right: 0; top: 10px; width: 18px; height: 18px; color: var(--text-muted); pointer-events: none; transition: color 0.3s ease; }
    .input-icon svg { width: 100%; height: 100%; }
    .input-group input:focus ~ .input-icon { color: var(--input-focus); }

    /* File upload area */
    .file-label-text {
        display: block;
        font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em;
        color: var(--text-muted); text-transform: uppercase;
        margin-bottom: 10px;
    }
    .file-upload-area {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px;
        background: rgba(255,255,255,0.04);
        border: 1.5px dashed rgba(255,255,255,0.15);
        border-radius: 10px;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        margin-bottom: 24px;
    }
    .file-upload-area:hover { border-color: var(--input-focus); background: rgba(228,80,48,0.06); }
    .file-upload-area input[type="file"] { display: none; }
    .file-upload-icon { color: var(--text-muted); flex-shrink: 0; }
    .file-upload-info p { font-size: 0.82rem; font-weight: 600; color: var(--text); margin-bottom: 2px; }
    .file-upload-info span { font-size: 0.72rem; color: var(--text-muted); }
    #logo-filename { font-size: 0.72rem; color: var(--accent-light); margin-top: 4px; }

    /* Divider */
    .section-divider {
        height: 1px;
        background: rgba(255,255,255,0.07);
        margin: 4px 0 24px;
    }
    .section-label {
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: rgba(255,255,255,0.3);
        margin-bottom: 18px;
    }

    /* Submit */
    .btn {
        width: 100%; height: 48px; margin-top: 4px;
        background: linear-gradient(to right,#d44525,#a02010);
        border: none; border-radius: 30px; color: #fff;
        font-size: 0.9rem; font-weight: 700; font-family: var(--font);
        letter-spacing: 0.06em; cursor: pointer;
        position: relative; overflow: hidden;
        box-shadow: 0 4px 18px rgba(200,60,20,0.5);
        transition: transform 0.2s ease, box-shadow 0.25s ease;
    }
    .btn::after { content:''; position:absolute; inset:0; background:linear-gradient(to right,rgba(255,255,255,0.08),transparent); border-radius:30px; }
    .btn:hover  { transform:translateY(-2px); box-shadow:0 8px 28px rgba(200,60,20,0.65); }
    .btn:active { transform:translateY(0); box-shadow:0 2px 10px rgba(200,60,20,0.4); }
    .btn .ripple { position:absolute; border-radius:50%; background:rgba(255,255,255,0.25); transform:scale(0); animation:ripple-anim 0.55s linear; pointer-events:none; }
    @keyframes ripple-anim { to { transform:scale(4); opacity:0; } }

    /* Footer links */
    .card-footer { padding: 0 40px 28px; display: flex; flex-direction: column; gap: 8px; align-items: center; }
    .footer-link { font-size: 0.76rem; color: var(--text-muted); text-decoration: none; text-align: center; transition: color 0.2s; }
    .footer-link:hover { color: var(--text); }
    .footer-link span { color: var(--accent-light); font-weight: 600; }

    /* Staggered entrance */
    @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:none; } }
    .anim { animation: fadeUp 0.45s ease both; }
    .d0 { animation-delay: 0.05s; } .d1 { animation-delay: 0.1s; }
    .d2 { animation-delay: 0.15s; } .d3 { animation-delay: 0.2s; }
    .d4 { animation-delay: 0.25s; } .d5 { animation-delay: 0.3s; }
    .d6 { animation-delay: 0.35s; } .d7 { animation-delay: 0.4s; }
    </style>
</head>
<body>
<a href="index.php" class="brand-link">SkillLink Rwanda</a>
<div class="wrapper">
  <div class="card">

    <!-- Header strip -->
    <div class="card-header anim d0">
      <p class="card-header-brand">SkillLink Rwanda</p>
      <h1 class="card-header-title">Employer<br>Registration</h1>
      <p class="card-header-sub">Post jobs and find talented TVET students</p>
    </div>

    <!-- Form body -->
    <div class="card-body">

      <?php if (isset($_SESSION['emp_reg_error'])): ?>
        <div class="alert alert-err anim d0"><?= htmlspecialchars($_SESSION['emp_reg_error']); unset($_SESSION['emp_reg_error']); ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">

        <!-- Personal account -->
        <p class="section-label anim d1">Your Account</p>

        <div class="input-group anim d1">
          <input type="text" name="name" id="emp-name" placeholder=" " required autocomplete="name">
          <label for="emp-name">Full Name</label>
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        </div>

        <div class="input-group anim d2">
          <input type="email" name="email" id="emp-email" placeholder=" " required autocomplete="email">
          <label for="emp-email">Email</label>
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
        </div>

        <div class="input-group anim d3">
          <input type="password" name="password" id="emp-pass" placeholder=" " required autocomplete="new-password">
          <label for="emp-pass">Password</label>
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        </div>

        <div class="section-divider anim d3"></div>

        <!-- Company info -->
        <p class="section-label anim d4">Company Details</p>

        <div class="input-group anim d4">
          <input type="text" name="company_name" id="emp-company" placeholder=" " required>
          <label for="emp-company">Company Name</label>
          <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
        </div>

        <div class="input-group anim d5">
          <textarea name="company_desc" id="emp-desc" placeholder=" " required></textarea>
          <label for="emp-desc">About Your Company</label>
        </div>

        <!-- Logo upload -->
        <span class="file-label-text anim d6">Company Logo <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></span>
        <label class="file-upload-area anim d6" for="logo-input">
          <span class="file-upload-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </span>
          <div class="file-upload-info">
            <p>Click to upload logo</p>
            <span>JPG, PNG, GIF — shown on job listings</span>
            <p id="logo-filename"></p>
          </div>
          <input type="file" name="company_logo" id="logo-input" accept=".jpg,.jpeg,.png,.gif">
        </label>

        <button class="btn anim d7" type="submit" name="register">Create Employer Account</button>
      </form>
    </div>

    <div class="card-footer">
      <a href="login.php" class="footer-link">Already have an account? <span>Sign In</span></a>
      <a href="login.php#register" class="footer-link" id="goStudentReg">Student? <span>Register here →</span></a>
    </div>

  </div>
</div>

<script>
// Show filename when logo picked
document.getElementById('logo-input').addEventListener('change', function(){
    const el = document.getElementById('logo-filename');
    el.textContent = this.files[0] ? this.files[0].name : '';
});

// Ripple
document.querySelectorAll('.btn').forEach(btn=>{
    btn.addEventListener('click',function(e){
        this.querySelectorAll('.ripple').forEach(r=>r.remove());
        const rect=this.getBoundingClientRect(), size=Math.max(rect.width,rect.height);
        const r=document.createElement('span'); r.classList.add('ripple');
        r.style.cssText=`width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;`;
        this.appendChild(r); r.addEventListener('animationend',()=>r.remove());
    });
});

// Icon focus color
document.querySelectorAll('.input-group input, .input-group textarea').forEach(inp=>{
    const ic=inp.closest('.input-group').querySelector('.input-icon');
    inp.addEventListener('focus',()=>{ if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur', ()=>{ if(ic) ic.style.color=''; });
});

// "Student register" link opens the register panel on login page
document.getElementById('goStudentReg').addEventListener('click', function(e){
    e.preventDefault();
    window.location.href = 'login.php';
    // After redirect, we can't trigger JS there, but opening login.php
    // lands on login form; user can click "Sign Up" to switch
});
</script>
</body>
</html>
