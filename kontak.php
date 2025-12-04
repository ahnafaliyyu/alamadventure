<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ALAMADVENTURE_SMD</title>
  <link rel="icon" href="/public/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="./public/css/main.css" />
  <link rel="stylesheet" href="./public/css/kontak.css" />
</head>

<body>
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
        <li><a href="index.php" class="nav-link">Beranda</a></li>
        <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
        <li><a href="katalog.php" class="nav-link">Katalog</a></li>
        <li><a href="kontak.php" class="nav-link active">Kontak</a></li>
      </ul>
    </div>
    <div class="btn-kanan">
      <a href="keranjang.php" class="nav-link" id="cartLink">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
      </a>

      <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
        <a href="admin/index.php" style="background:#d35400; color:white;">
          <i class="fas fa-user-shield"></i> Panel
        </a>

      <?php elseif (isset($_SESSION['user_id'])): ?>
        <a href="riwayat.php" title="Akun Saya">
          <i class="fas fa-user"></i>
        </a>

      <?php else: ?>
        <button onclick="openLoginModal()">
          <i class="fas fa-sign-in-alt"></i>
        </button>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Contact Content -->
  <div class="contact-container">
    <h1 class="contact-title">HUBUNGI KAMI</h1>

    <div class="contact-content" data-aos-delay="200">
      <!-- Left Side - Contact Info -->
      <div class="contact-info">
        <div class="info-card">
          <h3><i class="fa-solid fa-location-dot"></i> Alamat</h3>
          <p>Jl. Contoh No. 123, Samarinda, Kalimantan Timur</p>
        </div>

        <div class="info-card">
          <h3><i class="fa-solid fa-envelope"></i> Email</h3>
          <p>alamadventure@example.com</p>
        </div>

        <div class="info-card">
          <h3><i class="fa-solid fa-phone"></i> Telepon</h3>
          <p>+62 800-0000-000</p>
        </div>

        <div class="info-card">
          <h3><i class="fa-solid fa-clock"></i> Jam Operasional</h3>
          <p>Senin - Minggu<br />08:00 - 20:00 WITA</p>
        </div>

        <div class="social-buttons">
          <a href="https://wa.me/6280000000000" target="_blank" class="social-btn whatsapp-btn">
            <i class="fa-brands fa-whatsapp"></i> <span>WhatsApp</span>
          </a>
          <a href="https://instagram.com" target="_blank" class="social-btn instagram-btn">
            <div class="icon cart-icon">
              <i class="fa-brands fa-instagram"></i>
            </div>
            <span>Instagram</span>
          </a>
        </div>
      </div>

      <!-- Right Side - Map -->
      <div class="map-container">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127641.98276849588!2d117.06326394335938!3d-0.5021164999999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df67f753f1c5e5b%3A0x2e446e3f5b6f7e3d!2sSamarinda%2C%20Kota%20Samarinda%2C%20Kalimantan%20Timur!5e0!3m2!1sid!2sid!4v1234567890123!5m2!1sid!2sid"
          width="100%" height="100%" style="border: 0; border-radius: 20px" allowfullscreen="" loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>
  </div>

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

      <div id="loginChoiceModal" class="login-modal-overlay">
    <div class="login-modal-content">
      <button class="btn-close-modal" onclick="closeLoginModal()">&times;</button>

      <div class="login-modal-header">
        <h3>Selamat Datang!</h3>
        <p>Silakan pilih cara masuk Anda</p>
      </div>

      <a href="login.php" class="option-user">
        <i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan
      </a>

      <div class="modal-divider"><span>ATAU</span></div>

      <a href="admin/login.php" class="option-admin">
        <i class="fas fa-lock"></i> Masuk sebagai Admin
      </a>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="public/js/kontak.js"></script>
  <script src="public/js/script.js"></script>
  <script src="public/js/nav.js"></script>
</body>

</html>