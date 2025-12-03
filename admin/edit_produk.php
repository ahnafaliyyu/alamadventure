<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #2c4532;
            outline: none;
        }

        .btn-submit {
            background: #2c4532;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #1e3323;
        }

        /* Image Preview */
        .img-preview-box {
            margin-top: 10px;
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .img-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-preview-placeholder {
            color: #aaa;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>ALAM<span>ADVENTURE</span></h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="produk.php" class="active"><i class="fa-solid fa-arrow-left"></i> <span>Kembali</span></a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Edit Produk</h1>
                </div>
            </div>

            <div class="content-section">
                <div class="form-container">
                    <form id="editForm" enctype="multipart/form-data">
                        <input type="hidden" id="productId" name="id">

                        <div class="form-group">
                            <label>Nama Produk</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Harga Sewa (per hari)</label>
                                <input type="number" id="price" name="price" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Stok Total</label>
                                <input type="number" id="stock" name="stock" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Gambar Produk (Biarkan kosong jika tidak diganti)</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*"
                                onchange="previewImage(this)">
                            <div class="img-preview-box">
                                <img id="preview" src="" alt="Preview" style="display:none;">
                                <span class="img-preview-placeholder" id="placeholder">Tidak ada foto</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">Simpan Perubahan</button>
                        <a href="produk.php"
                            style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) { toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }); }

        // --- LOGIC EDIT PRODUK ---

        // 1. Ambil ID dari URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');

        if (!productId) {
            alert("ID Produk tidak ditemukan!");
            window.location.href = 'produk.php';
        }

        // 2. Load Data Produk
        document.addEventListener('DOMContentLoaded', () => {
            fetch(`../api/get_product_details.php?id=${productId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const p = data.data;
                        document.getElementById('productId').value = p.id;
                        document.getElementById('name').value = p.name;
                        document.getElementById('price').value = p.price_per_day;
                        document.getElementById('stock').value = p.stock;
                        document.getElementById('description').value = p.description;

                        if (p.image_url) {
                            const preview = document.getElementById('preview');
                            const placeholder = document.getElementById('placeholder');
                            preview.src = p.image_url; // Pastikan path dari API benar
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                        }
                    } else {
                        alert("Gagal memuat data: " + data.message);
                        window.location.href = 'produk.php';
                    }
                })
                .catch(err => console.error(err));
        });

        // 3. Preview Image Logic
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const placeholder = document.getElementById('placeholder');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 4. Handle Submit
        document.getElementById('editForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('../api/update_product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Produk berhasil diperbarui!");
                        window.location.href = 'produk.php';
                    } else {
                        alert("Gagal: " + data.message);
                        btn.innerHTML = 'Simpan Perubahan';
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    alert("Terjadi kesalahan sistem.");
                    console.error(err);
                    btn.innerHTML = 'Simpan Perubahan';
                    btn.disabled = false;
                });
        });
    </script>
</body>

</html>