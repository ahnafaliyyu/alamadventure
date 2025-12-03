<?php
// katalog.php
require 'config/init.php';

// --- 1. LOGIKA KERANJANG AJAX ---
if (isset($_POST['ajax_add_to_cart'])) {
  header('Content-Type: application/json');

  $product_id = $_POST['product_id'];
  $qty = 1;

  // Cek stok real-time sebelum menambah ke keranjang
  $stmt = $conn->prepare("SELECT p.id, p.name, p.price_per_day, p.image_url, p.stock,
      (p.stock - COALESCE((
          SELECT SUM(oi.qty) 
          FROM order_items oi 
          JOIN orders o ON oi.order_id = o.id 
          WHERE oi.product_id = p.id 
          AND o.status != 'cancelled' 
          AND o.status != 'failed'
          AND o.rental_status != 'returned'
      ), 0)) as available_stock
      FROM products p WHERE p.id = ?");

  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $prod = $stmt->get_result()->fetch_assoc();

  if ($prod) {
    if ($prod['available_stock'] < $qty) {
      echo json_encode([
        'success' => false,
        'message' => 'Stok barang habis atau sedang dipinjam semua!'
      ]);
      exit;
    }

    // Cek jika di keranjang sudah ada, apakah melebihi stok?
    $currentQtyInCart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['qty'] : 0;
    if (($currentQtyInCart + $qty) > $prod['available_stock']) {
      echo json_encode([
        'success' => false,
        'message' => 'Sisa stok tidak mencukupi permintaan Anda.'
      ]);
      exit;
    }

    if (isset($_SESSION['cart'][$product_id])) {
      $_SESSION['cart'][$product_id]['qty'] += $qty;
    } else {
      $_SESSION['cart'][$product_id] = [
        'name' => $prod['name'],
        'price' => $prod['price_per_day'],
        'image' => $prod['image_url'],
        'qty' => $qty
      ];
    }

    $cartCount = count($_SESSION['cart']);
    echo json_encode([
      'success' => true,
      'message' => 'Berhasil ditambahkan ke keranjang!',
      'cartCount' => $cartCount
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Produk tidak ditemukan'
    ]);
  }
  exit;
}

// --- 2. AJAX LOAD PRODUCTS ---
if (isset($_GET['ajax_load_products'])) {
  header('Content-Type: application/json');

  $limit = 8;
  $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
  $offset = ($page - 1) * $limit;

  $search = isset($_GET['q']) ? $_GET['q'] : '';
  $kategori_aktif = isset($_GET['category']) ? $_GET['category'] : 'semua';
  $sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

  $where_clauses = ["1=1"];
  $params = [];
  $types = "";

  if (!empty($search)) {
    $where_clauses[] = "name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
  }

  if ($kategori_aktif != 'semua') {
    if ($kategori_aktif == 'tenda') {
      $where_clauses[] = "(name LIKE '%tenda%' OR name LIKE '%flysheet%')";
    } elseif ($kategori_aktif == 'lampu') {
      $where_clauses[] = "(name LIKE '%lampu%' OR name LIKE '%senter%' OR name LIKE '%headlamp%')";
    } elseif ($kategori_aktif == 'alat-masak') {
      $where_clauses[] = "(name LIKE '%kompor%' OR name LIKE '%nesting%' OR name LIKE '%gas%')";
    } elseif ($kategori_aktif == 'paket') {
      $where_clauses[] = "name LIKE '%paket%'";
    } elseif ($kategori_aktif == 'lainnya') {
      $where_clauses[] = "(name NOT LIKE '%tenda%' AND name NOT LIKE '%flysheet%' 
                               AND name NOT LIKE '%lampu%' AND name NOT LIKE '%senter%' 
                               AND name NOT LIKE '%kompor%' AND name NOT LIKE '%paket%')";
    }
  }

  $sql_where = implode(" AND ", $where_clauses);

  $sql_order = "ORDER BY p.id DESC";
  if ($sort == 'termurah')
    $sql_order = "ORDER BY p.price_per_day ASC";
  if ($sort == 'termahal')
    $sql_order = "ORDER BY p.price_per_day DESC";

  $sql_count = "SELECT COUNT(*) as total FROM products WHERE $sql_where";
  $stmt_count = $conn->prepare($sql_count);
  if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
  }
  $stmt_count->execute();
  $total_data = $stmt_count->get_result()->fetch_assoc()['total'];
  $total_pages = ceil($total_data / $limit);

  // QUERY UPDATE: Menghitung Available Stock
  $sql_data = "SELECT p.*, 
               (p.stock - COALESCE((
                  SELECT SUM(oi.qty) 
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE oi.product_id = p.id 
                  AND o.status != 'cancelled' 
                  AND o.status != 'failed'
                  AND o.rental_status != 'returned'
               ), 0)) as available_stock
               FROM products p WHERE $sql_where $sql_order LIMIT ? OFFSET ?";

  $params[] = $limit;
  $params[] = $offset;
  $types .= "ii";

  $stmt = $conn->prepare($sql_data);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  $products = [];
  while ($row = $result->fetch_assoc()) {
    $row['available_stock'] = (int) $row['available_stock'];
    if ($row['available_stock'] < 0)
      $row['available_stock'] = 0;
    $products[] = $row;
  }

  echo json_encode([
    'success' => true,
    'products' => $products,
    'pagination' => [
      'current_page' => $page,
      'total_pages' => $total_pages,
      'total_data' => $total_data
    ]
  ]);
  exit;
}

// --- 3. HELPER KATEGORI ---
function tebakKategori($nama)
{
  $nama = strtolower($nama);
  if (strpos($nama, 'tenda') !== false || strpos($nama, 'flysheet') !== false)
    return 'tenda';
  if (strpos($nama, 'lampu') !== false || strpos($nama, 'senter') !== false || strpos($nama, 'headlamp') !== false)
    return 'lampu';
  if (strpos($nama, 'kompor') !== false || strpos($nama, 'nesting') !== false || strpos($nama, 'gas') !== false)
    return 'alat-masak';
  if (strpos($nama, 'paket') !== false)
    return 'paket';
  return 'lainnya';
}

// --- 4. INITIAL SERVER-SIDE LOAD ---
$limit = 8;
$page = 1;
$offset = 0;

$search = '';
$kategori_aktif = 'semua';
$sort = 'terbaru';

// Initial Load juga menggunakan logika stok
$sql_data = "SELECT p.*, 
             (p.stock - COALESCE((
                SELECT SUM(oi.qty) 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.product_id = p.id 
                AND o.status != 'cancelled' 
                AND o.status != 'failed'
                AND o.rental_status != 'returned'
             ), 0)) as available_stock
             FROM products p ORDER BY p.id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql_data);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$sql_count = "SELECT COUNT(*) as total FROM products";
$total_data = $conn->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ALAMADVENTURE_SMD</title>
  <link rel="icon" href="/public/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="./public/css/main.css" />
  <link rel="stylesheet" href="./public/css/katalog.css" />
  <style>
    .toast-notification {
      position: fixed;
      top: 0;
      left: 50%;
      transform: translate(-50%, -140px);
      opacity: 0;
      visibility: hidden;
      transition: transform 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
      background: #4CAF50;
      color: white;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 12px;
      max-width: 300px;
    }

    /* === Kondisi aktif (terlihat) === */
    .toast-notification.active {
      transform: translate(-50%, 10px);
      opacity: 1;
      visibility: visible;
    }

    /* Optional: Add error styling */
    .toast-notification.error {
      background: #f44336;
    }


    .toast-notification i {
      font-size: 20px;
    }

    /* Badge Counter */
    .cart-badge {
      position: relative;
    }

    .cart-badge.updated {
      animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.2);
      }
    }

    /* Loading Overlay */
    .loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
      border-radius: 8px;
    }

    .loading-spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #3498db;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Layout */
    .product-grid {
      position: relative;
      min-height: 400px;
    }

    .product-card {
      animation: fadeIn 0.3s ease-in;
      position: relative;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Style Kartu Habis */
    .product-card.out-of-stock {
      opacity: 0.8;
      filter: grayscale(0.5);
    }
  </style>
</head>

<body>
  <nav class="nav">
    <div class="desktop-nav">
      <div class="logo">
        <img src="/public/logo.png" width="30px" alt="Logo" />
      </div>
      <ul class="nav-menu">
        <li><a href="index.php" class="nav-link">Beranda</a></li>
        <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
        <li><a href="katalog.php" class="nav-link active">Katalog</a></li>
        <li><a href="kontak.php" class="nav-link">Kontak</a></li>
      </ul>
    </div>
    <div class="btn-kanan">
      <a href="keranjang.php" class="nav-link" id="cartLink">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
      </a>
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <a href="/admin/index.php">Admin</a>
      <?php else: ?>
        <a href="/admin/login.php">Login</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="product-detail-section" id="productSection">
    <div class="mobile-action-bar">
      <button class="btn-toggle-filter" id="mobileFilterBtn">
        <i class="fa-solid fa-magnifying-glass"></i> Cari
      </button>

      <div class="sort-wrapper">
        <span class="sort-label">Filter:</span>
        <div class="select-container">
          <select id="sortSelect">
            <option value="terbaru">Semua</option>
            <option value="termurah">Termurah</option>
            <option value="termahal">Termahal</option>
          </select>
        </div>
      </div>
    </div>

    <div class="filter-container-wrapper" id="filterContainer">
      <div class="category-filter">
        <div class="btn-filter">
          <button class="filter-btn active" data-val="semua"><span>Semua</span></button>
          <button class="filter-btn" data-val="tenda"><span>Tenda</span></button>
          <button class="filter-btn" data-val="lampu"><span>Lampu</span></button>
          <button class="filter-btn" data-val="alat-masak"><span>Alat Masak</span></button>
          <button class="filter-btn" data-val="paket"><span>Paket</span></button>
          <button class="filter-btn" data-val="lainnya"><span>Lainnya</span></button>
        </div>

        <div class="search-container">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Cari produk..." class="search-input" />
        </div>
      </div>
    </div>

    <div class="product-grid" id="productGrid">
      <?php
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $category = tebakKategori($row['name']);
          $row['available_stock'] = (int) $row['available_stock'];
          if ($row['available_stock'] < 0)
            $row['available_stock'] = 0;

          $stockClass = $row['available_stock'] > 0 ? '' : 'out-of-stock';
          ?>
          <div class="product-card <?= $stockClass ?>" data-category="<?= $category ?>"
            data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>">

            <?php if ($row['available_stock'] > 0): ?>
              <div
                style="position:absolute; top:10px; left:10px; background:#e8f5e9; color:#2e7d32; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">
                Tersedia: <?= $row['available_stock'] ?>
              </div>
            <?php else: ?>
              <div
                style="position:absolute; top:10px; left:10px; background:#ffebee; color:#c62828; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">
                Habis / Disewa
              </div>
            <?php endif; ?>

            <div class="product-image">
              <img src="<?= !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : '/public/logo.png' ?>"
                alt="<?= htmlspecialchars($row['name']) ?>" />
            </div>
            <div class="description">
              <h2><?= htmlspecialchars($row['name']) ?></h2>
              <p><?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...</p>
            </div>
            <div class="product-actions">
              <span>Rp<?= number_format($row['price_per_day'], 0, ',', '.') ?></span>

              <?php if ($row['available_stock'] > 0): ?>
                <button class="cart-btn add-to-cart-btn" data-product-id="<?= $row['id'] ?>" title="Tambahkan ke Keranjang">
                  <i class="fa-solid fa-cart-plus"></i>
                </button>
              <?php else: ?>
                <button class="cart-btn" style="background:#ccc; cursor:not-allowed;" disabled title="Stok Habis">
                  <i class="fa-solid fa-ban"></i>
                </button>
              <?php endif; ?>
            </div>
          </div>
          <?php
        }
      }
      ?>

      <div style="grid-column: 1 / -1; width: 100%;">
        <div class="pagination-wrapper" id="paginationContainer">
          <?php if ($total_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="#" data-page="<?= $i ?>" class="pagination-link <?= $i == 1 ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="footer-inner">

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

      <div class="footer-navigation">
        <h4>Navigasi</h4>
        <ul>
          <li><a href="index.php">Beranda</a></li>
          <li><a href="tentang-kami.php">Tentang Kami</a></li>
          <li><a href="katalog.php">Katalog Lengkap</a></li>
          <li><a href="kontak.php">Hubungi Kami</a></li>
        </ul>
      </div>

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
  <script>
    // ===== STATE MANAGEMENT =====
    let currentFilters = { category: 'semua', search: '', sort: 'terbaru', page: 1 };
    let searchTimeout = null;

    // ===== HELPER FUNCTIONS =====
    function showToast(message, isError = false) {
      const toast = document.createElement('div');
      toast.className = 'toast-notification' + (isError ? ' error' : '');
      toast.innerHTML = `<i class="fas fa-${isError ? 'exclamation-circle' : 'check-circle'}"></i><span>${message}</span>`;

      document.body.appendChild(toast);
      void toast.offsetWidth;
      toast.classList.add('active');
      setTimeout(() => {
        toast.classList.remove('active');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
          }
        }, 300);

      }, 2000);
    }

    function updateCartCount(count) {
      const cartCountElement = document.getElementById('cartCount');
      const cartLink = document.getElementById('cartLink');
      if (cartCountElement) cartCountElement.textContent = count;
      if (cartLink) {
        cartLink.classList.add('updated');
        setTimeout(() => { cartLink.classList.remove('updated'); }, 500);
      }
    }

    function showLoading() {
      const productGrid = document.getElementById('productGrid');
      const loadingOverlay = document.createElement('div');
      loadingOverlay.className = 'loading-overlay';
      loadingOverlay.id = 'loadingOverlay';
      loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
      productGrid.appendChild(loadingOverlay);
    }

    function hideLoading() {
      const loadingOverlay = document.getElementById('loadingOverlay');
      if (loadingOverlay) loadingOverlay.remove();
    }

    function tebakKategori(nama) {
      nama = nama.toLowerCase();
      if (nama.includes('tenda') || nama.includes('flysheet')) return 'tenda';
      if (nama.includes('lampu') || nama.includes('senter') || nama.includes('headlamp')) return 'lampu';
      if (nama.includes('kompor') || nama.includes('nesting') || nama.includes('gas')) return 'alat-masak';
      if (nama.includes('paket')) return 'paket';
      return 'lainnya';
    }

    // ===== LOAD PRODUCTS FUNCTION =====
    function loadProducts() {
      showLoading();
      const params = new URLSearchParams({
        ajax_load_products: '1',
        category: currentFilters.category,
        q: currentFilters.search,
        sort: currentFilters.sort,
        page: currentFilters.page
      });

      fetch(`katalog.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            renderProducts(data.products);
            renderPagination(data.pagination);
          } else {
            showToast('Gagal memuat produk', true);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Terjadi kesalahan saat memuat produk', true);
        })
        .finally(() => { hideLoading(); });
    }

    // ===== RENDER FUNCTIONS =====
    function renderProducts(products) {
      const productGrid = document.getElementById('productGrid');
      const paginationContainer = document.getElementById('paginationContainer');
      const productCards = productGrid.querySelectorAll('.product-card');
      productCards.forEach(card => card.remove());

      const existingNoProduct = productGrid.querySelector('.no-product-message');
      if (existingNoProduct) existingNoProduct.remove();

      if (products.length === 0) {
        const noProduct = document.createElement('p');
        noProduct.className = 'no-product-message';
        noProduct.style.cssText = 'text-align:center; width:100%; grid-column: 1 / -1; padding: 50px;';
        noProduct.textContent = 'Produk tidak ditemukan.';
        productGrid.insertBefore(noProduct, paginationContainer.parentElement);
        return;
      }

      products.forEach(product => {
        const category = tebakKategori(product.name);
        const imageUrl = product.image_url || '/public/logo.png';
        const description = product.description.substring(0, 80) + '...';
        const price = new Intl.NumberFormat('id-ID').format(product.price_per_day);

        let stockBadge = '';
        let buttonHtml = '';
        let cardClass = 'product-card';

        if (product.available_stock > 0) {
          stockBadge = `<div style="position:absolute; top:10px; left:10px; background:#e8f5e9; color:#2e7d32; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">
                            Stok: ${product.available_stock}
                          </div>`;
          buttonHtml = `<button class="cart-btn add-to-cart-btn" data-product-id="${product.id}" title="Tambahkan ke Keranjang">
                            <i class="fa-solid fa-cart-plus"></i>
                          </button>`;
        } else {
          stockBadge = `<div style="position:absolute; top:10px; left:10px; background:#ffebee; color:#c62828; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">
                            Habis / Dipinjam
                          </div>`;
          buttonHtml = `<button class="cart-btn" style="background:#ccc; cursor:not-allowed;" disabled>
                            <i class="fa-solid fa-ban"></i>
                          </button>`;
          cardClass += ' out-of-stock';
        }

        const productCard = document.createElement('div');
        productCard.className = cardClass;
        productCard.setAttribute('data-category', category);
        productCard.setAttribute('data-name', product.name.toLowerCase());

        productCard.innerHTML = `
          ${stockBadge}
          <div class="product-image">
            <img src="${imageUrl}" alt="${product.name}" />
          </div>
          <div class="description">
            <h2>${product.name}</h2>
            <p>${description}</p>
          </div>
          <div class="product-actions">
            <span>Rp ${price}</span>
            ${buttonHtml}
          </div>
        `;

        productGrid.insertBefore(productCard, paginationContainer.parentElement);
      });

      attachCartButtonListeners();
    }

    function renderPagination(pagination) {
      const paginationContainer = document.getElementById('paginationContainer');
      paginationContainer.innerHTML = '';

      if (pagination.total_pages <= 1) return;

      if (pagination.current_page > 1) {
        const prevLink = document.createElement('a');
        prevLink.href = '#';
        prevLink.className = 'pagination-link';
        prevLink.setAttribute('data-page', pagination.current_page - 1);
        prevLink.innerHTML = '&laquo; Prev';
        paginationContainer.appendChild(prevLink);
      }

      for (let i = 1; i <= pagination.total_pages; i++) {
        const pageLink = document.createElement('a');
        pageLink.href = '#';
        pageLink.className = 'pagination-link' + (i === pagination.current_page ? ' active' : '');
        pageLink.setAttribute('data-page', i);
        pageLink.textContent = i;
        paginationContainer.appendChild(pageLink);
      }

      if (pagination.current_page < pagination.total_pages) {
        const nextLink = document.createElement('a');
        nextLink.href = '#';
        nextLink.className = 'pagination-link';
        nextLink.setAttribute('data-page', pagination.current_page + 1);
        nextLink.innerHTML = 'Next &raquo;';
        paginationContainer.appendChild(nextLink);
      }
      attachPaginationListeners();
    }

    // ===== EVENT LISTENERS =====
    function attachCartButtonListeners() {
      document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          const productId = this.getAttribute('data-product-id');
          const buttonIcon = this.querySelector('i');
          this.disabled = true;
          buttonIcon.className = 'fas fa-spinner fa-spin';

          const formData = new FormData();
          formData.append('ajax_add_to_cart', '1');
          formData.append('product_id', productId);

          fetch('katalog.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showToast(data.message);
                updateCartCount(data.cartCount);
              } else {
                showToast(data.message, true);
              }
            })
            // .catch(error => {
            //   console.error('Error:', error);
            //   showToast('Terjadi kesalahan, silakan coba lagi', true);
            // })
            .finally(() => {
              this.disabled = false;
              buttonIcon.className = 'fa-solid fa-cart-plus';
            });
        });
      });
    }

    function attachPaginationListeners() {
      document.querySelectorAll('.pagination-link').forEach(link => {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          currentFilters.page = parseInt(this.getAttribute('data-page'));
          loadProducts();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });
    }

    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
      button.addEventListener('click', function () {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        currentFilters.category = this.getAttribute('data-val');
        currentFilters.page = 1;
        loadProducts();
      });
    });

    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentFilters.search = this.value;
        currentFilters.page = 1;
        loadProducts();
      }, 500);
    });

    const sortSelect = document.getElementById('sortSelect');
    sortSelect.addEventListener('change', function () {
      currentFilters.sort = this.value;
      currentFilters.page = 1;
      loadProducts();
    });

    const mobileFilterBtn = document.getElementById('mobileFilterBtn');
    const filterContainer = document.getElementById('filterContainer');
    if (mobileFilterBtn) {
      mobileFilterBtn.addEventListener('click', () => {
        filterContainer.style.display = (filterContainer.style.display === 'block') ? 'none' : 'block';
      });
    }

    attachCartButtonListeners();
    attachPaginationListeners();
  </script>
</body>

</html>