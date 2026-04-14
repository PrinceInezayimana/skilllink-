<?php
/**
 * realtime.php — Server-Sent Events (SSE) endpoint
 * 
 * Streams real-time updates to connected clients:
 *   - New notifications for the logged-in user
 *   - New approved jobs (for student dashboard)
 *   - New applicants count (for employer dashboard)
 *   - Live platform stats (for admin dashboard)
 *
 * Usage: EventSource('/realtime.php?role=student&last_notif_id=0&last_job_id=0')
 */

session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

// Client sends the last IDs it already knows about — we only send newer data
$last_notif_id = (int)($_GET['last_notif_id'] ?? 0);
$last_job_id   = (int)($_GET['last_job_id']   ?? 0);

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');    // disable nginx buffering
header('Connection: keep-alive');

// Disable PHP output buffering
if (ob_get_level()) ob_end_clean();

/**
 * Send one SSE event.
 */
function sse(string $event, array $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

/**
 * Send a heartbeat comment so the connection stays alive through proxies.
 */
function heartbeat(): void {
    echo ": ping\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Send an immediate "connected" event so the client knows it's live
sse('connected', ['ok' => true, 'role' => $role]);

$tick = 0;
$max_ticks = 120;   // close after ~2 min; browser auto-reconnects

while ($tick < $max_ticks) {
    // ── 1. New notifications for every role ──────────────────────────────────
    $stmt = $conn->prepare(
        "SELECT id, message, link, is_read, created_at
         FROM notifications
         WHERE user_id = ? AND id > ?
         ORDER BY id ASC"
    );
    $stmt->bind_param("ii", $user_id, $last_notif_id);
    $stmt->execute();
    $new_notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($new_notifs)) {
        $unread_count_stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND is_read=0"
        );
        $unread_count_stmt->bind_param("i", $user_id);
        $unread_count_stmt->execute();
        $unread = (int)$unread_count_stmt->get_result()->fetch_assoc()['cnt'];
        $unread_count_stmt->close();

        foreach ($new_notifs as $n) {
            $diff = time() - strtotime($n['created_at']);
            $time_ago = $diff < 60 ? 'just now' :
                        ($diff < 3600 ? floor($diff/60).'m ago' :
                        ($diff < 86400 ? floor($diff/3600).'h ago' : floor($diff/86400).'d ago'));
            $n['time_ago'] = $time_ago;
            $last_notif_id = max($last_notif_id, (int)$n['id']);
        }
        sse('notifications', [
            'items'  => $new_notifs,
            'unread' => $unread,
        ]);
    }

    // ── 2. Role-specific events ───────────────────────────────────────────────
    if ($role === 'student') {
        // New jobs published since last check
        $stmt = $conn->prepare(
            "SELECT jobs.id, jobs.title, jobs.location, jobs.required_skills, employers.company_name
             FROM jobs
             JOIN employers ON jobs.employer_id = employers.id
             WHERE jobs.status = 'approved' AND jobs.id > ?
             ORDER BY jobs.id ASC"
        );
        $stmt->bind_param("i", $last_job_id);
        $stmt->execute();
        $new_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($new_jobs)) {
            foreach ($new_jobs as &$j) {
                $j['required_skills'] = json_decode($j['required_skills'] ?? '[]', true) ?? [];
                $last_job_id = max($last_job_id, (int)$j['id']);
            }
            unset($j);
            sse('new_jobs', ['jobs' => $new_jobs]);
        }

        // Live application stats
        $stmt = $conn->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM applications
             WHERE student_id = (SELECT id FROM students WHERE user_id = ?)
             GROUP BY status"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $stats = ['applied' => 0, 'accepted' => 0, 'rejected' => 0];
        foreach ($rows as $r) $stats[$r['status']] = (int)$r['cnt'];
        sse('app_stats', $stats);

    } elseif ($role === 'employer') {
        // Live applicant counts per job
        $stmt = $conn->prepare(
            "SELECT jobs.id, jobs.title, COUNT(applications.id) AS app_cnt
             FROM jobs
             LEFT JOIN applications ON applications.job_id = jobs.id
             JOIN employers ON jobs.employer_id = employers.id
             WHERE employers.user_id = ?
             GROUP BY jobs.id"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $job_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        sse('applicant_counts', ['jobs' => $job_counts]);

    } elseif ($role === 'admin') {
        // Live platform stats
        $stats = [
            'students'     => (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='student'")->fetch_assoc()['c'],
            'employers'    => (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='employer'")->fetch_assoc()['c'],
            'jobs'         => (int)$conn->query("SELECT COUNT(*) AS c FROM jobs WHERE status='approved'")->fetch_assoc()['c'],
            'applications' => (int)$conn->query("SELECT COUNT(*) AS c FROM applications")->fetch_assoc()['c'],
        ];
        sse('platform_stats', $stats);
    }

    $tick++;

    // Heartbeat every 5 ticks (~5 seconds)
    if ($tick % 5 === 0) heartbeat();

    sleep(1);

    // Reconnect the DB if it timed out
    if (!$conn->ping()) {
        $conn->connect($GLOBALS['host'] ?? 'localhost', $GLOBALS['user'] ?? 'root', $GLOBALS['pass'] ?? '', $GLOBALS['db'] ?? 'skilllink');
    }

    // Stop if client disconnected
    if (connection_aborted()) break;
}

// Tell client to reconnect after a short delay
sse('reconnect', ['delay' => 3000]);
exit();
