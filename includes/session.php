<?php
$sessionPath = ini_get('session.save_path');
if (!$sessionPath || !is_dir($sessionPath) || !is_writable($sessionPath)) {
    $tmpPath = sys_get_temp_dir();
    if (is_dir($tmpPath) && is_writable($tmpPath)) {
        session_save_path($tmpPath);
    }
}
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$sessionEmail = $_SESSION['email'] ?? null;
$sessionUsername = $_SESSION['username'] ?? null;

require_once __DIR__ . '/i18n.php';

