<?php
// middleware/auth.php
require_once __DIR__ . '/../config/config.php';

if (!auth_is_logged_in()) {
    // Simpan URL yang diminta untuk redirect setelah login
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/admin/index.php';
    redirect('/alamadventure/admin/login.php');
}
