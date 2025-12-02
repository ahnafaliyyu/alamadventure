<?php
// katalog.php
require 'config/init.php';

// --- 1. LOGIKA KERANJANG AJAX ---
if (isset($_POST['ajax_add_to_cart'])) {
  header('Content-Type: application/json');

  $product_id = $_POST['product_id'];
  $qty = 1;

  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $prod = $stmt->get_result()->fetch_assoc();

  if ($prod) {
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

  $sql_order = "ORDER BY id DESC";
  if ($sort == 'termurah')
    $sql_order = "ORDER BY price_per_day ASC";
  if ($sort == 'termahal')
    $sql_order = "ORDER BY price_per_day DESC";

  $sql_count = "SELECT COUNT(*) as total FROM products WHERE $sql_where";
  $stmt_count = $conn->prepare($sql_count);
  if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
  }
  $stmt_count->execute();
  $total_data = $stmt_count->get_result()->fetch_assoc()['total'];
  $total_pages = ceil($total_data / $limit);

  $sql_data = "SELECT * FROM products WHERE $sql_where $sql_order LIMIT ? OFFSET ?";
  $params[] = $limit;
  $params[] = $offset;
  $types .= "ii";

  $stmt = $conn->prepare($sql_data);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  $products = [];
  while ($row = $result->fetch_assoc()) {
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

$sql_data = "SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?";
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
    /* Styling untuk notifikasi toast */
    .toast-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #4CAF50;
      color: white;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.3s ease-out;
      max-width: 300px;
    }

    .toast-notification.error {
      background: #f44336;
    }

    .toast-notification i {
      font-size: 20px;
    }

    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }

      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }

      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .toast-notification.hiding {
      animation: slideOut 0.3s ease-in;
    }

    /* Badge counter animasi */
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

    /* Loading overlay */
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

    .product-grid {
      position: relative;
      min-height: 400px;
    }

    /* Animasi fade in untuk produk */
    .product-card {
      animation: fadeIn 0.3s ease-in;
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
      <a href="keranjang.php" class="nav-link cart-badge" id="cartLink">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
      </a>
      <a href="/admin/login.php">Login</a>
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
          <button class="filter-btn active" data-val="semua">
            <span>Semua</span>
          </button>
          <button class="filter-btn" data-val="tenda">
            <span>Tenda</span>
          </button>
          <button class="filter-btn" data-val="lampu">
            <span>Lampu</span>
          </button>
          <button class="filter-btn" data-val="alat-masak">
            <span>Alat Masak</span>
          </button>
          <button class="filter-btn" data-val="paket">
            <span>Paket</span>
          </button>
          <button class="filter-btn" data-val="lainnya">
            <span>Lainnya</span>
          </button>
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
          ?>
          <div class="product-card" data-category="<?= $category ?>"
            data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>">
            <div class="product-image">
              <img src="<?= !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : '/public/logo.png' ?>"
                alt="<?= htmlspecialchars($row['name']) ?>" />
            </div>
            <div class="description">
              <h2><?= htmlspecialchars($row['name']) ?></h2>
              <p><?= htmlspecialchars(substr($row['description'], 0, 80)) ?>...</p>
            </div>
            <div class="product-actions">
              <span>Rp <?= number_format($row['price_per_day'], 0, ',', '.') ?></span>
              <button class="cart-btn add-to-cart-btn" data-product-id="<?= $row['id'] ?>" title="Tambahkan ke Keranjang">
                <i class="fa-solid fa-cart-plus"></i>
              </button>
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

  <!-- Footer -->
  <footer class="site-footer">
    <div class="footer-inner">
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
    let currentFilters = {
      category: 'semua',
      search: '',
      sort: 'terbaru',
      page: 1
    };

    let searchTimeout = null;

    // ===== HELPER FUNCTIONS =====
    function showToast(message, isError = false) {
      const toast = document.createElement('div');
      toast.className = 'toast-notification' + (isError ? ' error' : '');
      toast.innerHTML = `
        <i class="fas fa-${isError ? 'exclamation-circle' : 'check-circle'}"></i>
        <span>${message}</span>
      `;

      document.body.appendChild(toast);

      setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => {
          document.body.removeChild(toast);
        }, 300);
      }, 3000);
    }

    function updateCartCount(count) {
      const cartCountElement = document.getElementById('cartCount');
      const cartLink = document.getElementById('cartLink');

      cartCountElement.textContent = count;
      cartLink.classList.add('updated');
      setTimeout(() => {
        cartLink.classList.remove('updated');
      }, 500);
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
      if (loadingOverlay) {
        loadingOverlay.remove();
      }
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
        .finally(() => {
          hideLoading();
        });
    }

    // ===== RENDER FUNCTIONS =====
    function renderProducts(products) {
      const productGrid = document.getElementById('productGrid');
      const paginationContainer = document.getElementById('paginationContainer');

      // Hapus semua produk kecuali pagination
      const productCards = productGrid.querySelectorAll('.product-card');
      productCards.forEach(card => card.remove());

      // Hapus pesan "tidak ditemukan" jika ada
      const existingNoProduct = productGrid.querySelector('.no-product-message');
      if (existingNoProduct) {
        existingNoProduct.remove();
      }

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

        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.setAttribute('data-category', category);
        productCard.setAttribute('data-name', product.name.toLowerCase());

        productCard.innerHTML = `
          <div class="product-image">
            <img src="${imageUrl}" alt="${product.name}" />
          </div>
          <div class="description">
            <h2>${product.name}</h2>
            <p>${description}</p>
          </div>
          <div class="product-actions">
            <span>Rp ${price}</span>
            <button class="cart-btn add-to-cart-btn" data-product-id="${product.id}" title="Tambahkan ke Keranjang">
              <i class="fa-solid fa-cart-plus"></i>
            </button>
          </div>
        `;

        productGrid.insertBefore(productCard, paginationContainer.parentElement);
      });

      // Re-attach event listeners untuk tombol add to cart
      attachCartButtonListeners();
    }

    function renderPagination(pagination) {
      const paginationContainer = document.getElementById('paginationContainer');
      paginationContainer.innerHTML = '';

      if (pagination.total_pages <= 1) return;

      // Previous button
      if (pagination.current_page > 1) {
        const prevLink = document.createElement('a');
        prevLink.href = '#';
        prevLink.className = 'pagination-link';
        prevLink.setAttribute('data-page', pagination.current_page - 1);
        prevLink.innerHTML = '&laquo; Prev';
        paginationContainer.appendChild(prevLink);
      }

      // Page numbers
      for (let i = 1; i <= pagination.total_pages; i++) {
        const pageLink = document.createElement('a');
        pageLink.href = '#';
        pageLink.className = 'pagination-link' + (i === pagination.current_page ? ' active' : '');
        pageLink.setAttribute('data-page', i);
        pageLink.textContent = i;
        paginationContainer.appendChild(pageLink);
      }

      // Next button
      if (pagination.current_page < pagination.total_pages) {
        const nextLink = document.createElement('a');
        nextLink.href = '#';
        nextLink.className = 'pagination-link';
        nextLink.setAttribute('data-page', pagination.current_page + 1);
        nextLink.innerHTML = 'Next &raquo;';
        paginationContainer.appendChild(nextLink);
      }

      // Attach event listeners
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

          fetch('katalog.php', {
            method: 'POST',
            body: formData
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showToast(data.message);
                updateCartCount(data.cartCount);
              } else {
                showToast(data.message, true);
              }
            })
            .catch(error => {
              console.error('Error:', error);
              showToast('Terjadi kesalahan, silakan coba lagi', true);
            })
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
          const page = parseInt(this.getAttribute('data-page'));
          currentFilters.page = page;
          loadProducts();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });
    }

    // Category filter buttons
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

    // Search input with debounce
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentFilters.search = this.value;
        currentFilters.page = 1;
        loadProducts();
      }, 500); // 500ms debounce
    });

    // Sort select
    const sortSelect = document.getElementById('sortSelect');
    sortSelect.addEventListener('change', function () {
      currentFilters.sort = this.value;
      currentFilters.page = 1;
      loadProducts();
    });

    // Mobile filter toggle
    const mobileFilterBtn = document.getElementById('mobileFilterBtn');
    const filterContainer = document.getElementById('filterContainer');
    if (mobileFilterBtn) {
      mobileFilterBtn.addEventListener('click', () => {
        filterContainer.style.display = (filterContainer.style.display === 'block') ? 'none' : 'block';
      });
    }

    // Initial setup
    attachCartButtonListeners();
    attachPaginationListeners();
  </script>
</body>

</html>