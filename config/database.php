<?php
// config/database.php

// Konfigurasi Database untuk Docker
$servername = "db";      // Hostname dari service docker-compose
$username = "root";
$password = "250507"; // Password dari environment docker-compose
$dbname = "alamadventure";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    // Set header JSON agar frontend mengerti respon error ini
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit(); // Hentikan script jika koneksi gagal
}
?>