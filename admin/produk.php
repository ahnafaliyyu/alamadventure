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
        .pagination-container { margin-top: 20px; display: flex; justify-content: flex-end; gap: 5px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 6px; }
        .page-btn.active { background: #2c4532; color: white; border-color: #2c4532; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header"><h2>ALAM<span style="color:#fff">ADVENTURE</span></h2></div>
            <ul class="sidebar-nav">
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
                <li><a href="produk.php" class="active"><i class="fa-solid fa-box-open"></i> <span>Produk</span></a></li>
                <li><a href="transaksi.php"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Transaksi</span></a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Pengaturan</span></a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Keluar</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Daftar Produk</h1>
                </div>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama produk...">
                </div>
            </div>

            <div style="margin-bottom: 20px; text-align: right;">
                <a href="tambah_produk.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
            </div>

            <div class="content-section">
                <div class="table-responsive">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th width="10%">ID</th>
                                <th width="30%">Nama Produk</th>
                                <th width="20%">Harga/hari</th>
                                <th width="15%">Stok</th>
                                <th width="25%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="product-table-body">
                            <tr><td colspan="5" align="center">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" class="pagination-container"></div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if(toggleBtn) { toggleBtn.addEventListener('click', toggleSidebar); overlay.addEventListener('click', toggleSidebar); }

        // --- AJAX LOGIC ---
        let currentPage = 1;
        let searchKeyword = '';
        let debounceTimer;

        document.addEventListener('DOMContentLoaded', () => fetchProducts());

        // Event Listener Search (Debounce agar tidak spam server)
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            searchKeyword = e.target.value;
            debounceTimer = setTimeout(() => {
                currentPage = 1; // Reset ke halaman 1 saat search
                fetchProducts();
            }, 300);
        });

        function fetchProducts() {
            const tbody = document.getElementById('product-table-body');
            const paginationDiv = document.getElementById('paginationContainer');
            
            // Loading State
            tbody.innerHTML = '<tr><td colspan="5" align="center"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data...</td></tr>';

            fetch(`../api/get_products.php?page=${currentPage}&q=${searchKeyword}`)
                .then(res => res.json())
                .then(result => {
                    tbody.innerHTML = '';
                    paginationDiv.innerHTML = '';

                    if (result.success && result.data.length > 0) {
                        // Render Table
                        result.data.forEach(p => {
                            const price = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits:0 }).format(p.price_per_day);
                            const badge = p.stock > 0 ? `<span style="background:#e8f5e9; color:green; padding:4px 8px; border-radius:4px; font-size:12px;">${p.stock} Unit</span>` : `<span style="background:#ffebee; color:red; padding:4px 8px; border-radius:4px; font-size:12px;">Habis</span>`;
                            
                            tbody.innerHTML += `
                                <tr>
                                    <td>#${p.id}</td>
                                    <td><strong>${p.name}</strong></td>
                                    <td>${price}</td>
                                    <td>${badge}</td>
                                    <td>
                                        <a href="../api/edit_produk.html?id=${p.id}" class="btn btn-edit"><i class="fa-solid fa-pen"></i></a>
                                        <button class="btn btn-delete" onclick="deleteProduct(${p.id})"><i class="fa-solid fa-trash"></i></button>
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
                .catch(err => console.error(err));
        }

        function renderPagination(meta, container) {
            if (meta.total_pages <= 1) return;

            let html = '';
            // Prev Button
            html += `<button class="page-btn" ${meta.current_page === 1 ? 'disabled' : ''} onclick="changePage(${meta.current_page - 1})">&laquo;</button>`;
            
            // Numbers
            for (let i = 1; i <= meta.total_pages; i++) {
                html += `<button class="page-btn ${i === meta.current_page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }

            // Next Button
            html += `<button class="page-btn" ${meta.current_page === meta.total_pages ? 'disabled' : ''} onclick="changePage(${meta.current_page + 1})">&raquo;</button>`;
            
            container.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            fetchProducts();
        }

        function deleteProduct(id) {
            if(confirm('Hapus produk ini?')) {
                fetch('../api/delete_product.php', { method: 'POST', body: JSON.stringify({id: id}) })
                .then(res => res.json())
                .then(r => { alert(r.message); fetchProducts(); });
            }
        }
    </script>
</body>
</html>