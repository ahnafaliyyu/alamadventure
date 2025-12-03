<?php
require_once __DIR__ . '/../config/config.php';

// Pastikan hanya ADMIN yang bisa lewat
// User biasa tidak punya session 'admin_logged_in'
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika user biasa mencoba masuk, lempar ke login admin, bukan index user
    redirect('/admin/login.php');
}
?>