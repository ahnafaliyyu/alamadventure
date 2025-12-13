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
  <link rel="stylesheet" href="./public/css/tentang.css" />
  <link rel="stylesheet" href="./public/css/main.css" />
</head>

<body class="no-padding-top">
  <nav class="nav">
    <div class="desktop-nav">
      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="logo">
        <img src="public/logo.png" width="30px" alt="Logo" />
      </div>

      <ul class="nav-menu" id="navMenu">
        <li><a href="beranda" class="nav-link">Beranda</a></li>
        <li><a href="tentang-kami" class="nav-link">Tentang Kami</a></li>
        <li><a href="katalog" class="nav-link">Katalog</a></li>
        <li><a href="kontak" class="nav-link">Kontak</a></li>
      </ul>
    </div>
    <div class="btn-kanan">
      <a href="keranjang" class="nav-link" id="cartLink">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
      </a>

      <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
        <a href="dashboard-admin" style="background:#d35400; color:white;">
          <i class="fas fa-user-shield"></i> Panel
        </a>

      <?php elseif (isset($_SESSION['user_id'])): ?>
        <a href="profil" title="Akun Saya">
          <i class="fas fa-user"></i>
        </a>

      <?php else: ?>
        <button onclick="openLoginModal()">
          <i class="fas fa-sign-in-alt"></i>
        </button>
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
          <li><a href="index.php">Beranda</a></li>
          <li><a href="tentang-kami.php">Tentang Kami</a></li>
          <li><a href="katalog.php">Katalog Lengkap</a></li>
          <li><a href="kontak.php">Hubungi Kami</a></li>
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

  <div id="loginChoiceModal" class="login-modal-overlay">
    <div class="login-modal-content">
      <button class="btn-close-modal" onclick="closeLoginModal()">&times;</button>
      <div class="login-modal-header">
        <h3>Selamat Datang!</h3>
        <p>Silakan pilih cara masuk Anda</p>
      </div>
      <a href="login" class="option-user">
        <i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan
      </a>
      <div class="modal-divider"><span>ATAU</span></div>
      <a href="login-admin" class="option-admin">
        <i class="fas fa-lock"></i> Masuk sebagai Admin
      </a>
    </div>
  </div>

  <script src="public/js/script.js"></script>
  <script src="public/js/nav.js"></script>
</body>

</html>