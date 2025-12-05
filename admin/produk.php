<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 5px;
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 6px;
        }

        .page-btn.active {
            background: #2c4532;
            color: white;
            border-color: #2c4532;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Style untuk Badge Stok */
        .stock-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            min-width: 60px;
        }

        .stock-safe {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        /* Hijau */
        .stock-full {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Merah */
        .stock-label {
            font-size: 10px;
            color: #666;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>ALAM<span style="color:#fff">ADVENTURE</span></h2>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="produk.php" class="active">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Produk</span>
                    </a>
                </li>
                <li>
                    <a href="transaksi.php">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="pengaturan.php">
                        <i class="fa-solid fa-gear"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>
                <li class="logout">
                    <a href="logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Keluar</span>
                    </a>
                </li>
                <li class="beranda">
                    <a href="../index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Beranda</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Daftar Produk</h1>
                </div>
            </div>

            <div style="margin-bottom: 20px; text-align: left; display: flex; justify-content: space-between">
                <a href="tambah_produk.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama produk...">
                </div>
            </div>

            <div class="content-section">
                <div class="table-responsive">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th width="10%">ID</th>
                                <th width="30%">Nama Produk</th>
                                <th width="20%">Harga/hari</th>
                                <th width="20%">Stok (Disewa/Total)</th>
                                <th width="20%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="product-table-body">
                            <tr>
                                <td colspan="5" align="center">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" class="pagination-container"></div>
            </div>
        </main>
    </div>

    <div class="generic-overlay" id="genericModal">
        <div class="generic-box">
            <div class="generic-icon" id="genericIcon"></div>
            <h3 class="generic-title" id="genericTitle"></h3>
            <p class="generic-text" id="genericText"></p>
            <div class="generic-buttons" id="genericBtns">
                <button class="btn-generic btn-primary-modal" onclick="closeGenericModal()">OK</button>
            </div>
        </div>
    </div>

    <script>
        // =========================================
        // 1. SIDEBAR & UI LOGIC
        // =========================================
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }

        // =========================================
        // 2. CUSTOM MODAL SYSTEM (POP-UP)
        // =========================================
        const modal = document.getElementById('genericModal');
        const mIcon = document.getElementById('genericIcon');
        const mTitle = document.getElementById('genericTitle');
        const mText = document.getElementById('genericText');
        const mBtns = document.getElementById('genericBtns');

        function closeGenericModal() {
            modal.style.display = 'none';
        }

        // Fungsi untuk Alert Sederhana (Sukses/Gagal)
        function showAlert(title, msg, type = 'success') {
            // Icon
            if (type === 'success') {
                mIcon.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
                mIcon.className = 'generic-icon success'; // Pastikan ada class css .success { color: #2e7d32; }
            } else {
                mIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>';
                mIcon.className = 'generic-icon danger'; // Pastikan ada class css .danger { color: #ef4444; }
            }

            mTitle.innerText = title;
            mText.innerText = msg;

            // Tombol Tutup
            mBtns.innerHTML = `<button class="btn-generic btn-primary-modal" onclick="closeGenericModal()">Tutup</button>`;

            modal.style.display = 'flex';
        }

        // Fungsi untuk Konfirmasi (Ya/Batal)
        function showConfirm(title, msg, onYes) {
            mIcon.innerHTML = '<i class="fa-solid fa-circle-question"></i>';
            mIcon.className = 'generic-icon'; // Warna default (kuning)

            mTitle.innerText = title;
            mText.innerText = msg;

            // Tombol Batal & Ya
            mBtns.innerHTML = `
                <button class="btn-generic btn-secondary-modal" onclick="closeGenericModal()">Batal</button>
                <button class="btn-generic btn-primary-modal" id="btnYes">Ya, Hapus</button>
            `;

            modal.style.display = 'flex';

            // Bind action ke tombol Ya
            document.getElementById('btnYes').onclick = function () {
                closeGenericModal();
                onYes(); // Jalankan fungsi callback
            };
        }

        // =========================================
        // 3. AJAX DATA LOADING (LIVE)
        // =========================================
        let currentPage = 1;
        let searchKeyword = '';
        let debounceTimer;

        // Load data saat halaman pertama kali dibuka
        document.addEventListener('DOMContentLoaded', () => fetchProducts());

        // Event Listener untuk Pencarian (Live Search dengan Debounce)
        document.getElementById('searchInput').addEventListener('input', function (e) {
            clearTimeout(debounceTimer);
            searchKeyword = e.target.value;
            // Tunggu 300ms sebelum request agar tidak spam server
            debounceTimer = setTimeout(() => {
                currentPage = 1; // Reset ke halaman 1 saat mencari
                fetchProducts();
            }, 300);
        });

        // Fungsi Utama Fetch Data
        function fetchProducts() {
            const tbody = document.getElementById('product-table-body');
            const paginationDiv = document.getElementById('paginationContainer');

            // Tampilkan loading state
            tbody.style.opacity = '0.5';

            fetch(`../api/get_products.php?page=${currentPage}&q=${searchKeyword}`)
                .then(res => res.json())
                .then(result => {
                    tbody.innerHTML = '';
                    tbody.style.opacity = '1';
                    paginationDiv.innerHTML = '';

                    if (result.success && result.data.length > 0) {
                        // Loop data produk
                        result.data.forEach(p => {
                            const price = new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(p.price_per_day);

                            const rented = p.rented || 0;
                            const total = p.stock || 0;

                            // Logika Badge Stok
                            const badgeClass = (rented >= total && total > 0) ? 'stock-full' : 'stock-safe';

                            const stockDisplay = `
                                <div style="display:flex; flex-direction:column; align-items:center;">
                                    <span class="stock-badge ${badgeClass}">${rented} / ${total}</span>
                                    <small class="stock-label">Disewa / Total</small>
                                </div>
                            `;

                            // Render Baris Tabel
                            tbody.innerHTML += `
                                <tr>
                                    <td>#${p.id}</td>
                                    <td><strong>${p.name}</strong></td>
                                    <td>${price}</td>
                                    <td align="center">${stockDisplay}</td>
                                    <td>
                                        <a href="edit_produk.php?id=${p.id}" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <button class="btn btn-delete" onclick="handleDelete(${p.id})" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            `;
                        });

                        // Render Pagination
                        renderPagination(result.pagination, paginationDiv);

                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" align="center">Tidak ada data ditemukan.</td></tr>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    tbody.style.opacity = '1';
                    showAlert('Error', 'Gagal memuat data produk.', 'danger');
                });
        }

        // =========================================
        // 4. PAGINATION LOGIC
        // =========================================
        function renderPagination(meta, container) {
            const totalPages = meta.total_pages;
            const current = meta.current_page;

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';

            // Tombol Previous
            if (current > 1) {
                html += `<button class="page-btn" onclick="changePage(${current - 1})">&laquo;</button>`;
            } else {
                html += `<button class="page-btn" disabled>&laquo;</button>`;
            }

            // Smart Pagination Logic (Membatasi jumlah tombol angka)
            let startPage = Math.max(1, current - 2);
            let endPage = Math.min(totalPages, current + 2);

            if (startPage > 1) {
                html += `<button class="page-btn" onclick="changePage(1)">1</button>`;
                if (startPage > 2) html += `<span style="padding:4px 8px;">...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === current) ? 'active' : '';
                html += `<button class="page-btn ${activeClass}" onclick="changePage(${i})">${i}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += `<span style="padding:4px 8px;">...</span>`;
                html += `<button class="page-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
            }

            // Tombol Next
            if (current < totalPages) {
                html += `<button class="page-btn" onclick="changePage(${current + 1})">&raquo;</button>`;
            } else {
                html += `<button class="page-btn" disabled>&raquo;</button>`;
            }

            container.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            fetchProducts();
        }

        // =========================================
        // 5. DELETE ACTION (AJAX)
        // =========================================
        function handleDelete(id) {
            showConfirm(
                'Hapus Produk?',
                'Apakah Anda yakin ingin menghapus produk ini? Data yang dihapus tidak bisa dikembalikan.',
                function () {
                    // Eksekusi Hapus jika User klik "Ya"
                    fetch('../api/delete_product.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                        .then(res => res.json())
                        .then(r => {
                            if (r.success) {
                                showAlert('Berhasil', r.message, 'success');
                                fetchProducts(); // Refresh tabel otomatis
                            } else {
                                showAlert('Gagal', r.message, 'danger');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showAlert('Error', 'Terjadi kesalahan saat menghapus.', 'danger');
                        });
                }
            );
        }
    </script>
</body>

</html>