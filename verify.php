<?php
require 'config/init.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Cek token di database
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Aktifkan User
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->bind_param("s", $token);

        if ($update->execute()) {
            echo "<script>alert('Akun berhasil diverifikasi! Silakan Login.'); window.location='login.php';</script>";
        } else {
            echo "Gagal mengupdate database.";
        }
    } else {
        echo "<h3>Link tidak valid atau akun sudah diverifikasi.</h3><a href='login.php'>Login disini</a>";
    }
} else {
    header("Location: index.php");
}
?>