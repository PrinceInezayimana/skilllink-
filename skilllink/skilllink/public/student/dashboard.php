<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='student'){header("Location: ../login.php");exit();}
$student_id=$_SESSION['user_id'];
$stmt=$conn->prepare("SELECT * FROM users WHERE id=?"); $stmt->bind_param("i",$student_id); $stmt->execute();
$user=$stmt->get_result()->fetch_assoc(); $stmt->close();
$stmt=$conn->prepare("SELECT * FROM students WHERE user_id=?"); $stmt->bind_param("i",$student_id); $stmt->execute();
$student=$stmt->get_result()->fetch_assoc(); $stmt->close();
if(!$student){$ins=$conn->prepare("INSERT INTO students(user_id)VALUES(?)");$ins->bind_param("i",$student_id);$ins->execute();$ins->close();$student=['user_id'=>$student_id,'skills'=>null,'projects'=>null,'resume_link'=>null];}
$as=$conn->prepare("SELECT status,COUNT(*) as cnt FROM applications WHERE student_id=(SELECT id FROM students WHERE user_id=?) GROUP BY status");
$as->bind_param("i",$student_id);$as->execute();$app_stats=['applied'=>0,'accepted'=>0,'rejected'=>0];
$ar=$as->get_result(); while($r=$ar->fetch_assoc()) $app_stats[$r['status']]=$r['cnt']; $as->close();
$jobs=$conn->query("SELECT jobs.*,employers.company_name FROM jobs JOIN employers ON jobs.employer_id=employers.id WHERE jobs.status='approved' ORDER BY jobs.created_at DESC");
$unread=count_unread($conn,$student_id);
$nav_user_id=$student_id;$nav_role='student';$nav_name=$user['name'];$nav_pic=$user['profile_pic']??null;$nav_base='../';$nav_active='dashboard';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
<style>
.sl-main-grid{display:grid;grid-template-columns:260px 1fr;gap:1.5rem}
</style>
</head><body class="sl-page-body" data-role="student" data-last-notif-id="<?= count_unread($conn,$student_id) > 0 ? (int)$conn->query('SELECT MAX(id) FROM notifications WHERE user_id='.$student_id)->fetch_row()[0] : 0 ?>" data-last-job-id="<?= (int)$conn->query('SELECT MAX(id) FROM jobs WHERE status=\'approved\'')->fetch_row()[0] ?>">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap">
  <div class="sl-welcome-banner sl-anim sl-d0">
    <p class="greeting">Good <?= date('H')<12?'morning':(date('H')<17?'afternoon':'evening') ?> 👋</p>
    <h1 class="name"><?= htmlspecialchars($user['name']) ?></h1>
    <p class="sub">Find your next opportunity · <?= $unread ?> unread notification<?= $unread!=1?'s':'' ?></p>
  </div>
  <div class="sl-stats-grid sl-anim sl-d1">
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
      <div><div class="sl-stat-val" id="stat-total"><?= array_sum($app_stats) ?></div><div class="sl-stat-lbl">Total Applications</div></div>
    </div>
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:rgba(16,185,129,0.15);color:#10b981"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
      <div><div class="sl-stat-val" id="stat-accepted"><?= $app_stats['accepted'] ?></div><div class="sl-stat-lbl">Accepted</div></div>
    </div>
    <div class="sl-stat">
      <div class="sl-stat-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
      <div><div class="sl-stat-val" id="stat-pending"><?= $app_stats['applied'] ?></div><div class="sl-stat-lbl">Pending</div></div>
    </div>
  </div>
  <div class="sl-main-grid sl-anim sl-d2">
    <div class="sl-sidebar">
      <div class="sl-sidebar-head">
        <img src="<?= avatar_url($user['profile_pic']??null,$user['name'],'../') ?>" alt="avatar" class="sl-sidebar-avatar">
        <div class="sl-sidebar-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="sl-sidebar-email"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="sl-sidebar-body">
        <div class="sl-sidebar-section">
          <div class="sl-sidebar-section-label">Your Skills</div>
          <?php $skills=json_decode($student['skills']??'[]',true)??[]; ?>
          <?php if(!empty($skills)): foreach($skills as $s): ?><span class="sl-tag"><?= htmlspecialchars($s) ?></span><?php endforeach; else: ?>
          <p style="font-size:0.78rem;color:var(--text-muted)">No skills added yet.</p><?php endif; ?>
        </div>
        <?php if(!empty($student['resume_link'])): ?>
        <a href="../<?= htmlspecialchars($student['resume_link']) ?>" target="_blank" class="sl-btn-ghost" style="display:flex;width:100%;margin-bottom:0.75rem">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          View Resume
        </a>
        <?php endif; ?>
        <a href="profile.php" class="sl-btn sl-btn-sm" style="display:block;text-align:center;width:100%;margin-bottom:0.5rem">Edit Profile</a>
        <a href="applications.php" class="sl-btn-ghost" style="display:flex;width:100%;justify-content:center">My Applications</a>
      </div>
    </div>
    <div>
      <div class="sl-section-header">
        <span class="sl-section-title">Available Jobs &amp; Internships</span>
        <span class="sl-section-count" id="sl-job-count"><?= $jobs->num_rows ?> listing<?= $jobs->num_rows!=1?'s':'' ?></span>
      </div>
      <div style="display:grid;gap:1rem" id="sl-job-list">
        <?php $has=false; $colors=['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4'];
        while($job=$jobs->fetch_assoc()):$has=true;
          $skills=json_decode($job['required_skills']??'[]',true)??[];
          $ini=strtoupper(substr($job['company_name']??'C',0,1));
          $col=$colors[ord($ini)%count($colors)]; ?>
        <div class="sl-job-card">
          <div class="sl-job-logo" style="background:<?= $col ?>"><?= $ini ?></div>
          <div style="flex:1;min-width:0">
            <div class="sl-job-title"><?= htmlspecialchars($job['title']) ?></div>
            <div class="sl-job-meta"><?= htmlspecialchars($job['company_name']) ?><?= $job['location']?' · '.htmlspecialchars($job['location']):'' ?></div>
            <?php if(!empty($skills)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px"><?php foreach(array_slice($skills,0,4) as $sk): ?><span class="sl-tag"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?></div>
            <?php endif; ?>
            <a href="../job_details.php?id=<?= $job['id'] ?>" class="sl-btn sl-btn-sm">View &amp; Apply →</a>
          </div>
        </div>
        <?php endwhile; if(!$has): ?>
        <div class="sl-card" style="text-align:center;padding:3rem;color:var(--text-muted)">
          <p style="font-weight:600">No jobs posted yet</p>
          <p style="font-size:0.82rem;margin-top:4px">Check back soon for new opportunities</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  window.SL_BASE = '../';
</script>
<script src="../assets/js/realtime.js"></script>
</body></html>
