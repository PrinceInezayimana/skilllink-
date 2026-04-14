<?php
session_start();
require_once '../../src/config.php';
require_once '../../src/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employer') {
    header("Location: ../login.php"); exit();
}
$employer_user_id = $_SESSION['user_id'];

if (!isset($_GET['job_id'])) { header("Location: dashboard.php"); exit(); }
$job_id = intval($_GET['job_id']);

// Verify job ownership and applicant count
$stmt = $conn->prepare("SELECT jobs.*, employers.id AS emp_id, (SELECT COUNT(*) FROM applications WHERE applications.job_id=jobs.id) AS app_cnt FROM jobs JOIN employers ON jobs.employer_id=employers.id WHERE jobs.id=? AND employers.user_id=?");
$stmt->bind_param("ii", $job_id, $employer_user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) { header("Location: dashboard.php"); exit(); }

// Check if already paid
$stmt = $conn->prepare("SELECT * FROM payments WHERE job_id=? AND status='completed'");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$payment_exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($payment_exists) {
    header("Location: applicants.php?job_id=$job_id"); exit();
}

$success = $error = "";
if (isset($_POST['confirm_payment'])) {
    $phone = sanitize($_POST['phone_number']);
    $stmt = $conn->prepare("INSERT INTO payments (job_id, employer_id, phone_number, status) VALUES (?, ?, ?, 'completed')");
    $stmt->bind_param("iis", $job_id, $job['emp_id'], $phone);
    if ($stmt->execute()) {
        $success = "Payment confirmed! You can now view all applicants.";
        header("Refresh: 2; url=applicants.php?job_id=$job_id");
    } else {
        $error = "Failed to record payment. Please try again.";
    }
    $stmt->close();
}

$nav_user_id=$employer_user_id; $nav_role='employer'; $nav_name=$_SESSION['name']??'Employer';
$nav_base='../'; $nav_active='';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Payment Required — SkillLink Rwanda</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/sl-theme.css">
<style>
    .pay-card { max-width: 500px; margin: 2rem auto; }
    .momo-logo { width: 80px; margin-bottom: 1rem; }
    .amount-box { background: var(--card-bg-2); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; text-align: center; margin-bottom: 1.5rem; }
    .amount-val { font-size: 2rem; font-weight: 800; color: var(--text); }
    .amount-sub { font-size: 0.9rem; color: var(--text-muted); }
    .instruction { font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.6; }
    .target-number { font-weight: 700; color: var(--accent-light); font-size: 1.1rem; }
</style>
</head>
<body class="sl-page-body">
<?php include '../partials/navbar.php'; ?>
<div class="sl-page-wrap">
    <div class="sl-card pay-card sl-anim sl-d0">
        <div class="sl-card-body" style="text-align: center;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/93/MTN_Logo.svg/1200px-MTN_Logo.svg.png" alt="MTN MoMo" class="momo-logo">
            <h2 class="sl-card-title">Payment Required</h2>
            <p style="color:var(--text-muted); margin-bottom: 1.5rem;">This job has reached 10+ applicants. To view them, a one-time fee is required.</p>
            
            <?php if($success): ?><div class="sl-alert sl-alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if($error):   ?><div class="sl-alert sl-alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="amount-box">
                <div class="amount-val">10,000 FRW</div>
                <div class="amount-sub">($9.59 USD)</div>
            </div>

            <div class="instruction">
                Please transfer the amount to:<br>
                <span class="target-number">0788833101</span> (MTN Mobile Money)<br>
                Then enter your phone number below to confirm.
            </div>

            <form method="POST">
                <div class="sl-form-group">
                    <input class="sl-input" type="text" name="phone_number" id="p-phone" placeholder=" " required>
                    <label class="floating" for="p-phone">Your MTN Phone Number</label>
                </div>
                <button type="submit" name="confirm_payment" class="sl-btn sl-btn-full">Confirm Payment</button>
            </form>
            
            <div style="margin-top: 1.5rem;">
                <a href="dashboard.php" class="sl-btn-ghost">Cancel and Back</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
