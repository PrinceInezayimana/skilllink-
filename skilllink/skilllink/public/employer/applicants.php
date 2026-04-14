<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employer') {
    header("Location: ../login.php"); exit();
}
$employer_id = $_SESSION['user_id'];

if (!isset($_GET['job_id'])) { header("Location: dashboard.php"); exit(); }
$job_id = intval($_GET['job_id']);

$stmt = $conn->prepare("SELECT jobs.*, employers.user_id FROM jobs JOIN employers ON jobs.employer_id=employers.id WHERE jobs.id=?");
$stmt->bind_param("i", $job_id); $stmt->execute();
$job = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$job || $job['user_id'] != $employer_id) { header("Location: dashboard.php"); exit(); }

// Check applicant count and payment status
$stmt = $conn->prepare("SELECT COUNT(*) AS app_cnt FROM applications WHERE job_id=?");
$stmt->bind_param("i", $job_id); $stmt->execute();
$app_cnt = $stmt->get_result()->fetch_assoc()['app_cnt']; $stmt->close();

if ($app_cnt >= 10) {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE job_id=? AND status='completed'");
    $stmt->bind_param("i", $job_id); $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$payment) {
        header("Location: pay_job.php?job_id=$job_id"); exit();
    }
}

if (isset($_POST['action']) && isset($_POST['app_id'])) {
    $app_id = intval($_POST['app_id']);
    $action = ($_POST['action'] === 'accept') ? 'accepted' : 'rejected';
    $stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=? AND job_id=?");
    $stmt->bind_param("sii", $action, $app_id, $job_id); $stmt->execute(); $stmt->close();
    $stuNotif = $conn->prepare("SELECT students.user_id, jobs.title FROM applications JOIN students ON applications.student_id=students.id JOIN jobs ON applications.job_id=jobs.id WHERE applications.id=?");
    $stuNotif->bind_param("i", $app_id); $stuNotif->execute();
    $sn = $stuNotif->get_result()->fetch_assoc(); $stuNotif->close();
    if ($sn) {
        $msg = $action === 'accepted'
            ? "Congratulations! Your application for \"{$sn['title']}\" has been accepted! 🎉"
            : "Your application for \"{$sn['title']}\" was not selected this time.";
        push_notification($conn, $sn['user_id'], $msg, "../job_details.php?id=$job_id");
    }
    header("Location: applicants.php?job_id=$job_id"); exit();
}

$stmt = $conn->prepare(
    "SELECT applications.*, students.skills, students.projects, students.resume_link, users.name, users.email
     FROM applications
     JOIN students ON applications.student_id = students.id
     JOIN users    ON students.user_id = users.id
     WHERE applications.job_id = ?"
);
$stmt->bind_param("i", $job_id); $stmt->execute();
$applicants = $stmt->get_result(); $stmt->close();

$emp_nav = $conn->prepare("SELECT users.name, employers.profile_pic AS emp_pic FROM users JOIN employers ON users.id=employers.user_id WHERE users.id=?");
$emp_nav->bind_param("i", $employer_id); $emp_nav->execute();
$emp_nav_r = $emp_nav->get_result()->fetch_assoc(); $emp_nav->close();
$nav_user_id=$employer_id; $nav_role='employer'; $nav_name=$emp_nav_r['name']??'Employer';
$nav_pic=$emp_nav_r['emp_pic']??null; $nav_base='../'; $nav_active='';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Applicants — <?= htmlspecialchars($job['title']) ?> — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body" data-role="employer" data-last-notif-id="0" data-last-job-id="0">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap">

  <div class="sl-card sl-anim sl-d0" style="margin-bottom:1.75rem">
    <div class="sl-header-strip">
      <p class="brand-label">Job Applicants</p>
      <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($job['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($job['location']) ?> · <?= $applicants->num_rows ?> applicant<?= $applicants->num_rows!=1?'s':'' ?></p>
    </div>
  </div>

  <?php if ($applicants->num_rows == 0): ?>
    <div class="sl-card sl-anim" style="text-align:center;padding:3rem;color:var(--text-muted)">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
      <p style="font-weight:600;color:var(--text);margin-bottom:.25rem">No applicants yet</p>
      <p style="font-size:.85rem">Share your job posting to attract candidates.</p>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem">
      <?php $i=0; while ($app = $applicants->fetch_assoc()):
        $skills = json_decode($app['skills'], true) ?? [];
        $projects = json_decode($app['projects'], true) ?? [];
        $ini = strtoupper(substr($app['name']??'S',0,1));
        $colors = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4'];
        $col = $colors[ord($ini) % count($colors)];
      ?>
      <div class="sl-card sl-anim sl-d<?= min($i++,5) ?>">
        <div class="sl-card-body">
          <div style="display:flex;align-items:center;gap:.875rem;margin-bottom:1rem">
            <div style="width:46px;height:46px;border-radius:50%;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;flex-shrink:0"><?= $ini ?></div>
            <div>
              <div style="font-weight:700;color:var(--text);font-size:.95rem"><?= htmlspecialchars($app['name']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($app['email']) ?></div>
            </div>
            <span class="sl-badge sl-badge-<?= $app['status'] === 'applied' ? 'applied' : ($app['status'] === 'accepted' ? 'accepted' : 'rejected') ?>" style="margin-left:auto"><?= ucfirst($app['status']) ?></span>
          </div>

          <?php if (!empty($skills)): ?>
          <div style="margin-bottom:.875rem">
            <p style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Skills</p>
            <div style="display:flex;flex-wrap:wrap;gap:3px">
              <?php foreach ($skills as $sk): ?><span class="sl-tag"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($projects)): ?>
          <div style="margin-bottom:.875rem">
            <p style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Projects</p>
            <?php foreach ($projects as $proj): ?>
            <div style="font-size:.8rem;color:var(--text-2)">
              — <?= htmlspecialchars($proj['title']??'') ?>
              <?php if(!empty($proj['link'])): ?>
                <a href="<?= htmlspecialchars($proj['link']) ?>" target="_blank" class="sl-section-link" style="margin-left:4px">↗</a>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($app['resume_link'])): ?>
          <div style="margin-bottom:.875rem">
            <a href="../<?= htmlspecialchars($app['resume_link']) ?>" target="_blank" class="sl-btn-ghost" style="font-size:.78rem">
              <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              View Resume
            </a>
          </div>
          <?php endif; ?>

          <?php if ($app['status'] == 'applied'): ?>
          <div style="display:flex;gap:.625rem;margin-top:.5rem;border-top:1px solid var(--border);padding-top:.875rem">
            <form method="POST" style="flex:1">
              <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
              <input type="hidden" name="action" value="accept">
              <button type="submit" class="sl-btn sl-btn-sm sl-btn-green" style="width:100%">✓ Accept</button>
            </form>
            <form method="POST" style="flex:1" onsubmit="return confirm('Reject this application?')">
              <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button type="submit" class="sl-btn sl-btn-sm sl-btn-danger" style="width:100%">✕ Reject</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

  <div style="margin-top:1.5rem">
    <a href="dashboard.php" class="sl-btn-ghost">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
  </div>
</div>

<script>
  window.SL_BASE = '../';
</script>
<script src="../assets/js/realtime.js"></script>
</body>
</html>
