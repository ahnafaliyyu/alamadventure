<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    redirect('login.php');
}

if (auth_login($username, $password)) {
    $target = $_SESSION['intended_url'] ?? 'index.php';
    unset($_SESSION['intended_url']);
    // Normalisasi target agar tidak open redirect
    if (strpos($target, '/admin/') === false && strpos($target, 'index.php') === false) {
        $target = 'index.php';
    }
    header('Location: ' . $target);
    exit();
}

$_SESSION['login_error'] = 'Kredensial tidak valid.';
redirect('login.php');
