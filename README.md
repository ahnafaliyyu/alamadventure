# 🏕️ Alam Adventure

Selamat datang di repository source code **Alam Adventure**.
Project ini adalah sistem penyewaan alat camping berbasis web (PHP Native) yang terintegrasi dengan **Midtrans** (Payment Gateway), **Gmail SMTP** (Verifikasi Email), dan **Fonnte** (WhatsApp API).

> **⚠️ PERHATIAN PENTING:**
> Repository ini hanya berisi *Source Code Inti*. File konfigurasi sensitif (`.env`) dan folder vendor tidak disertakan demi keamanan.
>
> **Anda WAJIB mengikuti panduan "Setup Konfigurasi" di bawah ini agar website bisa berjalan.**

---

## 📋 Prasyarat Sistem
Pastikan laptop Anda memiliki:
1.  **PHP** (Minimal versi 8.0)
2.  **Composer** (Manajer dependensi PHP)
3.  **Database** (MySQL / MariaDB)
4.  **Docker** (Opsional, sangat disarankan) atau **XAMPP/Laragon**.

---

## 🚀 Langkah 1: Instalasi

1.  **Clone/Download** repository ini.
2.  Buka terminal/CMD di dalam folder project ini.
3.  Install library yang dibutuhkan (PHPMailer, Midtrans, Dotenv) dengan perintah:
    ```bash
    composer install
    ```
    *Jika folder `vendor/` berhasil muncul, lanjut ke langkah berikutnya.*

---

## ⚙️ Langkah 2: Setup Environment (.env)

Kita menggunakan file `.env` untuk menyimpan password dan settingan rahasia agar tidak terekspos di kode.

1.  Buat file baru bernama **`.env`** di root folder ini (sejajar dengan file `index.php`).
2.  Salin kode di bawah ini ke dalamnya:

```env
# --- DATABASE ---
# Ganti 'localhost' jika pakai XAMPP. Ganti 'db' jika pakai Docker.
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=alamadventure

# --- EMAIL (GMAIL SMTP) ---
# Gunakan 'App Password' Google, BUKAN password login biasa.
SMTP_HOST=smtp.gmail.com
SMTP_USER=email_anda@gmail.com
SMTP_PASS=app_password_anda_16_digit
SMTP_PORT=587

# --- PAYMENT (MIDTRANS) ---
# Ambil dari Dashboard Midtrans Sandbox > Settings > Access Keys
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# --- WHATSAPP (FONNTE) ---
# Token dari dashboard Fonnte.com
FONNTE_TOKEN=token_fonnte_anda
````

-----

## 🔧 Langkah 3: Setup Konfigurasi Backend (WAJIB)

Karena file konfigurasi di repo ini mungkin kosong atau berbeda path-nya di laptop Anda, silakan **Copy-Paste** kode berikut ke file yang bersangkutan di dalam folder **`config/`**.

### 1\. File: `config/init.php`

*File ini adalah pintu utama yang memuat library dan variabel environment.*

```php
<?php
// config/init.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Makassar'); // Sesuaikan timezone

// LOAD LIBRARY (Sesuaikan path 'vendor' jika error)
// Jika folder vendor ada di dalam folder public: '/../vendor/autoload.php'
// Jika folder vendor ada di luar folder public: '/../../vendor/autoload.php'
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env dari root folder
$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->safeLoad();

// Load Config Lain
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auto_cancel.php';

// Helper Function Sederhana
if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) { return "Rp " . number_format($angka, 0, ',', '.'); }
}
if (!function_exists('e')) {
    function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}
?>
```

### 2\. File: `config/database.php`

*File ini menghubungkan PHP ke Database menggunakan data dari .env.*

```php
<?php
// config/database.php

$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'alamadventure';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Return JSON jika diakses via API
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->connect_error]);
        exit();
    }
    die("Koneksi Database Gagal: " . $conn->connect_error . " (Cek file .env)");
}
?>
```

### 3\. File: `config/midtrans.php`

*File ini mengatur pembayaran dan notifikasi WhatsApp.*

```php
<?php
// config/midtrans.php

// Fallback load autoload jika belum ada
if (!class_exists('Midtrans\Config')) {
    $vendorPath = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($vendorPath)) require_once $vendorPath;
}

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey    = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
\Midtrans\Config::$isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true';
\Midtrans\Config::$isSanitized  = ($_ENV['MIDTRANS_IS_SANITIZED'] ?? 'true') === 'true';
\Midtrans\Config::$is3ds        = ($_ENV['MIDTRANS_IS_3DS'] ?? 'true') === 'true';

$midtransClientKey = $_ENV['MIDTRANS_CLIENT_KEY'] ?? '';

// Fungsi Kirim WA (Fonnte)
if (!function_exists('sendFonnte')) {
    function sendFonnte($target, $message) {
        $token = $_ENV['FONNTE_TOKEN'] ?? '';
        if (empty($token)) return false;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => '[https://api.fonnte.com/send](https://api.fonnte.com/send)',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('target' => $target, 'message' => $message),
            CURLOPT_HTTPHEADER => array("Authorization: $token"),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
?>
```

### 4\. File: `config/mail.php`

*File ini mengatur pengiriman email verifikasi.*

```php
<?php
// config/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;

        $mail->setFrom($mail->Username, 'Alam Adventure');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}
?>
```

### 5\. File: `config/config.php`

*Fungsi autentikasi dan helper global.*

```php
<?php
// config/config.php
require_once __DIR__ . '/database.php';

if (!function_exists('getSetting')) {
    function getSetting($key) {
        global $conn;
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($row = $res->fetch_assoc()) ? $row['setting_value'] : '';
    }
}

if (!function_exists('auth_login')) {
    function auth_login($username, $password) {
        global $conn;
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout() { session_destroy(); }
}
if (!function_exists('redirect')) {
    function redirect($path) { header("Location: " . $path); exit; }
}
if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in() { return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true; }
}
?>
```

### 6\. File: `config/auto_cancel.php`

*Script untuk membatalkan pesanan expired secara otomatis.*

```php
<?php
// config/auto_cancel.php
if (!isset($conn)) require_once __DIR__ . '/database.php';

if ($conn) {
    // Batalkan order pending yang sudah lewat waktu expires_at
    $conn->query("UPDATE orders SET status = 'cancelled', snap_token = NULL WHERE status = 'pending' AND expires_at IS NOT NULL AND expires_at < NOW()");
}
?>
```

-----

## 🗄️ Langkah 4: Setup Database

1.  Buat database baru di phpMyAdmin bernama `alamadventure`.
2.  Import file **`init.sql`** yang ada di repository ini.
3.  Pastikan tabel `users`, `products`, `orders` berhasil dibuat.

-----

## 🖥️ Langkah 5: Menjalankan Aplikasi

Silakan pilih metode yang sesuai dengan OS/Setup Anda.

### OPSI A: Menggunakan Docker (Rekomendasi)

Buat file `docker-compose.yml` di folder ini, lalu isi dengan:

```yaml
version: '3.8'
services:
  web:
    image: php:8.1-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html
    command: bash -c "a2enmod rewrite && docker-php-ext-install mysqli && apache2-foreground"
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: alamadventure
    ports:
      - "3306:3306"
```

**Cara Jalankan:**

1.  Edit `.env` -\> set `DB_HOST=db` dan `DB_PASS=root`.
2.  Jalankan terminal: `docker-compose up -d`.
3.  Akses web di: `http://localhost:8080`.

### OPSI B: Menggunakan XAMPP / Laragon

1.  Pastikan `.env` -\> set `DB_HOST=localhost` dan `DB_PASS=` (kosongkan jika default).
2.  Pindahkan folder ini ke `htdocs` atau `www`.
3.  Akses di browser: `http://localhost/nama_folder_ini/`.

-----

## 🛡️ Login Admin

  * **URL:** `/admin`
  * **Username:** `admin`
  * **Password:** `admin123`