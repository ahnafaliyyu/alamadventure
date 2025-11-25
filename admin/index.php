<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<?php /* halaman ini hasil konversi dari index.html agar terproteksi */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                Alam Adventure
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li class="logout"><a href="logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="main-header">
                <h1>Dashboard</h1>
            </div>

            <div class="summary-cards">
                <div class="card">
                    <h3>Total Penjualan</h3>
                    <p class="value">Rp 15.750.000</p>
                </div>
                <div class="card">
                    <h3>Jumlah Produk</h3>
                    <p class="value">8</p>
                </div>
                <div class="card">
                    <h3>Transaksi Bulan Ini</h3>
                    <p class="value">12</p>
                </div>
                <div class="card">
                    <h3>Pelanggan Baru</h3>
                    <p class="value">5</p>
                </div>
            </div>

            <div class="charts-grid">
            <!-- Semua chart jadi anak langsung dari .charts-grid -->
            <div class="chart-container">
                <h3>Pendapatan Per Bulan</h3>
                <canvas id="revenueLineChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Transaksi 7 Hari Terakhir</h3>
                <canvas id="dailyBarChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Kategori Produk Populer</h3>
                <canvas id="categoryDoughnutChart"></canvas>
            </div>
            </div>

            <div class="content-section">
                <h2>Transaksi Terbaru</h2>
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Nama Pelanggan</th>
                            <th>Total</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>TRX-0012</td>
                            <td>Budi Santoso</td>
                            <td>Rp 450.000</td>
                            <td>12 Nov 2025</td>
                            <td>Selesai</td>
                        </tr>
                        <tr>
                            <td>TRX-0011</td>
                            <td>Ani Yudhoyono</td>
                            <td>Rp 1.200.000</td>
                            <td>11 Nov 2025</td>
                            <td>Selesai</td>
                        </tr>
                        <tr>
                            <td>TRX-0010</td>
                            <td>Charlie</td>
                            <td>Rp 250.000</td>
                            <td>10 Nov 2025</td>
                            <td>Dibatalkan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Common Chart Options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#2c4532' }
                },
                x: {
                    ticks: { color: '#2c4532' }
                }
            },
            plugins: {
                legend: {
                    labels: { color: '#2c4532' }
                }
            }
        };

        // 1. Line Chart - Monthly Revenue
        const revenueCtx = document.getElementById('revenueLineChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                datasets: [{
                    label: 'Pendapatan',
                    data: [1250000, 1900000, 2100000, 1800000, 2500000, 3100000],
                    backgroundColor: 'rgba(44, 69, 50, 0.1)',
                    borderColor: '#2c4532',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: (value) => {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + 'jt';
                                }
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // 2. Bar Chart - Daily Transactions
        const dailyCtx = document.getElementById('dailyBarChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: [5, 8, 3, 6, 4, 7, 2],
                    backgroundColor: '#f9d84a',
                    borderColor: '#e8c438',
                    borderWidth: 1
                }]
            },
            options: commonOptions
        });

        // 3. Doughnut Chart - Product Categories
        const categoryCtx = document.getElementById('categoryDoughnutChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tenda', 'Kompor', 'Lampu', 'Hammock', 'Lainnya'],
                datasets: [{
                    label: 'Penyewaan per Kategori',
                    data: [45, 25, 15, 10, 5],
                    backgroundColor: [
                        '#2c4532',
                        '#f9d84a',
                        '#7a8f80',
                        '#e8c438',
                        '#a9b4ab'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#2c4532' }
                    }
                }
            }
        });
    </script>
</body>
</html>