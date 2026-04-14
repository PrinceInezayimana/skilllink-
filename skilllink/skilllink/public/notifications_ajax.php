<?php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'fetch';

if ($action === 'mark_read') {
    mark_all_read($conn, $user_id);
    echo json_encode(['ok' => true]);
    exit();
}

// fetch
$notifs = get_notifications($conn, $user_id, 10);
$unread = count_unread($conn, $user_id);

// Format time_ago
function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60)   . 'm ago';
    if ($diff < 86400)  return floor($diff/3600)  . 'h ago';
    return floor($diff/86400) . 'd ago';
}

$out = [];
foreach ($notifs as $n) {
    $out[] = [
        'id'      => $n['id'],
        'message' => $n['message'],
        'link'    => $n['link'],
        'is_read' => (bool)$n['is_read'],
        'time'    => time_ago($n['created_at']),
    ];
}

echo json_encode(['notifications' => $out, 'unread' => $unread]);
