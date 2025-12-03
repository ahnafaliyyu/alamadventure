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
        /* --- STYLE TAMBAHAN UNTUK HALAMAN INI --- */

        .content-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Tabel Responsif */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Smooth scroll di iOS */
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
            /* Agar kolom tidak menyempit aneh di HP */
        }

        .content-table th,
        .content-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .content-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c4532;
        }

        /* Pagination */
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
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: #f0f0f0;
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

        /* Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
            display: inline-block;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-warning {
            background: #fff8e1;
            color: #f57f17;
        }

        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }

        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-cod {
            background: #34495e;
            color: #fff;
            border: 1px solid #2c3e50;
        }

        .badge-secondary {
            background: #f5f5f5;
            color: #616161;
        }

        /* Media Queries untuk Responsivitas Mobile */
        @media (max-width: 768px) {
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-box {
                width: 100%;
                max-width: none;
            }

            .search-box input {
                width: 100%;
            }

            /* Sidebar Toggle Button (Pastikan terlihat di mobile) */
            .btn-toggle-sidebar {
                display: block;
                font-size: 24px;
                background: none;
                border: none;
                color: #2c4532;
                cursor: pointer;
            }
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
                    <a href="produk.php">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Produk</span>
                    </a>
                </li>
                <li>
                    <a href="transaksi.php" class="active">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="#">
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
                <div style="display:flex; align-items:center; gap:15px; width:100%;">
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
                                <th>ID Order</th>
                                <th>Pelanggan</th>
                                <th>Total Biaya</th>
                                <th>Metode</th>
                                <th>Status Bayar</th>
                                <th>Status Sewa</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="trx-table-body">
                            <tr>
                                <td colspan="7" align="center"><i class="fa-solid fa-spinner fa-spin"></i> Memuat
                                    data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" class="pagination-container"></div>
            </div>
        </main>
    </div>

    <script>
        // --- Sidebar Logic (Responsif) ---
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

        // --- AJAX DATA LOGIC ---
        let currentPage = 1;
        let searchKeyword = '';
        let debounceTimer;

        document.addEventListener('DOMContentLoaded', () => fetchTransactions());

        document.getElementById('searchInput').addEventListener('input', function (e) {
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
                            const total = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trx.total_amount);

                            // 1. Metode Pembayaran
                            let metodeHtml = '';
                            if (trx.payment_method === 'cod') {
                                metodeHtml = '<span class="badge badge-cod">COD</span>';
                            } else {
                                metodeHtml = '<span class="badge badge-info">Online</span>';
                            }

                            // 2. Status Bayar
                            let statusBayarHtml = '';
                            if (trx.status === 'paid') {
                                statusBayarHtml = '<span class="badge badge-success">Lunas</span>';
                            } else if (trx.status === 'pending') {
                                // Jika COD dan Pending = Belum Bayar (Bukan sekadar pending sistem)
                                if (trx.payment_method === 'cod') {
                                    statusBayarHtml = '<span class="badge badge-warning" style="color:#d35400;">Belum Bayar</span>';
                                } else {
                                    statusBayarHtml = '<span class="badge badge-warning">Pending</span>';
                                }
                            } else {
                                statusBayarHtml = '<span class="badge badge-danger">Batal</span>';
                            }

                            // 3. Status Sewa & Tombol Aksi
                            let statusSewa = '';
                            let actionBtn = '';

                            // Hitung tanggal kembali
                            const orderDate = new Date(trx.created_at);
                            const returnDate = new Date(orderDate);
                            returnDate.setDate(orderDate.getDate() + parseInt(trx.duration_days));
                            const today = new Date();

                            // ---- LOGIKA STATUS SEWA ----
                            if (trx.rental_status === 'pending_pickup') {
                                statusSewa = '<span class="badge badge-secondary">Menunggu Diambil</span>';

                                // Tombol Ambil
                                // Cek tipe bayar untuk pesan konfirmasi yang sesuai
                                const confirmType = (trx.payment_method === 'cod') ? 'cod' : 'online';
                                actionBtn = `<button class="btn btn-primary" style="padding:5px 10px; font-size:12px; margin-right:5px; white-space:nowrap;" onclick="startRent('${trx.id}', '${confirmType}')" title="Serahkan Barang"><i class="fa-solid fa-box-open"></i> Ambil</button>`;

                            } else if (trx.rental_status === 'ongoing') {
                                statusSewa = '<span class="badge badge-info">Sedang Disewa</span>';

                                // Cek Telat
                                const diffTime = today - returnDate;
                                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                const isLate = diffDays > 0;

                                if (isLate) statusSewa += `<br><small style="color:red; font-weight:bold;">Telat ${diffDays} Hari</small>`;
                                else statusSewa += `<br><small style="color:green;">Dalam masa sewa</small>`;

                                // Tombol Kembali
                                actionBtn = `<button class="btn btn-edit" style="background:#f9d84a; color:#2c4532; padding:5px 10px; font-size:12px; margin-right:5px; white-space:nowrap;" onclick="returnRent('${trx.id}', ${isLate ? diffDays : 0})"><i class="fa-solid fa-rotate-left"></i> Kembali</button>`;

                            } else if (trx.rental_status === 'returned') {
                                statusSewa = '<span class="badge badge-success">Selesai</span>';
                                if (trx.fine_amount > 0) statusSewa += `<br><small style="color:#c62828;">Denda: ${new Intl.NumberFormat('id-ID').format(trx.fine_amount)}</small>`;
                            }

                            // Tombol Cetak Faktur (Invoice)
                            if (trx.status !== 'cancelled') {
                                actionBtn += ` <a href="../invoice.php?order=${trx.order_code}" target="_blank" class="btn btn-secondary" style="padding:5px 10px; font-size:12px; background:#eee; color:#333;" title="Cetak Faktur"><i class="fa-solid fa-print"></i></a>`;
                            }

                            tbody.innerHTML += `
                                <tr>
                                    <td>
                                        <strong>${trx.order_code}</strong>
                                        ${trx.invoice_no ? `<br><small style="color:#888">${trx.invoice_no}</small>` : ''}
                                    </td>
                                    <td>
                                        <strong>${trx.customer_name}</strong>
                                        <br><small style="color:#666">${trx.customer_phone}</small>
                                    </td>
                                    <td>${total}</td>
                                    <td>${metodeHtml}</td>
                                    <td>${statusBayarHtml}</td>
                                    <td>${statusSewa}</td>
                                    <td align="center">${actionBtn}</td>
                                </tr>
                            `;
                        });
                        renderPagination(result.pagination, paginationDiv);
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" align="center">Tidak ada data ditemukan.</td></tr>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="7" align="center" style="color:red;">Gagal memuat data. Periksa koneksi/server.</td></tr>';
                });
        }

        function renderPagination(meta, container) {
            if (meta.total_pages <= 1) return;
            let html = `<button class="page-btn" ${meta.current_page === 1 ? 'disabled' : ''} onclick="changePage(${meta.current_page - 1})">&laquo;</button>`;
            for (let i = 1; i <= meta.total_pages; i++) {
                html += `<button class="page-btn ${i === meta.current_page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }
            html += `<button class="page-btn" ${meta.current_page === meta.total_pages ? 'disabled' : ''} onclick="changePage(${meta.current_page + 1})">&raquo;</button>`;
            container.innerHTML = html;
        }

        function changePage(page) { currentPage = page; fetchTransactions(); }

        // --- ACTION HANDLERS ---

        function startRent(id, type) {
            let msg = "Konfirmasi barang diserahkan ke penyewa?";
            if (type === 'cod') msg = "💰 Konfirmasi pembayaran tunai diterima LUNAS dan barang diserahkan?";

            if (confirm(msg)) {
                fetch('../api/process_rental.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'start_rent', order_id: id })
                })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) { alert(d.message); fetchTransactions(); }
                        else { alert(d.message); }
                    })
                    .catch(e => alert("Error: " + e));
            }
        }

        function returnRent(id, lateDays) {
            let fine = 0;
            let msg = "Konfirmasi barang dikembalikan?";

            if (lateDays > 0) {
                // Contoh: Denda 50.000 per hari
                const denda = lateDays * 50000;
                msg = `⚠️ Terlambat ${lateDays} hari.\nSistem menyarankan Denda: Rp ${new Intl.NumberFormat('id-ID').format(denda)}\n\nApakah denda sudah dibayar dan barang dikembalikan?`;
                fine = denda;
            }

            if (confirm(msg)) {
                fetch('../api/process_rental.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'finish_rent', order_id: id, fine: fine })
                })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) { alert(d.message); fetchTransactions(); }
                        else { alert(d.message); }
                    })
                    .catch(e => alert("Error: " + e));
            }
        }
    </script>
</body>

</html>