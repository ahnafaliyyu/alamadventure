<?php
// katalog.php
require 'config/init.php';

// --- 1. LOGIKA KERANJANG ---
if (isset($_POST['add_to_cart'])) {
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
    echo "<script>alert('Berhasil ditambahkan ke keranjang!'); window.location.href='katalog.php';</script>";
    exit;
  }
}

// --- 2. HELPER KATEGORI ---
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

// --- 3. SERVER-SIDE LOGIC ---
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
      <a href="keranjang.php" class="nav-link"><i
          class="fas fa-shopping-cart"></i><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></a>
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
          <select id="sortSelect" onchange="applyParams('sort', this.value)">
            <option value="terbaru" <?= $sort == 'terbaru' ? 'selected' : '' ?>>Semua</option>
            <option value="termurah" <?= $sort == 'termurah' ? 'selected' : '' ?>>Termurah</option>
            <option value="termahal" <?= $sort == 'termahal' ? 'selected' : '' ?>>Termahal</option>
          </select>
        </div>
      </div>
    </div>

    <div class="filter-container-wrapper" id="filterContainer">
      <div class="category-filter">

        <div class="btn-filter">
          <button class="filter-btn <?= $kategori_aktif == 'semua' ? 'active' : '' ?>" data-val="semua">
            <span>Semua</span>
          </button>
          <button class="filter-btn <?= $kategori_aktif == 'tenda' ? 'active' : '' ?>" data-val="tenda">
            <span>Tenda</span>
          </button>
          <button class="filter-btn <?= $kategori_aktif == 'lampu' ? 'active' : '' ?>" data-val="lampu">
            <span>Lampu</span>
          </button>
          <button class="filter-btn <?= $kategori_aktif == 'alat-masak' ? 'active' : '' ?>" data-val="alat-masak">
            <span>Alat Masak</span>
          </button>
          <button class="filter-btn <?= $kategori_aktif == 'paket' ? 'active' : '' ?>" data-val="paket">
            <span>Paket</span>
          </button>
          <button class="filter-btn <?= $kategori_aktif == 'lainnya' ? 'active' : '' ?>" data-val="lainnya">
            <span>Lainnya</span>
          </button>
        </div>

        <div class="search-container">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Cari produk..." class="search-input"
            value="<?= htmlspecialchars($search) ?>"
            onkeypress="if(event.key === 'Enter') applyParams('q', this.value)" />
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
              <form method="POST" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <button type="submit" name="add_to_cart" class="cart-btn" title="Tambahkan ke Keranjang">
                  <i class="fa-solid fa-cart-plus"></i>
                </button>
              </form>
            </div>
          </div>
        <?php
        }
      } else {
        echo "<p style='text-align:center; width:100%; grid-column: 1 / -1; padding: 50px;'>Produk tidak ditemukan.</p>";
      }
      ?>

      <?php if ($total_pages > 1): ?>
        <div style="grid-column: 1 / -1; width: 100%;">
          <div class="pagination-wrapper">
            <?php $baseUrl = "?category=$kategori_aktif&q=$search&sort=$sort&page="; ?>
            <?php if ($page > 1): ?>
              <a href="<?= $baseUrl . ($page - 1) ?>" class="pagination-link">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="<?= $baseUrl . $i ?>" class="pagination-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <a href="<?= $baseUrl . ($page + 1) ?>" class="pagination-link">Next &raquo;</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
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

  <script>
    // 1. Script Pindah Active Class
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
      button.addEventListener('click', function () {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active'); // Styling langsung berubah

        const categoryValue = this.getAttribute('data-val');
        applyParams('category', categoryValue);
      });
    });

    function applyParams(key, value) {
      const url = new URL(window.location.href);
      url.searchParams.set(key, value);
      if (key === 'category' || key === 'q') {
        url.searchParams.set('page', 1);
      }
      window.location.href = url.toString();
    }

    const mobileFilterBtn = document.getElementById('mobileFilterBtn');
    const filterContainer = document.getElementById('filterContainer');
    if (mobileFilterBtn) {
      mobileFilterBtn.addEventListener('click', () => {
        filterContainer.style.display = (filterContainer.style.display === 'block') ? 'none' : 'block';
      });
    }
  </script>
</body>

</html>