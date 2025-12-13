<?php
// katalog.php
require 'config/init.php';

// --- SERVER SIDE LOGIC ---
if (isset($_POST['ajax_add_to_cart'])) {
  header('Content-Type: application/json');
  $product_id = $_POST['product_id'];
  $qty = 1;
  $stmt = $conn->prepare("SELECT p.id, p.name, p.price_per_day, p.image_url, p.stock, (p.stock - COALESCE((SELECT SUM(oi.qty) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.status != 'cancelled' AND o.status != 'failed' AND o.rental_status != 'returned'), 0)) as available_stock FROM products p WHERE p.id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $prod = $stmt->get_result()->fetch_assoc();
  if ($prod) {
    if ($prod['available_stock'] < $qty) {
      echo json_encode(['success' => false, 'message' => 'Stok habis!']);
      exit;
    }
    if ((isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['qty'] : 0) + $qty > $prod['available_stock']) {
      echo json_encode(['success' => false, 'message' => 'Stok tidak cukup.']);
      exit;
    }
    if (isset($_SESSION['cart'][$product_id])) {
      $_SESSION['cart'][$product_id]['qty'] += $qty;
    } else {
      $_SESSION['cart'][$product_id] = ['name' => $prod['name'], 'price' => $prod['price_per_day'], 'image' => $prod['image_url'], 'qty' => $qty];
    }
    echo json_encode(['success' => true, 'message' => 'Masuk keranjang!', 'cartCount' => count($_SESSION['cart'])]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
  }
  exit;
}
if (isset($_GET['ajax_load_products'])) {
  header('Content-Type: application/json');
  $limit = 8;
  $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
  $offset = ($page - 1) * $limit;
  $search = isset($_GET['q']) ? $_GET['q'] : '';
  $kategori_aktif = isset($_GET['category']) ? $_GET['category'] : 'semua';
  $sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';
  $where = ["1=1"];
  $params = [];
  $types = "";
  if (!empty($search)) {
    $where[] = "name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
  }
  if ($kategori_aktif != 'semua') {
    if ($kategori_aktif == 'tenda')
      $where[] = "(name LIKE '%tenda%' OR name LIKE '%flysheet%')";
    elseif ($kategori_aktif == 'lampu')
      $where[] = "(name LIKE '%lampu%' OR name LIKE '%senter%' OR name LIKE '%headlamp%')";
    elseif ($kategori_aktif == 'alat-masak')
      $where[] = "(name LIKE '%kompor%' OR name LIKE '%nesting%' OR name LIKE '%gas%')";
    elseif ($kategori_aktif == 'paket')
      $where[] = "name LIKE '%paket%'";
    elseif ($kategori_aktif == 'lainnya')
      $where[] = "(name NOT LIKE '%tenda%' AND name NOT LIKE '%flysheet%' AND name NOT LIKE '%lampu%' AND name NOT LIKE '%senter%' AND name NOT LIKE '%kompor%' AND name NOT LIKE '%paket%')";
  }
  $sql_where = implode(" AND ", $where);
  $sql_order = "ORDER BY p.id DESC";
  if ($sort == 'termurah')
    $sql_order = "ORDER BY p.price_per_day ASC";
  if ($sort == 'termahal')
    $sql_order = "ORDER BY p.price_per_day DESC";

  $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE $sql_where");
  if (!empty($params))
    $stmt_count->bind_param($types, ...$params);
  $stmt_count->execute();
  $total_data = $stmt_count->get_result()->fetch_assoc()['total'];
  $total_pages = ceil($total_data / $limit);

  $sql_data = "SELECT p.*, (p.stock - COALESCE((SELECT SUM(oi.qty) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.status != 'cancelled' AND o.status != 'failed' AND o.rental_status != 'returned'), 0)) as available_stock FROM products p WHERE $sql_where $sql_order LIMIT ? OFFSET ?";
  $params[] = $limit;
  $params[] = $offset;
  $types .= "ii";
  $stmt = $conn->prepare($sql_data);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $products = [];
  while ($row = $res->fetch_assoc()) {
    $row['available_stock'] = (int) $row['available_stock'];
    $products[] = $row;
  }
  echo json_encode(['success' => true, 'products' => $products, 'pagination' => ['current_page' => $page, 'total_pages' => $total_pages]]);
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Katalog - ALAMADVENTURE SMD</title>
  <link rel="icon" href="/public/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="./public/css/main.css" />
  <link rel="stylesheet" href="./public/css/katalog.css" />
  <style>
    .toast-notification {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translate(-50%, -100px);
      background: #4CAF50;
      color: white;
      padding: 12px 24px;
      border-radius: 50px;
      opacity: 0;
      transition: all 0.3s;
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .toast-notification.active {
      transform: translate(-50%, 0);
      opacity: 1;
    }

    .toast-notification.error {
      background: #f44336;
    }
  </style>
</head>

<body>
  <nav class="nav">
    <div class="desktop-nav">
      <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
      <div class="logo"><img src="public/logo.png" width="30px" alt="Logo" /></div>
      <ul class="nav-menu" id="navMenu">
        <li><a href="beranda" class="nav-link">Beranda</a></li>
        <li><a href="tentang-kami" class="nav-link">Tentang Kami</a></li>
        <li><a href="katalog" class="nav-link active">Katalog</a></li>
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

  <div class="product-detail-section" id="productSection">

    <div class="mobile-catalog-header">
      <div class="mobile-search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="mobileSearchInput" placeholder="Cari alat camping..." autocomplete="off">
      </div>
      <div class="mobile-category-scroll">
        <button class="cat-pill active" data-val="semua">Semua</button>
        <button class="cat-pill" data-val="tenda">Tenda</button>
        <button class="cat-pill" data-val="lampu">Lampu</button>
        <button class="cat-pill" data-val="alat-masak">Masak</button>
        <button class="cat-pill" data-val="paket">Paket</button>
        <button class="cat-pill" data-val="lainnya">Lainnya</button>
      </div>
    </div>

    <div class="filter-container-wrapper" id="filterContainer">
      <div class="category-filter">
        <div class="btn-filter">
          <button class="filter-btn active" data-val="semua">Semua</button>
          <button class="filter-btn" data-val="tenda">Tenda</button>
          <button class="filter-btn" data-val="lampu">Lampu</button>
          <button class="filter-btn" data-val="alat-masak">Alat Masak</button>
          <button class="filter-btn" data-val="paket">Paket</button>
          <button class="filter-btn" data-val="lainnya">Lainnya</button>
        </div>
        <div class="search-container">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Cari produk..." class="search-input" />
        </div>
      </div>
    </div>

    <div class="product-grid" id="productGrid">
      <p style="grid-column:1/-1; text-align:center; padding:50px;">Memuat Produk...</p>
    </div>

    <div id="paginationContainer" class="pagination-wrapper"></div>
  </div>

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
      <a href="login" class="option-user">
        <i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan
      </a>
      <div class="modal-divider"><span>ATAU</span></div>
      <a href="login-admin" class="option-admin">
        <i class="fas fa-lock"></i> Masuk sebagai Admin
      </a>
    </div>
  </div>


  <script src="public/js/katalog.js"></script>
  <script src="public/js/nav.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => { if (typeof loadProducts === 'function') loadProducts(); });
    function showToast(m, e = false) {
      let t = document.createElement('div'); t.className = 'toast-notification' + (e ? ' error' : '');
      t.innerHTML = `<i class="fa-solid fa-${e ? 'circle-exclamation' : 'circle-check'}"></i> ${m}`;
      document.body.appendChild(t); setTimeout(() => t.classList.add('active'), 10);
      setTimeout(() => { t.classList.remove('active'); setTimeout(() => t.remove(), 300); }, 2000);
    }
  </script>
</body>

</html>