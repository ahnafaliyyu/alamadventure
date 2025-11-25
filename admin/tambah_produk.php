<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<?php /* konversi dari tambah_produk.html */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d6e2d9;
            background-color: #fff;
            border-radius: 12px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        #message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            display: none;
        }
        #message.success {
            background-color: #d4edda;
            color: #155724;
        }
        #message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
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
                <h1>Tambah Produk Baru</h1>
                <a href="produk.php" class="btn">Kembali</a>
            </div>

            <div class="card">
                <form id="add-product-form">
                    <div class="form-group">
                        <label for="name">Nama Produk</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price_per_day">Harga Sewa / hari (Rp)</label>
                        <input type="number" id="price_per_day" name="price_per_day" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stok</label>
                        <input type="number" id="stock" name="stock" required>
                    </div>
                    <div class="form-group">
                        <label for="image_url">URL Gambar</label>
                        <input type="text" id="image_url" name="image_url" placeholder="Contoh: public/nama_gambar.png">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Produk</button>
                    </div>
                </form>
                <div id="message"></div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('add-product-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah form submit biasa

            const messageDiv = document.getElementById('message');
            const form = event.target;
            const formData = new FormData(form);
            
            // Konversi FormData ke objek biasa
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            messageDiv.style.display = 'none';
            messageDiv.textContent = '';

            fetch('../api/add_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    messageDiv.textContent = result.message;
                    messageDiv.className = 'success';
                    form.reset(); // Kosongkan form setelah sukses
                } else {
                    messageDiv.textContent = result.message || 'Terjadi kesalahan yang tidak diketahui.';
                    messageDiv.className = 'error';
                }
                messageDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.textContent = 'Tidak dapat terhubung ke server. Cek konsol untuk detail.';
                messageDiv.className = 'error';
                messageDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>