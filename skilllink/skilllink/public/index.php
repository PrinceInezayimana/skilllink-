<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #111111; --card-bg: #1a1a1a; --border: rgba(255,255,255,0.08);
  --accent: #d44525; --accent-light: #e05530;
  --panel-red-1: #c03520; --panel-red-2: #8b1a0a; --panel-red-3: #2a0805;
  --text: #f0f0f0; --text-2: #b0b0b0; --text-muted: rgba(255,255,255,0.38);
  --font: 'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif;
}
html, body { font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }

/* Navbar */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; height: 64px; z-index: 100;
  background: rgba(26,26,26,0.92); border-bottom: 1px solid var(--border);
  backdrop-filter: blur(20px); display: flex; align-items: center; padding: 0 2rem;
}
.navbar-brand {
  font-size: 1.2rem; font-weight: 800; text-decoration: none;
  background: linear-gradient(135deg, var(--accent), var(--accent-light));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  letter-spacing: -0.3px;
}
.navbar-links { display: flex; gap: 0.75rem; margin-left: auto; }
.nav-btn {
  padding: 0.45rem 1.2rem; border-radius: 30px; font-size: 0.85rem; font-weight: 600;
  text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; font-family: var(--font);
}
.nav-btn-ghost { background: rgba(255,255,255,0.07); color: var(--text-2); border: 1px solid var(--border); }
.nav-btn-ghost:hover { background: rgba(255,255,255,0.12); color: var(--text); }
.nav-btn-solid { background: linear-gradient(to right,#d44525,#a02010); color: #fff; box-shadow: 0 4px 14px rgba(200,60,20,0.4); }
.nav-btn-solid:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(200,60,20,0.55); color: #fff; }

/* Hero */
.hero {
  min-height: 100vh;
  background: linear-gradient(rgba(0,0,0,0.7),rgba(0,0,0,0.7)), url('assets/images/hero-bg.png') center/cover no-repeat fixed;
  display: flex; align-items: center; justify-content: center; text-align: center;
  padding: 80px 2rem 2rem;
}
.hero-content { max-width: 750px; }
.hero-title {
  font-size: clamp(2.2rem, 5vw, 3.5rem); font-weight: 800; line-height: 1.1;
  margin-bottom: 1.25rem; letter-spacing: -0.5px;
  opacity: 0; transform: translateY(30px);
  animation: fadeUp 0.9s ease 0.3s forwards;
}
.hero-sub {
  font-size: 1.05rem; color: rgba(255,255,255,0.7); line-height: 1.65; margin-bottom: 2.5rem;
  opacity: 0; transform: translateY(20px);
  animation: fadeUp 0.9s ease 0.55s forwards;
}
.hero-btn {
  display: inline-block; padding: 0.9rem 2.5rem;
  background: linear-gradient(to right,#d44525,#a02010);
  color: #fff; font-size: 1rem; font-weight: 700; border-radius: 30px;
  text-decoration: none; letter-spacing: 0.04em;
  box-shadow: 0 6px 24px rgba(200,60,20,0.5);
  transition: transform 0.2s, box-shadow 0.2s;
  opacity: 0; transform: translateY(20px) scale(0.96);
  animation: fadeUp 0.9s ease 0.8s forwards;
}
.hero-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 32px rgba(200,60,20,0.65); color: #fff; }
@keyframes fadeUp { to { opacity: 1; transform: none; } }

/* Features */
.features {
  background: var(--card-bg);
  border-top: 1px solid var(--border);
  padding: 5rem 2rem;
}
.features-grid {
  display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5rem;
  max-width: 1100px; margin: 0 auto;
}
@media (max-width:720px) { .features-grid { grid-template-columns: 1fr; } }
.feature-card {
  background: #1f1f1f; border: 1px solid var(--border); border-radius: 16px;
  padding: 2rem 1.75rem;
  transition: border-color 0.2s, transform 0.2s;
}
.feature-card:hover { border-color: rgba(212,69,37,0.4); transform: translateY(-4px); }
.feature-icon {
  width: 46px; height: 46px; border-radius: 12px;
  background: linear-gradient(135deg,var(--panel-red-1),var(--panel-red-2));
  display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;
}
.feature-icon svg { width: 22px; height: 22px; color: #fff; }
.feature-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
.feature-desc { font-size: 0.85rem; color: var(--text-2); line-height: 1.6; }
.section-heading { text-align: center; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text); }
.section-sub { text-align: center; color: var(--text-muted); font-size: 0.88rem; margin-bottom: 3rem; }

/* Footer */
.footer {
  padding: 2rem;
  border-top: 1px solid var(--border);
  text-align: center;
  font-size: 0.8rem; color: var(--text-muted);
}
.footer a { color: var(--accent-light); text-decoration: none; }
</style>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="navbar-brand">SkillLink Rwanda</a>
  <div class="navbar-links">
    <a href="login.php" class="nav-btn nav-btn-ghost">Login</a>
    <a href="login.php" class="nav-btn nav-btn-solid">Get Started</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-content">
    <h1 class="hero-title">Connecting TVET Skills<br>to Real Opportunities 🇷🇼</h1>
    <p class="hero-sub">SkillLink Rwanda bridges students with employers,<br>helping talents turn skills into real jobs and internships.</p>
    <a href="login.php" class="hero-btn">Get Started →</a>
  </div>
</section>

<section class="features">
  <h2 class="section-heading">Built for Everyone</h2>
  <p class="section-sub">One platform, three powerful roles</p>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <div class="feature-title">For Students</div>
      <div class="feature-desc">Build your profile, showcase projects, and apply for internships and jobs with ease.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
      <div class="feature-title">For Employers</div>
      <div class="feature-desc">Post jobs, search for students based on skills, and manage applications efficiently.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
      <div class="feature-title">For Admins</div>
      <div class="feature-desc">Monitor platform activities, approve job postings, and track employment statistics.</div>
    </div>
  </div>
</section>

<footer class="footer">
  &copy; <?= date('Y') ?> <a href="#">SkillLink Rwanda</a>. All rights reserved.
</footer>
</body>
</html>
