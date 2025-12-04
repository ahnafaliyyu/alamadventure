<?php
require 'config/init.php'; // Koneksi database

// --- QUERY PRODUK UNGGULAN (TOP 10 BEST SELLER) ---
// Logika: Menggabungkan tabel products dengan order_items
// Menghitung jumlah qty yang terjual per produk
// Hanya menghitung pesanan yang TIDAK cancelled/failed
$sql_best = "SELECT p.*, 
             COALESCE(SUM(oi.qty), 0) as total_rented
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id 
             AND (o.status != 'cancelled' AND o.status != 'failed')
             GROUP BY p.id
             ORDER BY total_rented DESC, p.id DESC
             LIMIT 10";

$result_best = $conn->query($sql_best);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ALAMADVENTURE SMD</title>
  <link rel="icon" href="public/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="stylesheet" href="public/css/main.css">
</head>

<body class="no-padding-top">
  <div class="nav-main-wrapper">
    <nav class="nav">
      <div class="desktop-nav">
        <div class="logo">
          <img src="/public/logo.png" width="30px" alt="Logo" />
        </div>
        <ul class="nav-menu">
          <li><a href="index.php" class="nav-link active">Beranda</a></li>
          <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
          <li><a href="katalog.php" class="nav-link">Katalog</a></li>
          <li><a href="kontak.php" class="nav-link">Kontak</a></li>
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
            Masuk <i class="fas fa-sign-in-alt"></i>
          </button>
        <?php endif; ?>
      </div>
    </nav>


    <div class="main-content">
      <div class="main-text" data-aos="fade-up" data-aos-duration="800">
        <h1>Sewa Alat Camping<br>Terpercaya di Samarinda</h1>
        <p>
          Perlengkapan camping lengkap dan terawat untuk petualangan outdoor kamu.
          Harga bersahabat, booking cepat, layanan ramah.
        </p>
        <div class="button-container">
          <a href="katalog.php">
            <button class="active-button">Lihat Katalog</button>
          </a>
          <a href="#cara-sewa">
            <button class="action-button">Cara Sewa</button>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Kenapa Kami -->
  <section class="stats-container" data-aos="fade-up" data-aos-duration="800">
    <div class="left-section">
      <h2 class="main-title">
        Kenapa Memilih <strong>Alam Adventure?</strong>
      </h2>
      <p class="description">
        Perlengkapan lengkap, berkualitas, dan layanan siap membantu kapan saja
        untuk petualangan camping-mu di Kalimantan Timur.
      </p>

      <div class="stats-grid">
        <div class="stat-item">
          <i class="fas fa-users icon-dark"></i>
          <p class="stat-number">330+</p>
          <p class="stat-label">Pelanggan Puas</p>
        </div>
        <div class="stat-item">
          <i class="fas fa-clock icon-dark"></i>
          <p class="stat-number">4 Tahun</p>
          <p class="stat-label">Pengalaman</p>
        </div>
        <div class="stat-item">
          <i class="fas fa-box icon-dark"></i>
          <p class="stat-number">50+</p>
          <p class="stat-label">Produk Tersedia</p>
        </div>
      </div>
    </div>

    <div class="right-section">
      <div class="feature-card">
        <div class="card-icon-box">
          <i class="fas fa-shield-alt card-icon"></i>
        </div>
        <div class="card-content">
          <h3 class="card-title">Peralatan Terawat</h3>
          <p class="card-text">Semua perlengkapan dicek dan dibersihkan sebelum disewakan.</p>
        </div>
      </div>

      <div class="feature-card">
        <div class="card-icon-box">
          <i class="fas fa-bolt card-icon"></i>
        </div>
        <div class="card-content">
          <h3 class="card-title">Proses Cepat</h3>
          <p class="card-text">Booking online, konfirmasi instan, ambil barang langsung.</p>
        </div>
      </div>

      <div class="feature-card">
        <div class="card-icon-box">
          <i class="fas fa-tag card-icon"></i>
        </div>
        <div class="card-content">
          <h3 class="card-title">Harga Bersahabat</h3>
          <p class="card-text">Tarif sewa terjangkau dengan kualitas terjamin.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="product-unggulan-section" id="product-unggulan" data-aos="fade-up" data-aos-duration="800">
    <div class="header-content">
      <h2 class="section-title">Perlengkapan Terlaris</h2>
      <p class="section-description">Perlengkapan terbaik yang paling sering disewa oleh petualang lainnya.</p>
    </div>

    <div class="slider-container">
      <div class="swiper" id="productSwiper">
        <div class="swiper-wrapper">

          <?php if ($result_best && $result_best->num_rows > 0): ?>
            <?php while ($row = $result_best->fetch_assoc()):
              // Fallback gambar jika kosong
              $imgUrl = !empty($row['image_url']) ? $row['image_url'] : 'public/logo.png';
              ?>
              <div class="swiper-slide">
                <div class="product-card">
                  <span class="price-tag">Rp <?= number_format($row['price_per_day'], 0, ',', '.') ?>/hari</span>

                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($row['name']) ?>"
                    class="card-image" />

                  <div class="card-overlay">
                    <h3 class="card-title"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="card-subtitle">
                      <i class="fas fa-star"></i> 5.0 •
                      <?php if ($row['total_rented'] > 0): ?>
                        Disewa <?= $row['total_rented'] ?>x
                      <?php else: ?>
                        Produk Baru
                      <?php endif; ?>
                    </p>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align:center; width:100%;">Belum ada data produk unggulan.</p>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <div class="controls-row">
      <a href="katalog.php"><button class="view-more">Lihat Semua</button></a>
      <div class="slider-arrows">
        <button class="arrow prev-arrow" id="prevArrow"><i class="fas fa-chevron-left"></i></button>
        <button class="arrow next-arrow" id="nextArrow"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
  </section>

  <!-- Cara Sewa Section -->
  <section id="cara-sewa" class="cara-sewa-section" data-aos="fade-up" data-aos-duration="800">
    <h2 class="section-title">Cara Sewa</h2>
    <p class="section-subtitle">
      Proses mudah dan cepat dalam 5 langkah sederhana
    </p>

    <div class="steps-container">
      <div class="step-item">
        <div class="step-number">1</div>
        <div class="step-icon"><i class="fas fa-search"></i></div>
        <h3 class="step-title">Pilih Produk</h3>
        <p class="step-text">Buka katalog dan pilih peralatan yang kamu butuhkan</p>
      </div>

      <div class="step-item">
        <div class="step-number">2</div>
        <div class="step-icon"><i class="fas fa-cart-plus"></i></div>
        <h3 class="step-title">Masukkan Keranjang</h3>
        <p class="step-text">Tambahkan produk ke keranjang belanja</p>
      </div>

      <div class="step-item">
        <div class="step-number">3</div>
        <div class="step-icon"><i class="fas fa-calendar-alt"></i></div>
        <h3 class="step-title">Pilih Tanggal</h3>
        <p class="step-text">Tentukan durasi sewa sesuai kebutuhan</p>
      </div>

      <div class="step-item">
        <div class="step-number">4</div>
        <div class="step-icon"><i class="fas fa-credit-card"></i></div>
        <h3 class="step-title">Bayar</h3>
        <p class="step-text">Selesaikan pembayaran via transfer atau cash</p>
      </div>

      <div class="step-item">
        <div class="step-number">5</div>
        <div class="step-icon"><i class="fas fa-box-open"></i></div>
        <h3 class="step-title">Ambil Barang</h3>
        <p class="step-text">Ambil peralatan di lokasi atau pilih layanan antar</p>
      </div>
    </div>
  </section>

  <!-- Ketentuan Section -->
  <section class="ketentuan-section" data-aos="fade-up" data-aos-duration="800">
    <h2 class="ketentuan-title">Ketentuan Sewa</h2>
    <p class="ketentuan-subtitle">
      Mohon perhatikan ketentuan berikut sebelum menyewa
    </p>

    <div class="ketentuan-container">
      <div class="ketentuan-card">
        <i class="fas fa-id-card ketentuan-icon"></i>
        <h3 class="ketentuan-card-title">Jaminan Identitas</h3>
        <p>KTP/SIM/Kartu Pelajar wajib diserahkan sebagai jaminan selama masa sewa.</p>
      </div>

      <div class="ketentuan-card">
        <i class="fas fa-undo ketentuan-icon"></i>
        <h3 class="ketentuan-card-title">Pengembalian</h3>
        <p>Barang dikembalikan tepat waktu dalam kondisi bersih dan lengkap.</p>
      </div>

      <div class="ketentuan-card">
        <i class="fas fa-exclamation-circle ketentuan-icon"></i>
        <h3 class="ketentuan-card-title">Denda</h3>
        <p>Denda berlaku untuk keterlambatan, kerusakan, atau kehilangan barang.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="footer-inner">

      <!-- Brand Section -->
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="public/logo.png" alt="ALAMADVENTURE SMD" />
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

      <a href="login.php" class="option-user">
        <i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan
      </a>

      <div class="modal-divider"><span>ATAU</span></div>

      <a href="admin/login.php" class="option-admin">
        <i class="fas fa-lock"></i> Masuk sebagai Admin
      </a>
    </div>
  </div>

  <script src="./public/js/nav.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="public/js/main.js"></script>
</body>

</html>