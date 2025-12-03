<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="index.php" class="active"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
                </li>
                <li>
                    <a href="produk.php">
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
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Dashboard Overview</h1>
                </div>
            </div>

            <div class="summary-cards">
                <div class="card">
                    <div class="card-info">
                        <h3>Total Pendapatan</h3>
                        <p class="value" id="val-revenue">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h3>Transaksi Bulan Ini</h3>
                        <p class="value" id="val-trx-month">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h3>Transaksi Tahun Ini</h3>
                        <p class="value" id="val-trx-year">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h3>Total Transaksi</h3>
                        <p class="value" id="val-trx-total">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-receipt"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h3>Jumlah Produk</h3>
                        <p class="value" id="val-products">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-campground"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h3>Total Pelanggan</h3>
                        <p class="value" id="val-cust">Loading...</p>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                </div>
            </div>

            <div class="charts-wrapper">
                <div class="chart-container">
                    <h3><i class="fa-solid fa-chart-line"></i> Pendapatan 6 Bulan Terakhir</h3>
                    <div class="chart-container-body">
                        <canvas id="revenueLineChart"></canvas>
                    </div>
                </div>

                <div class="chart-grid-2">
                    <div class="chart-container">
                        <h3><i class="fa-solid fa-calendar-week"></i> Transaksi 7 Hari Terakhir</h3>
                        <div class="chart-container-body">
                            <canvas id="dailyBarChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-container">
                        <h3><i class="fa-solid fa-chart-pie"></i> Kategori Terlaris</h3>
                        <div class="chart-container-body">
                            <canvas id="categoryDoughnutChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2>5 Transaksi Terbaru</h2>
                    <a href="transaksi.php" style="color:var(--brand); text-decoration:none; font-weight:600;">Lihat
                        Semua <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Order Code</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recent-trx-body">
                            <tr>
                                <td colspan="5" align="center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // --- Sidebar Logic ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if (toggleBtn) { toggleBtn.addEventListener('click', toggleSidebar); overlay.addEventListener('click', toggleSidebar); }

        // --- FETCH REAL DATA ---
        document.addEventListener('DOMContentLoaded', function () {
            fetchDashboardData();
        });

        function fetchDashboardData() {
            fetch('../api/get_dashboard_data.php')
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        // 1. Update Kartu Summary
                        document.getElementById('val-revenue').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(res.summary.revenue);

                        document.getElementById('val-trx-month').innerText = res.summary.trx_month + " Pesanan";

                        // Update Data Baru
                        document.getElementById('val-trx-year').innerText = res.summary.trx_year + " Pesanan";
                        document.getElementById('val-trx-total').innerText = res.summary.trx_total + " Pesanan";

                        document.getElementById('val-products').innerText = res.summary.products + " Unit";
                        document.getElementById('val-cust').innerText = res.summary.customers + " Orang";

                        // 2. Update Charts
                        initCharts(res.charts);

                        // 3. Update Tabel Terbaru
                        const tableBody = document.getElementById('recent-trx-body');
                        tableBody.innerHTML = '';
                        if (res.recent.length > 0) {
                            res.recent.forEach(t => {
                                const total = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(t.total_amount);
                                const date = new Date(t.created_at).toLocaleDateString('id-ID');

                                let statusHtml = '';
                                if (t.status === 'paid') statusHtml = '<span style="color:green; font-weight:bold;">Lunas</span>';
                                else if (t.status === 'pending') statusHtml = '<span style="color:orange; font-weight:bold;">Pending</span>';
                                else statusHtml = '<span style="color:red; font-weight:bold;">Batal</span>';

                                tableBody.innerHTML += `
                                    <tr>
                                        <td><strong>${t.order_code}</strong></td>
                                        <td>${t.customer_name}</td>
                                        <td>${total}</td>
                                        <td>${date}</td>
                                        <td>${statusHtml}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            tableBody.innerHTML = '<tr><td colspan="5" align="center">Belum ada transaksi.</td></tr>';
                        }
                    } else {
                        console.error("Gagal memuat data: " + res.message);
                    }
                })
                .catch(err => console.error("Error:", err));
        }

        // --- INIT CHART JS ---
        function initCharts(data) {
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#666';
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#2c4532', font: { weight: '600' } } } }
            };

            // Revenue Chart
            new Chart(document.getElementById('revenueLineChart'), {
                type: 'line',
                data: {
                    labels: data.revenue.labels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: data.revenue.data,
                        backgroundColor: 'rgba(249, 216, 74, 0.2)',
                        borderColor: '#2c4532', borderWidth: 3, tension: 0.4, fill: true
                    }]
                }, options: commonOptions
            });

            // Daily Trx Chart
            new Chart(document.getElementById('dailyBarChart'), {
                type: 'bar',
                data: {
                    labels: data.daily.labels,
                    datasets: [{
                        label: 'Transaksi',
                        data: data.daily.data,
                        backgroundColor: '#2c4532', borderRadius: 6
                    }]
                }, options: commonOptions
            });

            // Category Chart
            new Chart(document.getElementById('categoryDoughnutChart'), {
                type: 'doughnut',
                data: {
                    labels: data.category.labels,
                    datasets: [{
                        data: data.category.data,
                        backgroundColor: ['#2c4532', '#f9d84a', '#e8c438', '#d3d3d3'], borderWidth: 0
                    }]
                }, options: commonOptions
            });
        }
    </script>
</body>

</html>