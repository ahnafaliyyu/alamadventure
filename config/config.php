<?php
// config/config.php
// Inisialisasi session dan kredensial admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi kredensial admin (bisa dipindah ke DB nanti)
// Default: username admin, password admin123
const ADMIN_USERS = [
    [
        'username' => 'admin',
        // Gunakan salah satu: password_hash (disarankan) ATAU password_plain (fallback untuk dev)
        'password_hash' => null,
        'password_plain' => 'admin123'
    ]
];

function auth_is_logged_in(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function auth_login(string $username, string $password): bool {
    foreach (ADMIN_USERS as $user) {
        if (hash_equals($user['username'], $username)) {
            $ok = false;
            if (!empty($user['password_hash'])) {
                $ok = password_verify($password, $user['password_hash']);
            }
            if (!$ok && isset($user['password_plain'])) {
                $ok = hash_equals((string)$user['password_plain'], $password);
            }
            if ($ok) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                // Regenerate session ID untuk mencegah fixation
                session_regenerate_id(true);
                return true;
            }
        }
    }
    return false;
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit();
}
