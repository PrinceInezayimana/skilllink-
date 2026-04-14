<?php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

if (isset($_POST['login'])) {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && password_verify($password, $result['password'])) {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['role']    = $result['role'];

        if ($result['role'] == 'student')        header('Location: student/dashboard.php');
        elseif ($result['role'] == 'employer')   header('Location: employer/dashboard.php');
        else                                      header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SkillLink Rwanda</title>
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
        --card-w: 860px; --card-h: 480px;
        --panel-dur: 700ms; --panel-ease: cubic-bezier(0.77,0,0.175,1);
        --field-dur: 700ms; --field-ease: ease;
        --font: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
    }
    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    .wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .brand-link {
        position: fixed; top: 22px; left: 28px;
        font-weight: 800; font-size: 1.05rem;
        background: linear-gradient(135deg,#d44525,#e07755);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        text-decoration: none; letter-spacing: -0.3px; z-index: 100;
    }
    .container {
        position: relative; width: var(--card-w); height: var(--card-h);
        background: var(--card-bg); border-radius: 18px; overflow: hidden;
        box-shadow: 0 0 0 1.5px rgba(190,55,20,0.55), 0 0 50px rgba(180,50,15,0.22), 0 24px 70px rgba(0,0,0,0.75);
    }
    .forms-wrap { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr; }
    .form-box { display: flex; flex-direction: column; justify-content: center; padding: 40px 44px; }
    .login-box  { grid-column: 1; }
    .register-box { grid-column: 2; }
    .form-title { font-size: 1.65rem; font-weight: 800; color: var(--text); margin-bottom: 22px; letter-spacing: 0.02em; }
    .alert { padding: 9px 13px; border-radius: 8px; font-size: 0.78rem; font-weight: 500; margin-bottom: 14px; }
    .alert-err { background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.35); color: #fca5a5; }
    .alert-ok  { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
    .input-group { position: relative; margin-bottom: 20px; }
    .input-group input {
        width: 100%; padding: 14px 34px 6px 0;
        background: transparent; border: none; border-bottom: 1.5px solid var(--input-line);
        color: var(--text); font-size: 0.88rem; font-family: var(--font); outline: none;
        transition: border-color 0.3s ease;
    }
    .input-group label {
        position: absolute; left: 0; top: 14px;
        font-size: 0.88rem; color: var(--text-muted);
        pointer-events: none;
        transition: top 0.25s ease, font-size 0.25s ease, color 0.25s ease;
    }
    .input-group input:focus ~ label,
    .input-group input:not(:placeholder-shown) ~ label { top: -2px; font-size: 0.72rem; color: var(--label-focus); }
    .input-group input:focus { border-bottom-color: var(--input-focus); }
    .input-icon { position: absolute; right: 0; top: 10px; width: 18px; height: 18px; color: var(--text-muted); pointer-events: none; transition: color 0.3s ease; }
    .input-icon svg { width: 100%; height: 100%; }
    .input-group input:focus ~ .input-icon { color: var(--input-focus); }
    .btn {
        width: 100%; height: 44px; margin-top: 6px; margin-bottom: 4px;
        background: linear-gradient(to right,#d44525,#a02010);
        border: none; border-radius: 30px; color: #fff;
        font-size: 0.88rem; font-weight: 700; font-family: var(--font);
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
    .switch-text { text-align:center; font-size:0.76rem; color:var(--text-muted); margin-top:12px; }
    .switch-text a { color:var(--accent-light); text-decoration:none; font-weight:600; margin-left:4px; transition:color 0.2s; }
    .switch-text a:hover { color:#ff7755; text-decoration:underline; }
    .employer-link { display:block; text-align:center; font-size:0.73rem; color:rgba(255,255,255,0.45); margin-top:8px; text-decoration:none; transition:color 0.2s; }
    .employer-link:hover { color:rgba(255,255,255,0.8); }
    .employer-link span { color:var(--accent-light); font-weight:600; }
    .animation {
        --li: 0;
        transform:translateX(0); opacity:1; filter:blur(0px);
        transition: transform var(--field-dur) var(--field-ease), opacity var(--field-dur) var(--field-ease), filter var(--field-dur) var(--field-ease);
        transition-delay: calc(0.1s * var(--li));
    }
    .register-box .animation { transform:translateX(120%); opacity:0; filter:blur(10px); }
    .container.active .register-box .animation { transform:translateX(0); opacity:1; filter:blur(0px); }
    .container.active .login-box .animation    { transform:translateX(-120%); opacity:0; filter:blur(10px); transition-delay:0s; }
    .sliding-panel {
        position:absolute; top:0; left:50%; width:50%; height:100%; z-index:10;
        background:linear-gradient(135deg,var(--panel-red-1) 0%,var(--panel-red-2) 45%,var(--panel-red-3) 100%);
        border-radius:0 18px 18px 0;
        transition: left var(--panel-dur) var(--panel-ease), width var(--panel-dur) var(--panel-ease), border-radius var(--panel-dur) var(--panel-ease);
        display:flex; align-items:center; justify-content:center; overflow:hidden;
    }
    .sliding-panel::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,0.07) 0%,transparent 40%); pointer-events:none; }
    .sliding-panel::after  { content:''; position:absolute; top:0; left:-1px; width:60px; height:100%; background:var(--card-bg); clip-path:polygon(0 0,0% 100%,100% 100%); pointer-events:none; transition:opacity var(--panel-dur) var(--panel-ease); }
    .container.active .sliding-panel { left:0; width:50%; border-radius:18px 0 0 18px; transition:left 350ms var(--panel-ease),width 350ms var(--panel-ease),border-radius 350ms var(--panel-ease); }
    .container.active .sliding-panel::after { opacity:0; transition:opacity 200ms ease; }
    .sliding-panel.sweeping { left:0!important; width:100%!important; border-radius:18px!important; transition:left 350ms var(--panel-ease),width 350ms var(--panel-ease),border-radius 200ms ease!important; }
    .sliding-panel.sweeping::after { opacity:0!important; transition:opacity 150ms ease!important; }
    .panel-content { position:relative; z-index:1; padding:32px 38px; text-align:left; max-width:300px; }
    .panel-message { transition:opacity 0.35s ease; }
    .panel-message.hidden { display:none; }
    .panel-brand { font-size:0.68rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:rgba(255,255,255,0.45); margin-bottom:16px; }
    .panel-heading { font-size:2rem; font-weight:800; color:#fff; line-height:1.15; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:14px; }
    .panel-sub { font-size:0.8rem; line-height:1.65; color:rgba(255,255,255,0.72); max-width:200px; }
    @media (max-width:900px) {
        :root { --card-w:95vw; --card-h:auto; }
        .container { width:95vw; height:auto; min-height:420px; }
        .forms-wrap { grid-template-columns:1fr; position:relative; }
        .login-box,.register-box { grid-column:1; min-height:420px; }
        .register-box { display:none; }
        .container.active .login-box { display:none; }
        .container.active .register-box { display:flex; }
        .sliding-panel { left:0; top:auto; bottom:0; width:100%; height:36%; border-radius:0 0 18px 18px; transition:none; }
        .sliding-panel::after { display:none; }
        .container.active .sliding-panel { left:0; width:100%; border-radius:0 0 18px 18px; }
        .panel-content { text-align:center; padding:16px 20px; max-width:100%; }
        .panel-heading { font-size:1.3rem; }
        .panel-sub { max-width:100%; }
    }
    </style>
</head>
<body>
<a href="index.php" class="brand-link">SkillLink Rwanda</a>
<div class="wrapper">
  <div class="container" id="container">
    <div class="forms-wrap">

      <!-- LOGIN FORM -->
      <div class="form-box login-box">
        <h2 class="form-title animation" style="--li:0">Login</h2>

        <?php if (isset($error)): ?>
          <div class="alert alert-err animation" style="--li:0"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['reg_success'])): ?>
          <div class="alert alert-ok animation" style="--li:0"><?= htmlspecialchars($_SESSION['reg_success']); unset($_SESSION['reg_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-err animation" style="--li:0">Invalid email or password.</div>
        <?php endif; ?>

        <form method="POST">
          <div class="input-group animation" style="--li:1">
            <input type="email" name="email" id="login-email" placeholder=" " required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label for="login-email">Email</label>
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          </div>
          <div class="input-group animation" style="--li:2">
            <input type="password" name="password" id="login-pass" placeholder=" " required autocomplete="current-password">
            <label for="login-pass">Password</label>
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          </div>
          <button class="btn animation" style="--li:3" type="submit" name="login">Login</button>
        </form>

        <p class="switch-text animation" style="--li:4">Don't have an account? <a href="#" id="toRegister">Sign Up</a></p>
      </div>

      <!-- REGISTER FORM (Student) -->
      <div class="form-box register-box" id="registerBox">
        <h2 class="form-title animation" style="--li:0">Create Account</h2>

        <?php if (isset($_SESSION['reg_error'])): ?>
          <div class="alert alert-err animation" style="--li:0"><?= htmlspecialchars($_SESSION['reg_error']); unset($_SESSION['reg_error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
          <div class="input-group animation" style="--li:1">
            <input type="text" name="name" id="reg-name" placeholder=" " required autocomplete="name">
            <label for="reg-name">Full Name</label>
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          </div>
          <div class="input-group animation" style="--li:2">
            <input type="email" name="email" id="reg-email" placeholder=" " required autocomplete="email">
            <label for="reg-email">Email</label>
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          </div>
          <div class="input-group animation" style="--li:3">
            <input type="password" name="password" id="reg-pass" placeholder=" " required autocomplete="new-password">
            <label for="reg-pass">Password</label>
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          </div>
          <input type="hidden" name="role" value="student">
          <button class="btn animation" style="--li:4" type="submit" name="register">Register</button>
        </form>

        <p class="switch-text animation" style="--li:5">Already have an account? <a href="#" id="toLogin">Sign In</a></p>
        <a href="employer_register.php" class="employer-link animation" style="--li:6">Registering as employer? <span>Click here →</span></a>
      </div>

    </div><!-- /forms-wrap -->

    <!-- SLIDING PANEL -->
    <div class="sliding-panel" id="slidingPanel">
      <div class="panel-content">
        <div class="panel-message" id="msgLogin">
          <p class="panel-brand">SkillLink Rwanda</p>
          <h3 class="panel-heading">WELCOME<br/>BACK!</h3>
          <p class="panel-sub">Great to see you again. Log in to explore new opportunities and track your applications.</p>
        </div>
        <div class="panel-message hidden" id="msgRegister">
          <p class="panel-brand">SkillLink Rwanda</p>
          <h3 class="panel-heading">JOIN<br/>US!</h3>
          <p class="panel-sub">Create your student account and connect with employers looking for your skills.</p>
        </div>
      </div>
    </div>

  </div><!-- /container -->
</div><!-- /wrapper -->

<script>
(function(){
    const container    = document.getElementById('container');
    const panel        = document.getElementById('slidingPanel');
    const toRegisterEl = document.getElementById('toRegister');
    const toLoginEl    = document.getElementById('toLogin');
    const msgLogin     = document.getElementById('msgLogin');
    const msgRegister  = document.getElementById('msgRegister');
    let isActive=false, isSweeping=false;

    function switchTo(toReg, instant){
        if(isSweeping && !instant) return;
        isSweeping=true;
        panel.classList.add('sweeping');
        setTimeout(()=>{
            toReg ? container.classList.add('active') : container.classList.remove('active');
            switchMsg(toReg);
            setTimeout(()=>{
                panel.classList.remove('sweeping');
                setTimeout(()=>{ isSweeping=false; isActive=toReg; },350);
            },20);
        }, instant?0:350);
    }
    function switchMsg(toReg){
        const out=toReg?msgLogin:msgRegister, inp=toReg?msgRegister:msgLogin;
        out.style.opacity='0';
        setTimeout(()=>{
            out.classList.add('hidden'); inp.classList.remove('hidden'); inp.style.opacity='0';
            requestAnimationFrame(()=>requestAnimationFrame(()=>{ inp.style.opacity='1'; inp.style.transition='opacity 0.35s ease'; }));
        },250);
    }
    toRegisterEl.addEventListener('click',(e)=>{ e.preventDefault(); if(!isActive) switchTo(true); });
    toLoginEl.addEventListener('click',   (e)=>{ e.preventDefault(); if(isActive)  switchTo(false); });

    document.querySelectorAll('.btn').forEach(btn=>{
        btn.addEventListener('click',function(e){
            this.querySelectorAll('.ripple').forEach(r=>r.remove());
            const rect=this.getBoundingClientRect(), size=Math.max(rect.width,rect.height);
            const x=e.clientX-rect.left-size/2, y=e.clientY-rect.top-size/2;
            const r=document.createElement('span'); r.classList.add('ripple');
            r.style.cssText=`width:${size}px;height:${size}px;left:${x}px;top:${y}px;`;
            this.appendChild(r); r.addEventListener('animationend',()=>r.remove());
        });
    });
    document.querySelectorAll('.input-group input').forEach(inp=>{
        const ic=inp.closest('.input-group').querySelector('.input-icon');
        inp.addEventListener('focus',()=>{ if(ic) ic.style.color='var(--input-focus)'; });
        inp.addEventListener('blur', ()=>{ if(ic) ic.style.color=''; });
    });
})();
</script>
</body>
</html>
