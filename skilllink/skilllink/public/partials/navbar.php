<?php
/**
 * Shared Navbar Partial — SkillLink Rwanda
 *
 * Required vars (set before including):
 *   $conn          — active mysqli connection
 *   $nav_user_id   — current user's id
 *   $nav_role      — 'student' | 'employer' | 'admin'
 *   $nav_name      — display name
 *   $nav_pic       — profile_pic filename or null
 *   $nav_base        — path prefix back to public/ root (e.g. '../' or './')
 *   $nav_link_prefix — optional: prefix for role nav links ('' when inside role subdir, 'student/' etc from root)
 *   $nav_active    — which nav item is active (e.g. 'dashboard', 'profile', 'applications')
 *
 * Optional employer-only:
 *   $nav_emp_pic   — employer profile_pic (separate from company logo)
 */

$_unread  = count_unread($conn, $nav_user_id);
$_pic_src     = avatar_url($nav_pic ?? null, $nav_name ?? 'U', $nav_base);
$_link_prefix = $nav_link_prefix ?? '';  // prefix for role-relative links (empty when already in subdir)

// Nav links by role
$_links = [];
if ($nav_role === 'student') {
    $_links = [
        ['href' => $_link_prefix . 'dashboard.php',     'label' => 'Dashboard',       'key' => 'dashboard'],
        ['href' => $_link_prefix . 'applications.php',  'label' => 'My Applications', 'key' => 'applications'],
        ['href' => $_link_prefix . 'profile.php',       'label' => 'Profile',         'key' => 'profile'],
    ];
} elseif ($nav_role === 'employer') {
    $_links = [
        ['href' => $_link_prefix . 'dashboard.php',    'label' => 'Post a Job', 'key' => 'dashboard'],
        ['href' => $_link_prefix . 'manage_jobs.php',  'label' => 'My Jobs',    'key' => 'manage_jobs'],
        ['href' => $_link_prefix . 'profile.php',      'label' => 'Company',    'key' => 'profile'],
    ];
} elseif ($nav_role === 'admin') {
    $_links = [
        ['href' => $_link_prefix . 'dashboard.php', 'label' => 'Dashboard',  'key' => 'dashboard'],
        ['href' => $_link_prefix . 'profile.php',   'label' => 'My Profile', 'key' => 'profile'],
    ];
}
?>

<style>
/* ── Navbar — Dark Theme ─────────────────────────────────────── */
.sl-navbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    height: 64px;
    background: rgba(26,26,26,0.92);
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; padding: 0 1.5rem; gap: 1rem;
}
.sl-navbar__brand {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    font-size: 1.2rem; font-weight: 800; text-decoration: none;
    background: linear-gradient(135deg, #d44525, #e05530);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    flex-shrink: 0; letter-spacing: -0.3px;
}
.sl-navbar__links { display: flex; align-items: center; gap: .25rem; margin-left: 1.5rem; flex: 1; }
.sl-navbar__link {
    padding: .375rem .875rem; border-radius: 8px;
    font-size: .875rem; font-weight: 500;
    color: rgba(255,255,255,0.55); text-decoration: none;
    transition: background .15s, color .15s;
}
.sl-navbar__link:hover { background: rgba(255,255,255,0.07); color: #f0f0f0; }
.sl-navbar__link.active { background: rgba(212,69,37,0.15); color: #e05530; font-weight: 600; }
.sl-navbar__right { display: flex; align-items: center; gap: .5rem; margin-left: auto; flex-shrink: 0; }

/* Bell */
.sl-bell-wrap { position: relative; }
.sl-bell-btn {
    width: 40px; height: 40px; border-radius: 10px;
    background: transparent; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.5); transition: background .15s, color .15s; position: relative;
}
.sl-bell-btn:hover { background: rgba(255,255,255,0.07); color: #f0f0f0; }
.sl-bell-btn svg { width: 20px; height: 20px; }
.sl-bell-badge {
    position: absolute; top: 6px; right: 6px;
    min-width: 16px; height: 16px;
    background: #d44525; border-radius: 8px; border: 2px solid #1a1a1a;
    font-size: .6rem; font-weight: 700; color: white;
    display: flex; align-items: center; justify-content: center; padding: 0 3px;
    pointer-events: none; opacity: 0; transform: scale(0.5);
    transition: opacity .2s ease, transform .25s cubic-bezier(0.34,1.56,0.64,1);
}
.sl-bell-badge.visible { opacity: 1; transform: scale(1); }
@keyframes sl-ring {
    0%{transform:rotate(0deg)}10%{transform:rotate(15deg)}20%{transform:rotate(-12deg)}
    30%{transform:rotate(10deg)}40%{transform:rotate(-8deg)}50%{transform:rotate(5deg)}
    60%{transform:rotate(-3deg)}70%{transform:rotate(2deg)}80%{transform:rotate(-1deg)}
    90%{transform:rotate(1deg)}100%{transform:rotate(0deg)}
}
.sl-bell-btn.ringing svg { animation: sl-ring .8s ease forwards; transform-origin: top center; }

/* Notification Dropdown */
.sl-notif-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0; width: 340px;
    background: #1a1a1a; border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.6), 0 4px 16px rgba(0,0,0,.4);
    border: 1px solid rgba(255,255,255,0.08); overflow: hidden;
    opacity: 0; transform: translateY(-8px) scale(.97); pointer-events: none;
    transition: opacity .22s ease, transform .22s cubic-bezier(0.34,1.56,0.64,1);
    transform-origin: top right;
}
.sl-notif-dropdown.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: all; }
.sl-notif-header {
    padding: 1rem 1.25rem .75rem; display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sl-notif-header h3 { font-size: .875rem; font-weight: 700; color: #f0f0f0; margin: 0; }
.sl-notif-mark-read { font-size: .75rem; color: #e05530; cursor: pointer; background: none; border: none; font-weight: 600; padding: 0; }
.sl-notif-mark-read:hover { text-decoration: underline; }
.sl-notif-list { max-height: 320px; overflow-y: auto; }
.sl-notif-list::-webkit-scrollbar { width: 4px; }
.sl-notif-list::-webkit-scrollbar-thumb { background: rgba(212,69,37,0.3); border-radius: 2px; }
.sl-notif-item {
    padding: .875rem 1.25rem; display: flex; gap: .75rem; align-items: flex-start;
    border-bottom: 1px solid rgba(255,255,255,0.04); text-decoration: none;
    transition: background .15s; cursor: pointer;
}
.sl-notif-item:hover { background: rgba(255,255,255,0.04); }
.sl-notif-item.unread { background: rgba(212,69,37,0.12); }
.sl-notif-item.unread:hover { background: rgba(212,69,37,0.2); }
.sl-notif-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #d44525;
    flex-shrink: 0; margin-top: 6px;
}
.sl-notif-item:not(.unread) .sl-notif-dot { background: rgba(255,255,255,0.2); }
.sl-notif-content { flex: 1; min-width: 0; }
.sl-notif-msg { font-size: .8rem; color: rgba(255,255,255,0.6); line-height: 1.4; margin: 0 0 2px; }
.sl-notif-item.unread .sl-notif-msg { color: #f0f0f0; font-weight: 500; }
.sl-notif-time { font-size: .7rem; color: rgba(255,255,255,0.35); }
.sl-notif-empty { padding: 2rem 1.25rem; text-align: center; color: rgba(255,255,255,0.38); font-size: .85rem; }
.sl-notif-footer { padding: .75rem 1.25rem; text-align: center; border-top: 1px solid rgba(255,255,255,0.07); }
.sl-notif-footer a { font-size: .8rem; color: #e05530; font-weight: 600; text-decoration: none; }
.sl-notif-footer a:hover { text-decoration: underline; }

/* Skeleton */
@keyframes sl-shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
.sl-skel {
    background: linear-gradient(90deg,#222 25%,#2a2a2a 50%,#222 75%);
    background-size: 800px 100%; animation: sl-shimmer 1.4s infinite;
    border-radius: 4px; height: 12px; margin-bottom: 6px;
}
.sl-skel-w60{width:60%} .sl-skel-w80{width:80%} .sl-skel-w40{width:40%}

/* Avatar */
.sl-avatar-wrap { position: relative; }
.sl-avatar-btn {
    width: 36px; height: 36px; border-radius: 50%; overflow: hidden;
    border: 2px solid rgba(255,255,255,0.12); cursor: pointer;
    transition: border-color .15s, transform .15s; display: block;
    background: #2a2a2a; padding: 0;
}
.sl-avatar-btn:hover { border-color: #d44525; transform: scale(1.05); }
.sl-avatar-btn img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sl-avatar-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0; width: 220px;
    background: #1a1a1a; border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.6); border: 1px solid rgba(255,255,255,0.08);
    overflow: hidden; opacity: 0; transform: translateY(-8px) scale(.97); pointer-events: none;
    transition: opacity .2s ease, transform .2s cubic-bezier(0.34,1.56,0.64,1); transform-origin: top right;
}
.sl-avatar-dropdown.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: all; }
.sl-avatar-dd-header {
    padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex; align-items: center; gap: .75rem;
}
.sl-avatar-dd-header img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.sl-avatar-dd-name { font-size: .8rem; font-weight: 700; color: #f0f0f0; margin: 0; }
.sl-avatar-dd-role { font-size: .7rem; color: rgba(255,255,255,0.38); margin: 0; text-transform: capitalize; }
.sl-avatar-dd-item {
    display: flex; align-items: center; gap: .6rem; padding: .625rem 1rem;
    text-decoration: none; font-size: .825rem; color: rgba(255,255,255,0.55);
    transition: background .12s;
}
.sl-avatar-dd-item:hover { background: rgba(255,255,255,0.06); color: #f0f0f0; }
.sl-avatar-dd-item svg { width: 15px; height: 15px; flex-shrink: 0; }
.sl-avatar-dd-item.danger { color: #e05530; }
.sl-avatar-dd-item.danger:hover { background: rgba(212,69,37,0.12); }
.sl-avatar-dd-divider { height: 1px; background: rgba(255,255,255,0.07); margin: .25rem 0; }
.sl-logout-form { margin: 0; }
.sl-logout-form button {
    display: flex; align-items: center; gap: .6rem; padding: .625rem 1rem;
    font-size: .825rem; color: #e05530;
    background: none; border: none; cursor: pointer; width: 100%; text-align: left;
    transition: background .12s; font-family: inherit;
}
.sl-logout-form button:hover { background: rgba(212,69,37,0.12); }
.sl-logout-form button svg { width: 15px; height: 15px; flex-shrink: 0; }

/* Hamburger */
.sl-hamburger {
    display: none; width: 40px; height: 40px;
    align-items: center; justify-content: center;
    border: none; background: none; cursor: pointer; border-radius: 8px;
    color: rgba(255,255,255,0.5); margin-left: auto;
}
.sl-hamburger:hover { background: rgba(255,255,255,0.07); }
.sl-mobile-menu {
    display: none; position: fixed; top: 64px; left: 0; right: 0;
    background: #1a1a1a; border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: .5rem 1rem; z-index: 999; flex-direction: column; gap: .25rem;
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
}
.sl-mobile-menu.open { display: flex; }
.sl-mobile-menu .sl-navbar__link { display: block; }
@media (max-width: 768px) { .sl-navbar__links { display: none; } .sl-hamburger { display: flex; } }
.sl-page-body { padding-top: 64px; }
</style>

<nav class="sl-navbar">
    <!-- Brand -->
    <a href="<?= $nav_base ?>index.php" class="sl-navbar__brand" id="sl-navbar-brand-anchor">
        SkillLink Rwanda
    </a>

    <!-- Nav links -->
    <div class="sl-navbar__links" id="sl-nav-links"><span id="sl-live-dot" class="sl-live-dot sl-live-offline" title="Connecting..." style="margin-right:4px"></span>
        <?php foreach ($_links as $_l): ?>
            <a href="<?= htmlspecialchars($_l['href']) ?>"
               class="sl-navbar__link <?= ($nav_active??'') === $_l['key'] ? 'active' : '' ?>">
                <?= htmlspecialchars($_l['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="sl-navbar__right">
        <!-- Bell -->
        <div class="sl-bell-wrap" id="sl-bell-wrap">
            <button class="sl-bell-btn" id="sl-bell-btn" title="Notifications" aria-label="Notifications">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="sl-bell-badge <?= $_unread > 0 ? 'visible' : '' ?>" id="sl-badge">
                    <?= $_unread > 9 ? '9+' : ($_unread > 0 ? $_unread : '') ?>
                </span>
            </button>

            <!-- Notification dropdown -->
            <div class="sl-notif-dropdown" id="sl-notif-dropdown">
                <div class="sl-notif-header">
                    <h3>Notifications</h3>
                    <button class="sl-notif-mark-read" id="sl-mark-read">Mark all read</button>
                </div>
                <div class="sl-notif-list" id="sl-notif-list" id="sl-notif-list">
                    <!-- skeleton -->
                    <div style="padding:1rem 1.25rem">
                        <div class="sl-skel sl-skel-w80"></div>
                        <div class="sl-skel sl-skel-w40"></div>
                    </div>
                    <div style="padding:1rem 1.25rem; border-top:1px solid #F8FAFC">
                        <div class="sl-skel sl-skel-w60"></div>
                        <div class="sl-skel sl-skel-w40"></div>
                    </div>
                </div>
                <div class="sl-notif-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- Avatar -->
        <div class="sl-avatar-wrap" id="sl-avatar-wrap">
            <button class="sl-avatar-btn" id="sl-avatar-btn" title="Account" aria-label="Account menu">
                <img src="<?= $_pic_src ?>" alt="<?= htmlspecialchars($nav_name ?? 'User') ?>" id="sl-avatar-img">
            </button>

            <div class="sl-avatar-dropdown" id="sl-avatar-dropdown">
                <div class="sl-avatar-dd-header">
                    <img src="<?= $_pic_src ?>" alt="avatar">
                    <div>
                        <p class="sl-avatar-dd-name"><?= htmlspecialchars($nav_name ?? 'User') ?></p>
                        <p class="sl-avatar-dd-role"><?= htmlspecialchars($nav_role ?? '') ?></p>
                    </div>
                </div>

                <?php if ($nav_role === 'student'): ?>
                    <a href="<?= $_link_prefix ?>profile.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Edit Profile
                    </a>
                    <a href="<?= $_link_prefix ?>applications.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        My Applications
                    </a>
                <?php elseif ($nav_role === 'employer'): ?>
                    <a href="<?= $_link_prefix ?>profile.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Company Profile
                    </a>
                    <a href="<?= $_link_prefix ?>manage_jobs.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Manage Jobs
                    </a>
                <?php elseif ($nav_role === 'admin'): ?>
                    <a href="<?= $_link_prefix ?>profile.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Profile
                    </a>
                    <a href="<?= $nav_base ?>change_password.php" class="sl-avatar-dd-item">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Change Password
                    </a>
                <?php endif; ?>

                <div class="sl-avatar-dd-divider"></div>

                <form method="POST" action="<?= $nav_base ?>logout.php" class="sl-logout-form">
                    <button type="submit">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign out
                    </button>
                </form>
            </div>
        </div>

        <!-- Hamburger -->
        <button class="sl-hamburger" id="sl-hamburger" aria-label="Menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
</nav>

<!-- Mobile menu -->
<div class="sl-mobile-menu" id="sl-mobile-menu">
    <?php foreach ($_links as $_l): ?>
        <a href="<?= htmlspecialchars($_l['href']) ?>"
           class="sl-navbar__link <?= ($nav_active??'') === $_l['key'] ? 'active' : '' ?>">
            <?= htmlspecialchars($_l['label']) ?>
        </a>
    <?php endforeach; ?>
</div>

<script>
(function() {
    const bellBtn   = document.getElementById('sl-bell-btn');
    const bellWrap  = document.getElementById('sl-bell-wrap');
    const dropdown  = document.getElementById('sl-notif-dropdown');
    const list      = document.getElementById('sl-notif-list');
    const badge     = document.getElementById('sl-badge');
    const markRead  = document.getElementById('sl-mark-read');

    const avatarBtn = document.getElementById('sl-avatar-btn');
    const avatarDD  = document.getElementById('sl-avatar-dropdown');
    const hamburger = document.getElementById('sl-hamburger');
    const mobileMenu= document.getElementById('sl-mobile-menu');

    let notifLoaded = false;
    let bellOpen    = false;
    let avatarOpen  = false;

    // ── Bell ring animation on load if unread ────────────────────────────
    const initialUnread = parseInt(badge.textContent) || 0;
    if (initialUnread > 0) {
        setTimeout(() => {
            bellBtn.classList.add('ringing');
            bellBtn.addEventListener('animationend', () => bellBtn.classList.remove('ringing'),{once:true});
        }, 800);
    }

    // ── Toggle notification dropdown ─────────────────────────────────────
    function openBell() {
        bellOpen = true;
        dropdown.classList.add('open');
        if (!notifLoaded) loadNotifications();
    }
    function closeBell() {
        bellOpen = false;
        dropdown.classList.remove('open');
    }

    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeAvatar();
        bellOpen ? closeBell() : openBell();
    });

    // ── Load notifications via AJAX ───────────────────────────────────────
    function loadNotifications() {
        notifLoaded = true;
        // Determine base path for ajax call
        const base = '<?= $nav_base ?>';
        fetch(base + 'notifications_ajax.php?action=fetch')
            .then(r => r.json())
            .then(data => renderNotifications(data))
            .catch(() => {
                list.innerHTML = '<div class="sl-notif-empty">Could not load notifications.</div>';
            });
    }

    function renderNotifications(data) {
        const items = data.notifications || [];
        const unread = data.unread || 0;

        // Update badge
        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.classList.add('visible');
        } else {
            badge.classList.remove('visible');
            badge.textContent = '';
        }

        if (items.length === 0) {
            list.innerHTML = `<div class="sl-notif-empty">
                <svg width="32" height="32" fill="none" stroke="#CBD5E1" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 0.5rem">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                You're all caught up!</div>`;
            return;
        }

        list.innerHTML = items.map((n, i) => {
            const cls = n.is_read ? '' : 'unread';
            const href = n.link ? `href="${escHtml(n.link)}"` : '';
            const tag = n.link ? 'a' : 'div';
            return `<${tag} class="sl-notif-item ${cls}" ${href}
                        style="animation: sl-fadeIn 0.25s ease ${i*0.04}s both">
                <div class="sl-notif-dot"></div>
                <div class="sl-notif-content">
                    <p class="sl-notif-msg">${escHtml(n.message)}</p>
                    <span class="sl-notif-time">${escHtml(n.time)}</span>
                </div>
            </${tag}>`;
        }).join('');
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Mark all read ─────────────────────────────────────────────────────
    markRead.addEventListener('click', () => {
        const base = '<?= $nav_base ?>';
        fetch(base + 'notifications_ajax.php?action=mark_read')
            .then(() => {
                badge.classList.remove('visible');
                badge.textContent = '';
                // Re-render unread dots
                document.querySelectorAll('.sl-notif-item.unread').forEach(el => {
                    el.classList.remove('unread');
                });
            });
    });

    // ── Avatar dropdown ───────────────────────────────────────────────────
    function openAvatar() {
        avatarOpen = true;
        avatarDD.classList.add('open');
    }
    function closeAvatar() {
        avatarOpen = false;
        avatarDD.classList.remove('open');
    }

    avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeBell();
        avatarOpen ? closeAvatar() : openAvatar();
    });

    // ── Close on outside click ────────────────────────────────────────────
    document.addEventListener('click', (e) => {
        if (!bellWrap.contains(e.target))   closeBell();
        if (!document.getElementById('sl-avatar-wrap').contains(e.target)) closeAvatar();
    });

    // ── Mobile hamburger ──────────────────────────────────────────────────
    hamburger.addEventListener('click', (e) => {
        e.stopPropagation();
        mobileMenu.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && e.target !== hamburger) {
            mobileMenu.classList.remove('open');
        }
    });

    // ── Periodic badge refresh (every 30s) ────────────────────────────────
    setInterval(() => {
        if (bellOpen) return; // don't refresh while open
        const base = '<?= $nav_base ?>';
        fetch(base + 'notifications_ajax.php?action=fetch')
            .then(r => r.json())
            .then(data => {
                const u = data.unread || 0;
                if (u > 0) {
                    badge.textContent = u > 9 ? '9+' : u;
                    badge.classList.add('visible');
                    // ring the bell if new notifications arrived
                    if (!bellBtn.classList.contains('ringing')) {
                        bellBtn.classList.add('ringing');
                        bellBtn.addEventListener('animationend', () => bellBtn.classList.remove('ringing'), {once:true});
                    }
                } else {
                    badge.classList.remove('visible');
                    badge.textContent = '';
                }
                notifLoaded = false; // force re-load next time dropdown opens
            });
    }, 30000);

    // ── Fade-in keyframe (injected once) ─────────────────────────────────
    if (!document.getElementById('sl-fadein-style')) {
        const s = document.createElement('style');
        s.id = 'sl-fadein-style';
        s.textContent = '@keyframes sl-fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }';
        document.head.appendChild(s);
    }
})();
</script>
