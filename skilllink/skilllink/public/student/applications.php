<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->bind_param("i", $student_id); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$student) {
    $ins = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
    $ins->bind_param("i", $student_id); $ins->execute();
    $student_table_id = $ins->insert_id; $ins->close();
} else { $student_table_id = $student['id']; }

$u = $conn->prepare("SELECT name, profile_pic FROM users WHERE id=?");
$u->bind_param("i", $student_id); $u->execute();
$user_info = $u->get_result()->fetch_assoc(); $u->close();

if (isset($_POST['withdraw']) && isset($_POST['app_id'])) {
    $app_id = intval($_POST['app_id']);
    $stmt = $conn->prepare("DELETE FROM applications WHERE id=? AND student_id=?");
    $stmt->bind_param("ii", $app_id, $student_table_id); $stmt->execute(); $stmt->close();
    header("Location: applications.php?msg=withdrawn"); exit();
}

$stmt = $conn->prepare(
    "SELECT applications.id AS app_id, applications.status, applications.applied_at,
            jobs.id AS job_id, jobs.title, jobs.location, jobs.required_skills, employers.company_name
     FROM applications
     JOIN jobs ON applications.job_id = jobs.id
     JOIN employers ON jobs.employer_id = employers.id
     WHERE applications.student_id = ?
     ORDER BY applications.applied_at DESC"
);
$stmt->bind_param("i", $student_table_id); $stmt->execute();
$applications = $stmt->get_result(); $stmt->close();

$all_apps = [];
while ($row = $applications->fetch_assoc()) $all_apps[] = $row;
$counts = ['applied'=>0,'accepted'=>0,'rejected'=>0];
foreach ($all_apps as $a) { if(isset($counts[$a['status']])) $counts[$a['status']]++; }

$nav_user_id=$student_id; $nav_role='student'; $nav_name=$user_info['name']??'Student';
$nav_pic=$user_info['profile_pic']??null; $nav_base='../'; $nav_active='applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Applications — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap-md">

  <h1 class="sl-page-title sl-anim sl-d0">My Applications</h1>
  <p class="sl-page-sub sl-anim sl-d0">Track the status of jobs and internships you've applied for.</p>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'withdrawn'): ?>
    <div class="sl-alert sl-alert-warn sl-anim">Application withdrawn successfully.</div>
  <?php endif; ?>

  <?php if (empty($all_apps)): ?>
    <div class="sl-card sl-anim" style="text-align:center;padding:3rem">
      <svg width="40" height="40" fill="none" stroke="var(--text-muted)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <p style="font-weight:700;color:var(--text);margin-bottom:.5rem">No applications yet</p>
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.5rem">Browse available jobs and internships to get started.</p>
      <a href="dashboard.php" class="sl-btn sl-btn-sm">Browse Jobs →</a>
    </div>
  <?php else: ?>

    <!-- Stats row -->
    <div class="sl-stats-grid sl-anim sl-d1" style="margin-bottom:1.75rem">
      <div class="sl-stat">
        <div class="sl-stat-icon" style="background:var(--blue-dim);color:var(--blue)">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><div class="sl-stat-val"><?= $counts['applied'] ?></div><div class="sl-stat-lbl">Pending</div></div>
      </div>
      <div class="sl-stat">
        <div class="sl-stat-icon" style="background:var(--green-dim);color:var(--green)">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><div class="sl-stat-val"><?= $counts['accepted'] ?></div><div class="sl-stat-lbl">Accepted</div></div>
      </div>
      <div class="sl-stat">
        <div class="sl-stat-icon" style="background:var(--red-dim);color:#ef4444">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><div class="sl-stat-val"><?= $counts['rejected'] ?></div><div class="sl-stat-lbl">Rejected</div></div>
      </div>
    </div>

    <!-- Applications list -->
    <div style="display:grid;gap:1rem">
      <?php foreach ($all_apps as $i => $app):
        $skills = json_decode($app['required_skills'], true) ?? [];
        $colors = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4'];
        $ini = strtoupper(substr($app['company_name']??'C',0,1));
        $col = $colors[ord($ini) % count($colors)];
      ?>
      <div class="sl-card sl-anim sl-d<?= min($i,5) ?>" style="padding:1.25rem 1.5rem">
        <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap">
          <div style="width:44px;height:44px;border-radius:11px;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0"><?= $ini ?></div>
          <div style="flex:1;min-width:180px">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:4px">
              <span style="font-size:.95rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($app['title']) ?></span>
              <span class="sl-badge sl-badge-<?= $app['status'] === 'applied' ? 'applied' : ($app['status'] === 'accepted' ? 'accepted' : 'rejected') ?>"><?= ucfirst($app['status']) ?></span>
            </div>
            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:6px"><?= htmlspecialchars($app['company_name']) ?><?= $app['location'] ? ' · '.htmlspecialchars($app['location']) : '' ?></p>
            <?php if (!empty($skills)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-bottom:8px">
              <?php foreach (array_slice($skills,0,5) as $sk): ?><span class="sl-tag"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p style="font-size:.72rem;color:var(--text-muted)">Applied <?= date('M j, Y', strtotime($app['applied_at'])) ?></p>
          </div>
          <div style="display:flex;gap:.5rem;flex-shrink:0;align-items:center">
            <a href="../job_details.php?id=<?= $app['job_id'] ?>" class="sl-btn sl-btn-sm">View Job</a>
            <?php if ($app['status'] === 'applied'): ?>
            <form method="POST" onsubmit="return confirm('Withdraw this application?')">
              <input type="hidden" name="app_id" value="<?= $app['app_id'] ?>">
              <button type="submit" name="withdraw" class="sl-btn-ghost">Withdraw</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
