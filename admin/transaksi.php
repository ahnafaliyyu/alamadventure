<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<?php /* konversi dari transaksi.html */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                Alam Adventure
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="transaksi.php" class="active">Transaksi</a></li>
                <li class="logout"><a href="logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="main-header">
                <h1>Manajemen Transaksi</h1>
            </div>

            <div class="content-section">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Nama Pelanggan</th>
                            <th>Total</th>
                            <th>Tanggal Sewa</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>TRX-0012</td>
                            <td>Budi Santoso</td>
                            <td>Rp 450.000</td>
                            <td>12 Nov 2025</td>
                            <td>15 Nov 2025</td>
                            <td>Selesai</td>
                            <td><a href="#" class="btn btn-primary">Detail</a></td>
                        </tr>
                        <tr>
                            <td>TRX-0011</td>
                            <td>Ani Yudhoyono</td>
                            <td>Rp 1.200.000</td>
                            <td>11 Nov 2025</td>
                            <td>13 Nov 2025</td>
                            <td>Selesai</td>
                            <td><a href="#" class="btn btn-primary">Detail</a></td>
                        </tr>
                        <tr>
                            <td>TRX-0010</td>
                            <td>Charlie</td>
                            <td>Rp 250.000</td>
                            <td>10 Nov 2025</td>
                            <td>11 Nov 2025</td>
                            <td>Dibatalkan</td>
                            <td><a href="#" class="btn btn-primary">Detail</a></td>
                        </tr>
                        <tr>
                            <td>TRX-0009</td>
                            <td>Diana Putri</td>
                            <td>Rp 750.000</td>
                            <td>15 Nov 2025</td>
                            <td>18 Nov 2025</td>
                            <td>Aktif</td>
                            <td><a href="#" class="btn btn-primary">Detail</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>