<?php require_once __DIR__ . '/../middleware/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header"><h2>ALAM<span>ADVENTURE</span></h2></div>
            <ul class="sidebar-nav">
                <li><a href="produk.php" class="active"><i class="fa-solid fa-arrow-left"></i> <span>Kembali</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Tambah Produk</h1>
                </div>
            </div>

            <div class="content-section" style="max-width: 800px;">
                <form id="add-product-form">
                    <div class="form-group">
                        <label>Nama Produk <span style="color:red">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Harga Sewa / Hari (Rp) <span style="color:red">*</span></label>
                            <input type="number" name="price_per_day" required>
                        </div>
                        <div class="form-group">
                            <label>Stok Awal <span style="color:red">*</span></label>
                            <input type="number" name="stock" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="5"></textarea>
                    </div>

                    <div class="form-group">
                        <label>URL Gambar</label>
                        <input type="text" name="image_url" placeholder="public/tenda.png">
                    </div>

                    <div style="text-align:right; margin-top:20px;">
                        <button type="submit" class="btn btn-primary" id="btnSubmit"><i class="fa-solid fa-save"></i> Simpan</button>
                    </div>
                </form>
                <div id="message" style="margin-top:20px; display:none; padding:15px; border-radius:8px;"></div>
            </div>
        </main>
    </div>
    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Form Submit
        document.getElementById('add-product-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const msg = document.getElementById('message');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            
            const data = Object.fromEntries(new FormData(e.target));
            fetch('../api/add_product.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
            }).then(r=>r.json()).then(res => {
                msg.style.display = 'block';
                msg.style.background = res.success ? '#d4edda' : '#f8d7da';
                msg.style.color = res.success ? '#155724' : '#721c24';
                msg.innerText = res.message;
                if(res.success) e.target.reset();
            }).finally(() => { btn.innerHTML = '<i class="fa-solid fa-save"></i> Simpan'; });
        });
    </script>
</body>
</html>