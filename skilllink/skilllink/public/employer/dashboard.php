<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employer') {
    header("Location: ../login.php"); exit();
}
$employer_user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT users.*, employers.id AS emp_id, employers.company_name, employers.profile_pic AS emp_pic FROM users JOIN employers ON users.id=employers.user_id WHERE users.id=?");
$stmt->bind_param("i", $employer_user_id); $stmt->execute();
$emp = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$emp) die("Employer profile not found.");
$emp_table_id = $emp['emp_id'];

$success = $error = "";
if (isset($_POST['post_job'])) {
    $title       = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $location    = sanitize($_POST['location']);
    $skills      = array_filter(array_map('trim', explode(',', $_POST['skills'])));
    $skills_json = json_encode(array_values($skills));
    $stmt = $conn->prepare("INSERT INTO jobs(employer_id,title,description,location,required_skills,status) VALUES(?,?,?,?,?,'approved')");
    $stmt->bind_param("issss", $emp_table_id, $title, $description, $location, $skills_json);
    if ($stmt->execute()) {

        $success = "Job posted! It is now live and visible to students.";
    } else { $error = "Failed to post job."; }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT jobs.*, (SELECT COUNT(*) FROM applications WHERE applications.job_id=jobs.id) AS app_cnt, (SELECT status FROM payments WHERE payments.job_id=jobs.id AND payments.status='completed' LIMIT 1) AS payment_status FROM jobs WHERE employer_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $emp_table_id); $stmt->execute();
$jobs_result = $stmt->get_result(); $stmt->close();

$nav_user_id=$employer_user_id; $nav_role='employer'; $nav_name=$emp['name'];
$nav_pic=$emp['emp_pic']??$emp['profile_pic']??null; $nav_base='../'; $nav_active='dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Employer Dashboard — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
<style>.sl-main-grid{display:grid;grid-template-columns:1fr 340px;gap:1.5rem}</style>
</head>
<body class="sl-page-body" data-role="employer" data-last-notif-id="0" data-last-job-id="0">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap">

  <div class="sl-welcome-banner sl-anim sl-d0">
    <p class="greeting">Welcome back 👋</p>
    <h1 class="name"><?= htmlspecialchars($emp['company_name'] ?: $emp['name']) ?></h1>
    <p class="sub"><?= htmlspecialchars($emp['name']) ?> · Employer Dashboard</p>
  </div>

  <div class="sl-main-grid">
    <!-- Jobs list -->
    <div class="sl-anim sl-d2">
      <div class="sl-section-header">
        <span class="sl-section-title">Your Job Postings</span>
        <a href="manage_jobs.php" class="sl-section-link">Manage all →</a>
      </div>

      <?php if($jobs_result->num_rows === 0): ?>
        <div class="sl-card" style="text-align:center;padding:3rem;color:var(--text-muted)">
          <p style="font-weight:600;margin-bottom:.25rem">No jobs posted yet</p>
          <p style="font-size:.85rem">Use the form to post your first job →</p>
        </div>
      <?php else: ?>
        <div style="display:grid;gap:1rem">
          <?php $colors=['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444'];
          while($job=$jobs_result->fetch_assoc()):
            $st=$job['status']??'pending';
            $ini=strtoupper(substr($emp['company_name']??'C',0,1));
            $col=$colors[abs(crc32($emp['company_name']??'C'))%count($colors)]; ?>
          <div class="sl-job-card" data-job-id="<?= $job['id'] ?>">
            <div class="sl-job-logo" style="background:<?= $col ?>"><?= $ini ?></div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:3px">
                <span class="sl-job-title" style="margin-bottom:0"><?= htmlspecialchars($job['title']) ?></span>
                <span class="sl-badge sl-badge-<?= $st === 'approved' ? 'approved' : ($st === 'rejected' ? 'rejected' : 'pending') ?>"><?= ucfirst($st) ?></span>
              </div>
              <div class="sl-job-meta"><?= htmlspecialchars($job['location']) ?> · <span class="app-count"><?= $job['app_cnt'] ?></span> applicant<?= $job['app_cnt']!=1?'s':'' ?></div>
              <div style="display:flex;gap:.5rem;margin-top:.625rem">
                <?php if ($job['app_cnt'] >= 10 && $job['payment_status'] !== 'completed'): ?>
                  <a href="pay_job.php?job_id=<?= $job['id'] ?>" class="sl-btn sl-btn-sm sl-btn-amber">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pay to View Applicants
                  </a>
                <?php else: ?>
                  <a href="applicants.php?job_id=<?= $job['id'] ?>" class="sl-btn sl-btn-sm sl-btn-green">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                    View Applicants
                  </a>
                <?php endif; ?>
                <a href="manage_jobs.php?edit=<?= $job['id'] ?>" class="sl-btn-ghost">Edit</a>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Post job form -->
    <div class="sl-anim sl-d1">
      <div class="sl-card">
        <div class="sl-card-body">
          <h2 class="sl-card-title">Post a New Job</h2>
          <?php if($success): ?><div class="sl-alert sl-alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
          <?php if($error):   ?><div class="sl-alert sl-alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
          <form method="POST">
            <div class="sl-form-group">
              <input class="sl-input" type="text" name="title" id="j-title" placeholder=" " required>
              <label class="floating" for="j-title">Job Title</label>
            </div>
            <div class="sl-form-group">
              <input class="sl-input" type="text" name="location" id="j-loc" placeholder=" " required>
              <label class="floating" for="j-loc">Location</label>
            </div>
            <div class="sl-form-group">
              <input class="sl-input" type="text" name="skills" id="j-skills" placeholder=" " required>
              <label class="floating" for="j-skills">Required Skills (comma separated)</label>
            </div>
            <div class="sl-form-group">
              <textarea class="sl-textarea" name="description" id="j-desc" placeholder=" " required></textarea>
              <label class="floating" for="j-desc">Job Description</label>
            </div>
            <button type="submit" name="post_job" class="sl-btn sl-btn-full">Post Job</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.sl-form-group .sl-input, .sl-form-group .sl-textarea').forEach(inp => {
    const ic = inp.closest('.sl-form-group').querySelector('.sl-input-icon');
    inp.addEventListener('focus', () => { if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur',  () => { if(ic) ic.style.color=''; });
});
</script>

<script>
  window.SL_BASE = '../';
</script>
<script src="../assets/js/realtime.js"></script>
</body>
</html>
