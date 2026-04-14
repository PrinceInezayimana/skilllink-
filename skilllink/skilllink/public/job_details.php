<?php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') { header("Location: login.php"); exit(); }
$student_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) { header("Location: student/dashboard.php"); exit(); }
$job_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->bind_param("i",$student_id); $stmt->execute();
$student_rec = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$student_rec) {
    $stmt = $conn->prepare("INSERT INTO students(user_id) VALUES(?)");
    $stmt->bind_param("i",$student_id);
    if($stmt->execute()) $student_table_id=$stmt->insert_id; else die("Error: ".$stmt->error);
    $stmt->close();
} else { $student_table_id = $student_rec['id']; }
if (empty($student_table_id)) die("Failed to get student record.");

$stmt = $conn->prepare("SELECT jobs.*, employers.company_name, employers.description AS company_desc FROM jobs JOIN employers ON jobs.employer_id=employers.id WHERE jobs.id=?");
$stmt->bind_param("i",$job_id); $stmt->execute();
$job = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$job) { header("Location: student/dashboard.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM applications WHERE student_id=? AND job_id=?");
$stmt->bind_param("ii",$student_table_id,$job_id); $stmt->execute();
$application = $stmt->get_result()->fetch_assoc(); $stmt->close();
$applied = $application ? true : false;
$success = "";

if (isset($_POST['apply']) && !$applied) {
    $status = "applied";
    $stmt = $conn->prepare("INSERT INTO applications(student_id,job_id,status) VALUES(?,?,?)");
    if(!$stmt){ $success="Error: ".$conn->error; } else {
        $stmt->bind_param("iis",$student_table_id,$job_id,$status);
        if($stmt->execute()){
            $success="Application submitted successfully!"; $applied=true;
            $en=$conn->prepare("SELECT employers.user_id FROM jobs JOIN employers ON jobs.employer_id=employers.id WHERE jobs.id=?");
            $en->bind_param("i",$job_id); $en->execute(); $er=$en->get_result()->fetch_assoc(); $en->close();
            if($er) push_notification($conn,$er['user_id'],"A student applied for your job \"{$job['title']}\".", "employer/applicants.php?job_id={$job_id}");
            push_notification($conn,$student_id,"Your application for \"{$job['title']}\" has been submitted!","job_details.php?id={$job_id}");
        } else { $success="Error: ".$stmt->error; }
        $stmt->close();
    }
}

$_njob_stmt=$conn->prepare("SELECT name,profile_pic FROM users WHERE id=?");
$_njob_stmt->bind_param("i",$student_id); $_njob_stmt->execute();
$_njob_user=$_njob_stmt->get_result()->fetch_assoc(); $_njob_stmt->close();
$nav_user_id=$student_id; $nav_role='student';
$nav_name=$_njob_user['name']??'Student'; $nav_pic=$_njob_user['profile_pic']??null;
$nav_base='./'; $nav_active=''; $nav_link_prefix='student/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($job['title']) ?> — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sl-theme.css">
</head>
<body class="sl-page-body">
<?php include 'partials/navbar.php'; ?>
<div class="sl-page-wrap-md">

  <?php if($success): ?>
    <div class="sl-alert <?= str_starts_with($success,'Error')?'sl-alert-err':'sl-alert-ok' ?> sl-anim">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <div class="sl-card sl-anim sl-d0" style="margin-bottom:1.5rem">
    <div class="sl-header-strip">
      <p class="brand-label">Job Listing</p>
      <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:6px"><?= htmlspecialchars($job['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($job['company_name']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($job['location']) ?></p>
    </div>
    <div class="sl-card-body">
      <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:1.5rem"><?= htmlspecialchars($job['company_desc']) ?></p>

      <h2 style="font-size:0.9rem;font-weight:700;color:var(--text);margin-bottom:0.75rem">Job Description</h2>
      <p style="font-size:0.87rem;color:var(--text-2);line-height:1.7;margin-bottom:1.5rem"><?= nl2br(htmlspecialchars($job['description'])) ?></p>

      <h2 style="font-size:0.9rem;font-weight:700;color:var(--text);margin-bottom:0.75rem">Required Skills</h2>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:2rem">
        <?php foreach(json_decode($job['required_skills'],true)??[] as $skill): ?>
          <span class="sl-tag"><?= htmlspecialchars($skill) ?></span>
        <?php endforeach; ?>
      </div>

      <?php if($applied): ?>
        <button class="sl-btn" style="background:rgba(255,255,255,0.08);box-shadow:none;cursor:not-allowed;color:var(--text-muted)" disabled>Already Applied</button>
      <?php else: ?>
        <form method="POST">
          <button type="submit" name="apply" class="sl-btn sl-btn-green">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Apply Now
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <a href="student/dashboard.php" class="sl-btn-ghost" style="display:inline-flex">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Dashboard
  </a>
</div>
</body>
</html>
