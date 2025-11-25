<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<?php /* konversi dari produk.html */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Alam Adventure</title>
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
                <li><a href="produk.php" class="active">Produk</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li class="logout"><a href="logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="main-header">
                <h1>Manajemen Produk</h1>
                <a href="tambah_produk.php" class="btn btn-primary">Tambah Produk Baru</a>
            </div>

            <div class="content-section">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>ID Produk</th>
                            <th>Nama Produk</th>
                            <th>Harga Sewa/hari</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="product-table-body">
                        <!-- Data produk akan dimuat di sini oleh JavaScript -->
                        <tr>
                            <td colspan="5" style="text-align: center;">Memuat data produk...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.getElementById('product-table-body');
            loadProducts(); // Panggil fungsi untuk memuat produk

            function loadProducts() {
                fetch('../api/get_products.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    tableBody.innerHTML = ''; // Kosongkan isi tabel
                    if (result.success) {
                        if (result.data.length > 0) {
                            result.data.forEach(product => {
                                const row = document.createElement('tr');
                                
                                // Format harga ke format Rupiah
                                const price = new Intl.NumberFormat('id-ID', { 
                                    style: 'currency', 
                                    currency: 'IDR', 
                                    minimumFractionDigits: 0 
                                }).format(product.price_per_day);

                                row.innerHTML = `
                                    <td>${product.id}</td>
                                    <td>${product.name}</td>
                                    <td>${price}</td>
                                    <td>${product.stock}</td>
                                    <td>
                                        <a href="../api/edit_produk.html?id=${product.id}" class="btn btn-edit">Edit</a>
                                        <button class="btn btn-delete" data-id="${product.id}">Hapus</button>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Tidak ada data produk.</td></tr>';
                        }
                    } else {
                        throw new Error(result.message || 'Gagal memuat data dari API.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center;">Terjadi kesalahan: ${error.message}</td></tr>`;
                });
            }

            // Event listener untuk tombol hapus
            tableBody.addEventListener('click', function(event) {
                if (event.target.classList.contains('btn-delete')) {
                    const button = event.target;
                    const productId = button.dataset.id;
                    
                    if (confirm(`Apakah Anda yakin ingin menghapus produk dengan ID ${productId}?`)) {
                        fetch('../api/delete_product.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: productId })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                alert(result.message);
                                // Hapus baris dari tabel tanpa reload halaman
                                button.closest('tr').remove();
                            } else {
                                alert(`Gagal menghapus: ${result.message}`);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Tidak dapat terhubung ke server.');
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>