<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang</title>
    <link rel="icon" href="public/logo.png" type="image/png" />
    <link rel="stylesheet" href="styling-admin">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styling Preview Gambar */
        .img-preview-box {
            margin-top: 10px;
            width: 100%;
            max-width: 200px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background: #fafafa;
        }

        .img-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .img-preview-placeholder {
            color: #aaa;
            font-size: 13px;
            text-align: center;
        }

        .form-group input[type="file"] {
            padding: 10px;
            background: white;
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
                    <a href="dashboard-admin"><i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span></a>
                </li>
                <li>
                    <a href="barang-admin" class="active">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Produk</span>
                    </a>
                </li>
                <li>
                    <a href="transaksi-admin">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="pengaturan-admin">
                        <i class="fa-solid fa-gear"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>
                <li class="Beranda">
                    <a href="../beranda">
                        <i class="fa-solid fa-house"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="Logout">
                    <a href="logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Tambah Produk Baru</h1>
                </div>
            </div>

            <div class="content-section" style="max-width: 800px;">
                <form id="addForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Produk <span style="color:red">*</span></label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="Contoh: Tenda Dome 4 Orang">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Harga Sewa / Hari (Rp) <span style="color:red">*</span></label>
                            <input type="number" name="price_per_day" class="form-control" required placeholder="50000">
                        </div>
                        <div class="form-group">
                            <label>Stok Awal <span style="color:red">*</span></label>
                            <input type="number" name="stock" class="form-control" required placeholder="10">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4"
                            placeholder="Deskripsi lengkap produk..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto Produk</label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*"
                            onchange="previewImage(this)">
                        <div class="img-preview-box">
                            <img id="preview" src="" alt="Preview">
                            <div class="img-preview-placeholder" id="placeholder">
                                <i class="fa-solid fa-image" style="font-size: 24px; margin-bottom:5px;"></i><br>
                                Preview Foto
                            </div>
                        </div>
                    </div>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="fa-solid fa-save"></i> Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="generic-overlay" id="genericModal">
        <div class="generic-box">
            <div class="generic-icon" id="genericIcon"></div>
            <h3 class="generic-title" id="genericTitle"></h3>
            <p class="generic-text" id="genericText"></p>
            <div class="generic-buttons">
                <button class="btn-generic btn-primary-modal" onclick="closeGenericModal()">OK</button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if (toggleBtn) { toggleBtn.addEventListener('click', toggleSidebar); overlay.addEventListener('click', toggleSidebar); }

        // --- 1. PREVIEW GAMBAR ---
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
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        }

        // --- 2. MODAL CUSTOM ---
        const modal = document.getElementById('genericModal');
        const mIcon = document.getElementById('genericIcon');
        const mTitle = document.getElementById('genericTitle');
        const mText = document.getElementById('genericText');

        function closeGenericModal() { modal.style.display = 'none'; }

        function showModal(title, msg, type = 'success') {
            if (type === 'success') {
                mIcon.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
                mIcon.className = 'generic-icon success';
            } else {
                mIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>';
                mIcon.className = 'generic-icon danger';
            }
            mTitle.innerText = title;
            mText.innerText = msg;
            modal.style.display = 'flex';
        }

        // --- 3. SUBMIT FORM VIA AJAX ---
        document.getElementById('addForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerHTML;

            // Loading state
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            btn.disabled = true;

            // Ambil semua data form termasuk file
            const formData = new FormData(this);

            fetch('../api/add_product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModal('Berhasil!', data.message, 'success');
                        // Reset form & preview
                        document.getElementById('addForm').reset();
                        document.getElementById('preview').style.display = 'none';
                        document.getElementById('placeholder').style.display = 'block';
                    } else {
                        showModal('Gagal!', data.message, 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showModal('Error', 'Terjadi kesalahan sistem atau koneksi.', 'danger');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });
    </script>
</body>

</html>