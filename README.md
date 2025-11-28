# Alam Adventure - Sistem Penyewaan Alat Camping

**Alam Adventure** adalah aplikasi berbasis web untuk menyewakan peralatan camping dan outdoor (Tenda, Kompor, Tas, dll). Aplikasi ini dilengkapi dengan panel Admin untuk manajemen produk dan transaksi, serta terintegrasi dengan Payment Gateway **Midtrans** untuk pembayaran online otomatis.

![Tech Stack](https://img.shields.io/badge/PHP-Native-blue)
![Database](https://img.shields.io/badge/MySQL-Database-orange)
![Payment](https://img.shields.io/badge/Midtrans-Gateway-green)
![Frontend](https://img.shields.io/badge/HTML5-CSS3-red)

---

## 📋 Fitur Utama

### 👤 Halaman Pengunjung (User)
- Katalog Produk dengan Filter (Kategori, Harga).
- Detail Produk & Stok Real-time.
- Keranjang Belanja.
- Checkout & Pembayaran Online (via Midtrans).
- Integrasi WhatsApp untuk notifikasi.

### 🛡️ Halaman Admin
- Dashboard Statistik (Pendapatan, Jumlah Transaksi).
- Manajemen Produk (Tambah, Edit, Hapus, Upload Gambar).
- Manajemen Transaksi (Update Status, Lihat Detail).
- Cetak Faktur/Invoice.

---

## ⚙️ Persyaratan Sistem (Prerequisites)

Sebelum memulai, pastikan Anda memiliki:
1.  **PHP** >= 7.4 (Disarankan 8.0+).
2.  **MySQL/MariaDB**.
3.  **Composer** (Opsional, jika menggunakan library Midtrans via composer).
4.  Akun **Midtrans** (Mode Sandbox untuk testing).

---

## 🚀 Instalasi & Konfigurasi

Pilih salah satu metode instalasi server di bawah ini:

### Opsi 1: Menggunakan XAMPP (Windows/Mac/Linux)

1.  **Download & Install XAMPP**.
2.  Buka folder `htdocs` (biasanya di `C:\xampp\htdocs`).
3.  Buat folder baru bernama `alamadventure`.
4.  Copy semua file proyek ini ke dalam folder `htdocs/alamadventure`.
5.  Nyalakan **Apache** dan **MySQL** pada XAMPP Control Panel.
6.  **Database:**
    - Buka `http://localhost/phpmyadmin`.
    - Buat database baru dengan nama `alamadventure`.
    - Import file `init.sql` yang ada di root folder proyek ke database tersebut.
7.  **Akses Web:** Buka browser dan kunjungi `http://localhost/alamadventure`.

### Opsi 2: Menggunakan Laragon (Windows - Recommended)

1.  **Download & Install Laragon**.
2.  Buka folder `www` (biasanya di `C:\laragon\www`).
3.  Clone/Copy folder proyek ini menjadi `C:\laragon\www\alamadventure`.
4.  Buka aplikasi Laragon, klik **Start All**.
5.  Laragon biasanya akan membuat *Pretty URL* otomatis.
6.  **Database:**
    - Klik tombol **Database** di Laragon (HeidiSQL).
    - Buat database baru bernama `alamadventure`.
    - Jalankan query dari file `init.sql`.
7.  **Akses Web:** Buka browser dan kunjungi `http://alamadventure.test`.

### Opsi 3: Menggunakan Docker (Advanced)

Jika Anda ingin menggunakan Docker, buat file `docker-compose.yml` di root folder dengan isi berikut:

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
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: alamadventure
      MYSQL_ROOT_PASSWORD: root
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
volumes:
  db_data:
````

**Langkah Docker:**

1.  Jalankan `docker-compose up -d`.
2.  Import database `init.sql` ke container MySQL (bisa via GUI client menghubungkan ke port 3306).
3.  Akses web di `http://localhost:8080`.

-----

## 🔧 Konfigurasi Penting (Wajib Dilakukan)

Aplikasi tidak akan berjalan dengan benar tanpa file konfigurasi berikut. Karena folder `config/` biasanya di-*ignore* oleh git untuk keamanan, Anda harus membuatnya secara manual.

### 1\. Konfigurasi Database

Buat file `config/database.php`:

```php
<?php
$servername = "localhost";
$username = "root"; // Sesuaikan dengan user DB Anda
$password = "";     // Sesuaikan dengan password DB Anda (kosongkan jika XAMPP default)
$dbname = "alamadventure";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
```

### 2\. Konfigurasi Midtrans

Buat file `config/midtrans.php`:

```php
<?php
require_once dirname(__FILE__) . '/../vendor/autoload.php'; // Pastikan path library benar

// Set your Merchant Server Key
\Midtrans\Config::$serverKey = 'SB-Mid-server-XXXXXXXXXXXXXXXX'; // Ganti dengan Server Key Sandbox Anda
// Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
\Midtrans\Config::$isProduction = false;
// Set sanitization on (default)
\Midtrans\Config::$isSanitized = true;
// Set 3DS transaction for credit card to true
\Midtrans\Config::$is3ds = true;
?>
```

*Catatan: Anda perlu menjalankan `composer install` jika folder `vendor/` belum ada, atau download library Midtrans PHP secara manual.*

### 3\. Konfigurasi Umum (Optional)

Buat file `config/config.php` (jika digunakan untuk helper/base URL):

```php
<?php
session_start();
// Helper functions
function base_url($path = '') {
    return '/alamadventure/' . $path; // Sesuaikan jika folder project berbeda
}

function redirect($path) {
    header("Location: " . $path);
    exit;
}

// Auth Helpers
function auth_is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function auth_login($username, $password) {
    // Hardcoded admin untuk contoh, sebaiknya gunakan database
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

function auth_logout() {
    session_destroy();
}
?>
```

-----

## 💳 Integrasi Pembayaran (Midtrans & Ngrok)

Agar pembayaran statusnya bisa otomatis berubah menjadi **Lunas/Paid**, Anda perlu mengatur **Notification URL (Webhook)**.

1.  Karena Anda menjalankannya di Localhost, Midtrans tidak bisa mengirim notifikasi langsung ke komputer Anda.
2.  Gunakan **Ngrok** untuk mem-public-kan localhost Anda.
    ```bash
    ngrok http 80
    ```
3.  Copy URL HTTPS dari Ngrok (contoh: `https://abcd-123.ngrok-free.app`).
4.  Masuk ke Dashboard Midtrans Sandbox -\> Settings -\> Configuration.
5.  Pada bagian **Notification URL**, masukkan:
    `https://abcd-123.ngrok-free.app/alamadventure/api/midtrans_webhook.php`
6.  Edit juga variabel `$ngrok_url` di file `api/midtrans_webhook.php` agar link di WhatsApp benar.

-----

## 🔐 Akun Default

Untuk masuk ke halaman Admin:

  - **URL:** `/admin/login.php`
  - **Username:** `admin`
  - **Password:** `admin123`
    *(Ubah logic di `config/config.php` atau `admin/do_login.php` untuk keamanan lebih lanjut)*

-----

## 📂 Struktur Folder

```
alamadventure/
├── admin/              # Halaman & Logika Admin
├── api/                # Endpoint API (Add Cart, Payment, Webhook)
├── config/             # Konfigurasi DB & Midtrans
├── middleware/         # Cek Login Admin
├── public/             # Aset (CSS, JS, Images)
├── vendor/             # Library PHP (Midtrans) - via Composer
├── index.php           # Landing Page
├── katalog.php         # Halaman Produk
├── keranjang.php       # Halaman Cart
├── ...
└── README.md
```

## ⚠️ Troubleshooting

1.  **Gambar tidak muncul?**
    Pastikan path di database sesuai. Jika path di DB `public/tenda.png`, pastikan file ada di folder tersebut.
2.  **Midtrans Error 404/500?**
    Pastikan `Server Key` dan `Client Key` di file config sudah benar dan sesuai mode (Sandbox/Production).
3.  **Redirect Loop di Admin?**
    Cek `middleware/auth.php` dan pastikan session tersimpan dengan benar.
