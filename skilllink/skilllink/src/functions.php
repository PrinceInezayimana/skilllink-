<?php
// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check login by role
// FIX: session_start() must already be called before this function is used.
function check_login($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != $role) {
        redirect('../login.php');
    }
}

// Password hashing & verification
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Get user info by ID
// FIX: Now uses mysqli ($conn) consistently with the rest of the codebase
// instead of mixing PDO and mysqli.
function get_user($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user;
}

// JSON decode helper
function decode_json($json) {
    return $json ? json_decode($json, true) : [];
}

// ── Notification helpers ────────────────────────────────────────────────────

/**
 * Push a notification to a user.
 */
function push_notification($conn, $user_id, $message, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

/**
 * Fetch unread notifications for a user (newest first, limit 10).
 */
function get_notifications($conn, $user_id, $limit = 10) {
    $stmt = $conn->prepare(
        "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Count unread notifications.
 */
function count_unread($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    return (int)$cnt;
}

/**
 * Mark all notifications as read for a user.
 */
function mark_all_read($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Avatar URL helper — returns the correct path for an avatar image,
 * or a generated initial-based SVG data URI as fallback.
 * $pic      : the filename stored in DB (e.g. "avatar_abc123.jpg") or null
 * $name     : user's display name for initials fallback
 * $base_url : path prefix from current page to public/ root
 */
function avatar_url($pic, $name, $base_url = '../') {
    if ($pic) {
        // rawurlencode handles spaces/special chars in filenames safely in URLs.
        // Do NOT htmlspecialchars here — the caller's template does that on output.
        return $base_url . 'uploads/avatars/' . rawurlencode($pic);
    }
    // Initials fallback — return a data URI SVG
    $initials = strtoupper(substr($name ?? 'U', 0, 1));
    $second   = strtoupper(substr(strrchr($name ?? '', ' ') ?: '', 1, 1));
    $initials .= $second;
    $colors   = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4'];
    $color    = $colors[ord($initials[0]) % count($colors)];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40">'
         . '<rect width="40" height="40" rx="20" fill="' . $color . '"/>'
         . '<text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" '
         . 'font-family="system-ui" font-size="14" font-weight="700" fill="white">'
         . htmlspecialchars($initials)
         . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
