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
        /* --- LAYOUT UTAMA --- */
        .main-content {
            width: calc(100% - 260px);
            margin-left: 260px;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 15px;
            }
        }

        /* --- TABLE TO CARD (RESPONSIVE) --- */
        .table-responsive {
            width: 100%;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table th,
        .content-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .content-table th {
            background-color: #f8f9fa;
            color: #2c4532;
            position: sticky;
            top: 0;
        }

        /* MOBILE VIEW CSS */
        @media (max-width: 768px) {
            .content-table thead {
                display: none;
                /* Sembunyikan header tabel di HP */
            }

            .content-table,
            .content-table tbody,
            .content-table tr,
            .content-table td {
                display: block;
                width: 100%;
            }

            .content-table tr {
                background: #fff;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                padding: 15px;
                position: relative;
            }

            .content-table td {
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
            }

            .content-table td:last-child {
                border-bottom: none;
                justify-content: center;
                gap: 10px;
                padding-top: 15px;
            }

            /* Label Data (Pseudo-element) */
            .content-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #666;
                text-align: left;
                flex: 1;
            }
        }

        /* --- BADGES & BUTTONS --- */
        .badge {
            padding: 5px 10px;
            border-radius: 6px;
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
        }

        .badge-secondary {
            background: #f5f5f5;
            color: #616161;
        }

        .btn-act {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: white;
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
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
                <li><a href="produk.php"><i class="fa-solid fa-box-open"></i> <span>Produk</span></a></li>
                <li><a href="transaksi.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Transaksi</span></a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Keluar</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px; width:100%;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"
                        style="display:block; background:none; border:none; font-size:24px;"><i
                            class="fa-solid fa-bars"></i></button>
                    <h1>Transaksi</h1>
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari Order / Nama...">
                </div>
            </div>

            <div class="content-section" style="background:white; padding:20px; border-radius:10px;">
                <div class="table-responsive">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Order Info</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status Bayar</th>
                                <th>Status Sewa</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="trx-table-body">
                            <tr>
                                <td colspan="7" align="center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginationContainer" style="margin-top:20px; text-align:right;"></div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Logic
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        // --- FETCH DATA ---
        let currentPage = 1;
        let searchKeyword = '';
        let debounceTimer;

        document.addEventListener('DOMContentLoaded', fetchTransactions);

        document.getElementById('searchInput').addEventListener('input', function (e) {
            clearTimeout(debounceTimer);
            searchKeyword = e.target.value;
            debounceTimer = setTimeout(() => { currentPage = 1; fetchTransactions(); }, 300);
        });

        function fetchTransactions() {
            const tbody = document.getElementById('trx-table-body');
            tbody.innerHTML = '<tr><td colspan="7" align="center">Memuat...</td></tr>';

            // Panggil API (yang sudah ada auto-cancel logicnya nanti)
            fetch(`../api/get_transactions.php?page=${currentPage}&q=${searchKeyword}`)
                .then(res => res.json())
                .then(result => {
                    tbody.innerHTML = '';
                    if (result.success && result.data.length > 0) {
                        result.data.forEach(trx => {
                            const total = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trx.total_amount);

                            // Badge Helpers
                            const methodBadge = (trx.payment_method === 'cod')
                                ? '<span class="badge badge-cod">COD</span>'
                                : '<span class="badge badge-info">Online</span>';

                            let statusBadge = '';
                            if (trx.status === 'paid') statusBadge = '<span class="badge badge-success">Lunas</span>';
                            else if (trx.status === 'cancelled') statusBadge = '<span class="badge badge-danger">Batal</span>';
                            else statusBadge = '<span class="badge badge-warning">Pending</span>';

                            let rentalBadge = '';
                            if (trx.rental_status === 'ongoing') rentalBadge = '<span class="badge badge-info">Sedang Disewa</span>';
                            else if (trx.rental_status === 'returned') rentalBadge = '<span class="badge badge-success">Selesai</span>';
                            else rentalBadge = '<span class="badge badge-secondary">Menunggu Ambil</span>';

                            // Buttons
                            let buttons = '';
                            if (trx.status !== 'cancelled') {
                                buttons += `<a href="../invoice.php?order=${trx.order_code}" target="_blank" class="btn-act" style="background:#6c757d;" title="Print"><i class="fa-solid fa-print"></i></a> `;
                            }

                            // Tombol Aksi Sewa (Ambil/Kembali)
                            if (trx.status !== 'cancelled') {
                                if (trx.rental_status === 'pending_pickup') {
                                    buttons += `<button class="btn-act" style="background:#2980b9;" onclick="processRental('${trx.id}', 'start')">Ambil Barang</button> `;
                                } else if (trx.rental_status === 'ongoing') {
                                    buttons += `<button class="btn-act" style="background:#f39c12;" onclick="processRental('${trx.id}', 'finish')">Kembalikan</button> `;
                                }
                            }

                            // Tombol Batal (Hanya jika masih pending)
                            if (trx.status === 'pending') {
                                buttons += `<button class="btn-act" style="background:#c0392b;" onclick="cancelOrder(${trx.id})"><i class="fa-solid fa-ban"></i> Batal</button>`;
                            }

                            tbody.innerHTML += `
                                <tr>
                                    <td data-label="Order Info"><strong>${trx.order_code}</strong><br><small>${trx.created_at}</small></td>
                                    <td data-label="Pelanggan">${trx.customer_name}<br><small>${trx.customer_phone}</small></td>
                                    <td data-label="Total">${total}</td>
                                    <td data-label="Metode">${methodBadge}</td>
                                    <td data-label="Status Bayar">${statusBadge}</td>
                                    <td data-label="Status Sewa">${rentalBadge}</td>
                                    <td data-label="Aksi" style="text-align:center;">${buttons}</td>
                                </tr>
                            `;
                        });
                        // Tambahkan navigasi paginasi jika perlu
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" align="center">Tidak ada data.</td></tr>';
                    }
                });
        }

        // --- FUNGSI BATALKAN PESANAN (PERBAIKAN) ---
        function cancelOrder(id) {
            if (!confirm('Yakin ingin membatalkan pesanan ini? Stok akan dikembalikan.')) return;

            fetch('cancel_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_id=' + id
            })
                .then(res => res.json()) // Harap cancel_order.php return JSON
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchTransactions(); // Refresh data tanpa reload
                    } else {
                        alert("Gagal: " + data.message);
                    }
                })
                .catch(err => alert("Terjadi kesalahan sistem."));
        }

        // Fungsi Proses Sewa (Start/Finish)
        function processRental(id, type) {
            const action = (type === 'start') ? 'start_rent' : 'finish_rent';
            let extra = {};

            if (type === 'finish') {
                if (!confirm("Barang sudah diterima kembali dan diperiksa?")) return;
                // Bisa tambah logika denda di sini jika mau
            } else {
                if (!confirm("Serahkan barang ke penyewa?")) return;
            }

            fetch('../api/process_rental.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, order_id: id, ...extra })
            })
                .then(res => res.json())
                .then(d => {
                    alert(d.message);
                    fetchTransactions();
                });
        }
    </script>
</body>

</html>