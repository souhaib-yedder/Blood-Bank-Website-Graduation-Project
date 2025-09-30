<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['donor', 'staff', 'admin', 'hospital'])) {
    http_response_code(403);
    exit();
}

require_once 'db.php';
require_once 'class_Notification.php';

$db = new Database();
$conn = $db->connect();
$notificationObj = new Notification($conn);

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($action === 'read') {
    $notificationObj->markAsRead($id);
} elseif ($action === 'delete') {
    $notificationObj->deleteNotification($id);
}
