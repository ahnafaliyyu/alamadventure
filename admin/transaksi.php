<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - Alam Adventure</title>
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
                <li><a href="produk.php"><i class="fa-solid fa-box-open"></i> <span>Produk</span></a></li>
                <li><a href="transaksi.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Transaksi</span></a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> <span>Pengaturan</span></a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Keluar</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Data Transaksi</h1>
                </div>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Cari ID Order / Pelanggan...">
                </div>
            </div>

            <div class="content-section">
                <div class="table-responsive">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>ID Order / Faktur</th>
                                <th>Pelanggan</th>
                                <th>Total Biaya</th>
                                <th>Durasi</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="trx-table-body">
                            <tr><td colspan="7" align="center">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" class="pagination-container"></div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar logic
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if(toggleBtn) { toggleBtn.addEventListener('click', toggleSidebar); overlay.addEventListener('click', toggleSidebar); }

        // --- AJAX LOGIC ---
        let currentPage = 1;
        let searchKeyword = '';
        let debounceTimer;

        document.addEventListener('DOMContentLoaded', () => fetchTransactions());

        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            searchKeyword = e.target.value;
            debounceTimer = setTimeout(() => {
                currentPage = 1; 
                fetchTransactions();
            }, 300);
        });

        function fetchTransactions() {
            const tbody = document.getElementById('trx-table-body');
            const paginationDiv = document.getElementById('paginationContainer');
            
            tbody.innerHTML = '<tr><td colspan="7" align="center"><i class="fa-solid fa-spinner fa-spin"></i> Memuat...</td></tr>';

            fetch(`../api/get_transactions.php?page=${currentPage}&q=${searchKeyword}`)
                .then(res => res.json())
                .then(result => {
                    tbody.innerHTML = '';
                    paginationDiv.innerHTML = '';

                    if (result.success && result.data.length > 0) {
                        result.data.forEach(trx => {
                            const total = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits:0 }).format(trx.total_amount);
                            const date = new Date(trx.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'});
                            
                            // Status Badge
                            let statusBadge = '';
                            if(trx.status === 'paid') statusBadge = '<span style="background:#e8f5e9; color:green; padding:4px 8px; border-radius:4px; font-weight:600;"><i class="fa-solid fa-check"></i> Lunas</span>';
                            else if(trx.status === 'pending') statusBadge = '<span style="background:#fff8e1; color:#f57f17; padding:4px 8px; border-radius:4px; font-weight:600;"><i class="fa-solid fa-hourglass"></i> Pending</span>';
                            else statusBadge = '<span style="background:#ffebee; color:red; padding:4px 8px; border-radius:4px; font-weight:600;">Batal</span>';

                            // Invoice
                            let invoiceHtml = trx.invoice_no ? `<br><small style="background:#eee; padding:2px 4px; border-radius:3px;">${trx.invoice_no}</small>` : '';
                            
                            // Action Button
                            let actionBtn = trx.status === 'paid' 
                                ? `<a href="../invoice.php?order=${trx.order_code}" target="_blank" class="btn btn-primary" style="padding:5px 10px; font-size:12px;"><i class="fa-solid fa-print"></i></a>`
                                : `<button class="btn" style="background:#eee; color:#aaa; cursor:not-allowed; padding:5px 10px;"><i class="fa-solid fa-lock"></i></button>`;

                            tbody.innerHTML += `
                                <tr>
                                    <td><strong>${trx.order_code}</strong>${invoiceHtml}</td>
                                    <td>${trx.customer_name}<br><small style="color:#888">${trx.customer_phone}</small></td>
                                    <td><strong>${total}</strong></td>
                                    <td>${trx.duration_days} Hari</td>
                                    <td>${date}</td>
                                    <td>${statusBadge}</td>
                                    <td align="center">${actionBtn}</td>
                                </tr>
                            `;
                        });

                        // Render Pagination
                        renderPagination(result.pagination, paginationDiv);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" align="center">Tidak ada data ditemukan.</td></tr>';
                    }
                })
                .catch(err => console.error(err));
        }

        // Fungsi Pagination Reusable
        function renderPagination(meta, container) {
            if (meta.total_pages <= 1) return;
            let html = `<button class="page-btn" ${meta.current_page === 1 ? 'disabled' : ''} onclick="changePage(${meta.current_page - 1})">&laquo;</button>`;
            for (let i = 1; i <= meta.total_pages; i++) {
                html += `<button class="page-btn ${i === meta.current_page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }
            html += `<button class="page-btn" ${meta.current_page === meta.total_pages ? 'disabled' : ''} onclick="changePage(${meta.current_page + 1})">&raquo;</button>`;
            container.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            fetchTransactions();
        }
    </script>
</body>
</html>