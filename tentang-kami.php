<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tentang Kami - ALAMADVENTURE_SMD</title>
  <link rel="icon" href="/public/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="./public/css/main.css" />
  <link rel="stylesheet" href="./public/css/tentang.css" />
</head>

<body>
  <nav class="nav">
    <div class="desktop-nav">
      <div class="logo">
        <img src="/public/logo.png" width="30px" alt="Logo" />
      </div>
      <ul class="nav-menu">
        <li><a href="index.php" class="nav-link">Beranda</a></li>
        <li>
          <a href="tentang-kami.php" class="nav-link">Tentang Kami</a>
        </li>
        <li><a href="katalog.php" class="nav-link">Katalog</a></li>
        <li><a href="kontak.php" class="nav-link">Kontak</a></li>
      </ul>
    </div>
    <div class="btn-kanan">
      <a href="keranjang.php" class="nav-link"><i
          class="fas fa-shopping-cart"></i><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></a>
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <a href="/admin/index.php">Admin</a>
      <?php else: ?>
        <a href="/admin/login.php">Login</a>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Tentang Kami</h1>

      <div class="content-card">
        <div class="content-grid">
          <!-- Left Side -->
          <div class="content-left">
            <div class="logo-container">
              <img src="/public/logo.png" alt="ALAMADVENTURE SMD">
            </div>
            <div class="badge">SEJAK 2022</div>
            <div class="stats-container">
              <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Pelanggan</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">100+</div>
                <div class="stat-label">Peralatan</div>
              </div>
            </div>
          </div>

          <!-- Right Side -->
          <div class="content-right">
            <h2 class="section-title">Siapa Kami</h2>
            <div class="text-content">
              <p>
                <strong>ALAMADVENTURE SMD</strong> adalah toko sewa perlengkapan kemah dan outdoor yang siap mendukung
                setiap perjalanan alam Anda. Kami menyediakan berbagai peralatan berkualitas seperti tenda berbagai
                kapasitas, sleeping bag, matras, kompor portable, nesting, hingga penerangan.
              </p>
              <p>
                Semua barang dirawat secara rutin, dibersihkan, dan dicek kelayakannya sebelum disewakan, sehingga Anda
                bisa fokus pada pengalaman tanpa khawatir soal perlengkapan. Berlokasi di Kalimantan Timur, kami
                melayani penyewaan harian hingga beberapa hari dengan proses yang mudah, cepat, dan transparan.
              </p>
              <p>
                Tim kami siap memberikan rekomendasi perlengkapan sesuai kebutuhan aktivitas—mulai dari camping
                keluarga, pendakian gunung, hingga kegiatan komunitas.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="footer-inner">

      <!-- Brand Section -->
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="../public/logo.png" alt="ALAMADVENTURE SMD" />
          <h3>ALAMADVENTURE<br>SMD</h3>
        </div>
        <p>
          Sewa perlengkapan camping dan outdoor terpercaya di Kalimantan Timur. Melayani rental peralatan camping
          berkualitas dengan harga terjangkau sejak 2020.
        </p>
      </div>

      <!-- Navigation Section -->
      <div class="footer-navigation">
        <h4>Navigasi</h4>
        <ul>
          <li><a href="index.html">Beranda</a></li>
          <li><a href="index.html#tentang">Tentang Kami</a></li>
          <li><a href="katalog.html">Katalog Lengkap</a></li>
          <li><a href="kontak.html">Hubungi Kami</a></li>
          <li><a href="testimoni.html">Testimoni</a></li>
          <li><a href="faq.html">FAQ</a></li>
        </ul>
      </div>

      <!-- Features Section -->
      <div class="footer-features-section">
        <h4>Keunggulan Kami</h4>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
          </div>
          <span>Peralatan Berkualitas</span>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
          </div>
          <span>Harga Terjangkau</span>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
            </svg>
          </div>
          <span>Pelayanan 24/7</span>
        </div>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© 2025 ALAMADVENTURE SMD • Semua hak cipta dilindungi</p>
    </div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
    AOS.init({ once: true, mirror: false });
  </script>
  <script src="./public/js/script.js"></script>
</body>

</html>