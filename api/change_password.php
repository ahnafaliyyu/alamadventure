<?php
// api/change_password.php
require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$old_pass = $_POST['old_password'] ?? '';
$new_pass = $_POST['new_password'] ?? '';
$conf_pass = $_POST['confirm_password'] ?? '';

if (empty($old_pass) || empty($new_pass)) {
    echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
    exit;
}

if ($new_pass !== $conf_pass) {
    echo json_encode(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok.']);
    exit;
}

// Cek Password Lama
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res && password_verify($old_pass, $res['password'])) {
    // Password lama benar, update ke yang baru
    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $new_hash, $user_id);

    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Password lama salah.']);
}
?>