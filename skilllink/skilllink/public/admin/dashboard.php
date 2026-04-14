<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}

if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action  = ($_POST['action'] === 'approve') ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET is_active=? WHERE id=?");
    $stmt->bind_param("ii", $action, $user_id); $stmt->execute(); $stmt->close();
    header("Location: dashboard.php"); exit();
}

// Job approval removed — jobs are now auto-approved
// Admin can delete any job
if (isset($_POST['job_action']) && $_POST['job_action'] === 'delete' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    $da = $conn->prepare("DELETE FROM applications WHERE job_id=?"); $da->bind_param("i",$job_id); $da->execute(); $da->close();
    $dj = $conn->prepare("DELETE FROM jobs WHERE id=?"); $dj->bind_param("i",$job_id); $dj->execute(); $dj->close();
    header("Location: dashboard.php"); exit();
}

$total_students     = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'];
$total_employers    = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='employer'")->fetch_assoc()['cnt'];
$total_jobs         = $conn->query("SELECT COUNT(*) AS cnt FROM jobs")->fetch_assoc()['cnt'];
$total_applications = $conn->query("SELECT COUNT(*) AS cnt FROM applications")->fetch_assoc()['cnt'];

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Jobs now auto-approved — show all jobs for admin visibility
$all_jobs = $conn->query("SELECT jobs.*, employers.company_name FROM jobs JOIN employers ON jobs.employer_id=employers.id ORDER BY jobs.created_at DESC LIMIT 10");

$admin_user_id = $_SESSION['user_id'];
$adm = $conn->prepare("SELECT * FROM users WHERE id=?");
$adm->bind_param("i", $admin_user_id); $adm->execute();
$admin_user = $adm->get_result()->fetch_assoc(); $adm->close();

$nav_user_id=$admin_user_id; $nav_role='admin'; $nav_name=$admin_user['name']??'Admin';
$nav_pic=$admin_user['profile_pic']??null; $nav_base='../'; $nav_active='dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body" data-role="admin" data-last-notif-id="0" data-last-job-id="0">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap">

  <div class="sl-welcome-banner sl-anim sl-d0">
    <p class="greeting">Admin Panel 🛡</p>
    <h1 class="name">Platform Overview</h1>
    <p class="sub">Monitor activity, approve users and job postings.</p>
  </div>

  <!-- Stats -->
  <div class="sl-stats-grid sl-stats-grid-4 sl-anim sl-d1" style="margin-bottom:1.75rem">
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:var(--blue-dim);color:var(--blue)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div><div class="sl-stat-val" id="admin-students"><?= $total_students ?></div><div class="sl-stat-lbl">Students</div></div>
    </div>
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:var(--amber-dim);color:var(--amber)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      </div>
      <div><div class="sl-stat-val" id="admin-employers"><?= $total_employers ?></div><div class="sl-stat-lbl">Employers</div></div>
    </div>
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:var(--accent-dim);color:var(--accent-light)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <div><div class="sl-stat-val" id="admin-jobs"><?= $total_jobs ?></div><div class="sl-stat-lbl">Jobs Posted</div></div>
    </div>
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:var(--green-dim);color:var(--green)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      </div>
      <div><div class="sl-stat-val" id="admin-applications"><?= $total_applications ?></div><div class="sl-stat-lbl">Applications</div></div>
    </div>
  </div>

  <!-- Live Job Listings -->
  <div class="sl-card sl-anim sl-d2" style="margin-bottom:1.75rem">
    <div class="sl-card-body">
      <h2 class="sl-card-title">Live Job Listings</h2>
      <?php if (!$pending_jobs): ?>
        <div class="sl-alert sl-alert-warn">Job approval requires a <code>status</code> column in the jobs table.</div>
      <?php elseif ($pending_jobs->num_rows == 0): ?>
        <p style="color:var(--text-muted);font-size:.88rem">No jobs pending approval. ✓</p>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
          <?php while ($job = $pending_jobs->fetch_assoc()):
            $req = json_decode($job['required_skills'],true)??[];
          ?>
          <div style="background:var(--card-bg-2);border:1px solid var(--border);border-radius:12px;padding:1.25rem">
            <div style="font-weight:700;color:var(--text);margin-bottom:3px"><?= htmlspecialchars($job['title']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:6px"><?= htmlspecialchars($job['company_name']) ?></div>
            <?php if(!empty($req)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-bottom:.875rem">
              <?php foreach(array_slice($req,0,5) as $sk): ?><span class="sl-tag"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:.5rem">
              <form method="POST" style="flex:1">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <input type="hidden" name="job_action" value="approve">
                <button type="submit" class="sl-btn sl-btn-sm sl-btn-green" style="width:100%">✓ Approve</button>
              </form>
              <form method="POST" style="flex:1" onsubmit="return confirm('Reject this job?')">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <input type="hidden" name="job_action" value="reject">
                <button type="submit" class="sl-btn sl-btn-sm sl-btn-danger" style="width:100%">✕ Reject</button>
              </form>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Users table -->
  <div class="sl-card sl-anim sl-d3">
    <div class="sl-card-body">
      <h2 class="sl-card-title">Users Management</h2>
      <div style="overflow-x:auto">
        <table class="sl-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $users->fetch_assoc()):
              $is_active = isset($user['is_active']) ? $user['is_active'] : 1;
            ?>
            <tr>
              <td style="color:var(--text);font-weight:500"><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><span class="sl-badge sl-badge-<?= $user['role']==='employer'?'applied':($user['role']==='admin'?'approved':'pending') ?>"><?= ucfirst($user['role']) ?></span></td>
              <td>
                <?php if($is_active): ?>
                  <span class="sl-badge sl-badge-approved">Active</span>
                <?php else: ?>
                  <span class="sl-badge sl-badge-pending">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (isset($user['is_active']) && !$user['is_active']): ?>
                  <div style="display:flex;gap:.375rem;align-items:center">
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="sl-btn sl-btn-sm sl-btn-green">Approve</button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Reject this user?')">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" class="sl-btn sl-btn-sm sl-btn-danger">Reject</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span style="font-size:.78rem;color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  window.SL_BASE = '../';
</script>
<script src="../assets/js/realtime.js"></script>
</body>
</html>
