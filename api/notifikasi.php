<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'unread';

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    db_query('UPDATE notifikasi SET is_read = 1 WHERE user_id = ?', 'i', [$userId]);
    echo json_encode(['success' => true]);
    exit;
}

$unread = db_fetch_one('SELECT COUNT(*) AS c FROM notifikasi WHERE user_id = ? AND is_read = 0', 'i', [$userId]);
$items = db_fetch_all(
    'SELECT pesan, created_at FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 8',
    'i', [$userId]
);

echo json_encode([
    'success' => true,
    'unread'  => (int)($unread['c'] ?? 0),
    'items'   => $items,
]);
