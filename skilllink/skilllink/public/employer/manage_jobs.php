<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employer') {
    header("Location: ../login.php"); exit();
}
$employer_user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, company_name FROM employers WHERE user_id=?");
$stmt->bind_param("i", $employer_user_id); $stmt->execute();
$emp_row = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$emp_row) die("Employer profile not found.");
$emp_table_id = $emp_row['id'];

$success = $error = "";

if (isset($_POST['delete_job'])) {
    $job_id = intval($_POST['job_id']);
    $check = $conn->prepare("SELECT id FROM jobs WHERE id=? AND employer_id=?");
    $check->bind_param("ii", $job_id, $emp_table_id); $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        $conn->prepare("DELETE FROM applications WHERE job_id=?")->bind_param("i",$job_id);
        $da = $conn->prepare("DELETE FROM applications WHERE job_id=?"); $da->bind_param("i",$job_id); $da->execute(); $da->close();
        $dl = $conn->prepare("DELETE FROM jobs WHERE id=? AND employer_id=?"); $dl->bind_param("ii",$job_id,$emp_table_id); $dl->execute(); $dl->close();
        header("Location: manage_jobs.php?msg=deleted"); exit();
    } else { $check->close(); $error = "Job not found or permission denied."; }
}

if (isset($_POST['update_job'])) {
    $job_id      = intval($_POST['job_id']);
    $title       = sanitize($_POST['title']       ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location    = sanitize($_POST['location']    ?? '');
    $skills      = array_filter(array_map('trim', explode(',', $_POST['skills'] ?? '')));
    $skills_json = json_encode(array_values($skills));
    $check = $conn->prepare("SELECT id FROM jobs WHERE id=? AND employer_id=?");
    $check->bind_param("ii", $job_id, $emp_table_id); $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        $stmt = $conn->prepare("UPDATE jobs SET title=?, description=?, location=?, required_skills=?, status='approved' WHERE id=? AND employer_id=?");
        $stmt->bind_param("ssssii", $title, $description, $location, $skills_json, $job_id, $emp_table_id);
        $success = $stmt->execute() ? "Job updated! Changes are now live." : "Failed to update job.";
        $stmt->close();
    } else { $check->close(); $error = "Job not found or permission denied."; }
}

$stmt = $conn->prepare("SELECT jobs.*, (SELECT COUNT(*) FROM applications WHERE applications.job_id=jobs.id) AS applicants_count FROM jobs WHERE jobs.employer_id=? ORDER BY jobs.created_at DESC");
$stmt->bind_param("i", $emp_table_id); $stmt->execute();
$jobs_result = $stmt->get_result(); $stmt->close();
$jobs = [];
while ($row = $jobs_result->fetch_assoc()) $jobs[] = $row;

$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;

$mnav = $conn->prepare("SELECT users.name, employers.profile_pic AS emp_pic FROM users JOIN employers ON users.id=employers.user_id WHERE users.id=?");
$mnav->bind_param("i", $employer_user_id); $mnav->execute();
$mnav_r = $mnav->get_result()->fetch_assoc(); $mnav->close();
$nav_user_id=$employer_user_id; $nav_role='employer'; $nav_name=$mnav_r['name']??'Employer';
$nav_pic=$mnav_r['emp_pic']??null; $nav_base='../'; $nav_active='manage_jobs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Jobs — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap-md">
  <h1 class="sl-page-title sl-anim sl-d0">Manage Jobs</h1>
  <p class="sl-page-sub sl-anim sl-d0">Edit or delete job postings for <strong style="color:var(--text)"><?= htmlspecialchars($emp_row['company_name']) ?></strong>.</p>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="sl-alert sl-alert-warn sl-anim">Job deleted successfully.</div>
  <?php endif; ?>
  <?php if ($success): ?><div class="sl-alert sl-alert-ok sl-anim"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="sl-alert sl-alert-err sl-anim"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if (empty($jobs)): ?>
    <div class="sl-card sl-anim" style="text-align:center;padding:3rem;color:var(--text-muted)">
      <p style="font-weight:600;margin-bottom:.5rem">No jobs posted yet</p>
      <a href="dashboard.php" class="sl-btn sl-btn-sm" style="margin-top:.75rem">Post a Job</a>
    </div>
  <?php else: ?>
    <div style="display:grid;gap:1rem">
      <?php foreach ($jobs as $job):
        $is_editing = ($editing_id === (int)$job['id']);
        $st = $job['status'] ?? 'pending';
        $colors = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444'];
        $ini = strtoupper(substr($emp_row['company_name']??'C',0,1));
        $col = $colors[abs(crc32($emp_row['company_name']??'C'))%count($colors)];
      ?>
      <div class="sl-card sl-anim">
        <?php if ($is_editing): ?>
          <!-- Edit form -->
          <div class="sl-card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
              <h2 class="sl-section-title">Editing Job</h2>
              <a href="manage_jobs.php" class="sl-btn-ghost" style="padding:.3rem .75rem;font-size:.78rem">✕ Cancel</a>
            </div>
            <form method="POST">
              <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="sl-form-group">
                  <input class="sl-input" type="text" name="title" id="edit-title-<?= $job['id'] ?>" placeholder=" " required value="<?= htmlspecialchars($job['title']) ?>">
                  <label class="floating" for="edit-title-<?= $job['id'] ?>">Job Title</label>
                </div>
                <div class="sl-form-group">
                  <input class="sl-input" type="text" name="location" id="edit-loc-<?= $job['id'] ?>" placeholder=" " required value="<?= htmlspecialchars($job['location']) ?>">
                  <label class="floating" for="edit-loc-<?= $job['id'] ?>">Location</label>
                </div>
              </div>
              <div class="sl-form-group">
                <input class="sl-input" type="text" name="skills" id="edit-skills-<?= $job['id'] ?>" placeholder=" "
                       value="<?= htmlspecialchars(implode(', ', json_decode($job['required_skills'],true)??[])) ?>">
                <label class="floating" for="edit-skills-<?= $job['id'] ?>">Required Skills (comma separated)</label>
              </div>
              <div class="sl-form-group">
                <textarea class="sl-textarea" name="description" id="edit-desc-<?= $job['id'] ?>" placeholder=" "><?= htmlspecialchars($job['description']) ?></textarea>
                <label class="floating" for="edit-desc-<?= $job['id'] ?>">Job Description</label>
              </div>
              <div style="display:flex;gap:.75rem">
                <button type="submit" name="update_job" class="sl-btn">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                  Save Changes
                </button>
                <a href="manage_jobs.php" class="sl-btn-ghost">Cancel</a>
              </div>
            </form>
          </div>
        <?php else: ?>
          <!-- Read view -->
          <div style="padding:1.25rem 1.5rem;display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap">
            <div style="width:44px;height:44px;border-radius:11px;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0"><?= $ini ?></div>
            <div style="flex:1;min-width:180px">
              <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:4px">
                <span style="font-size:.95rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($job['title']) ?></span>
                <span class="sl-badge sl-badge-<?= $st === 'approved'?'approved':($st==='rejected'?'rejected':'pending') ?>"><?= ucfirst($st) ?></span>
              </div>
              <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:4px"><?= htmlspecialchars($job['location']) ?> · <?= (int)$job['applicants_count'] ?> applicant<?= $job['applicants_count']!=1?'s':'' ?></p>
              <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:4px">
                <?php $req=json_decode($job['required_skills'],true)??[]; echo htmlspecialchars(implode(', ',$req))?:'N/A'; ?>
              </p>
              <p style="font-size:.72rem;color:var(--text-muted)">Posted <?= date('M j, Y', strtotime($job['created_at'])) ?></p>
              <?php if ($st === 'rejected'): ?>
                <p style="font-size:.72rem;color:#ef4444;margin-top:3px">⚠ Rejected by admin. Edit to resubmit.</p>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:.5rem;flex-shrink:0;align-items:center;flex-wrap:wrap">
              <a href="applicants.php?job_id=<?= $job['id'] ?>" class="sl-btn sl-btn-sm sl-btn-green">
                Applicants (<?= (int)$job['applicants_count'] ?>)
              </a>
              <a href="manage_jobs.php?edit=<?= $job['id'] ?>" class="sl-btn-ghost">Edit</a>
              <form method="POST" onsubmit="return confirm('Delete this job and all its applications?')">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <button type="submit" name="delete_job" class="sl-btn sl-btn-sm sl-btn-danger">Delete</button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
document.querySelectorAll('.sl-form-group .sl-input, .sl-form-group .sl-textarea').forEach(inp => {
    const ic = inp.closest('.sl-form-group').querySelector('.sl-input-icon');
    inp.addEventListener('focus', () => { if(ic) ic.style.color='var(--input-focus)'; });
    inp.addEventListener('blur',  () => { if(ic) ic.style.color=''; });
});
</script>
</body>
</html>
