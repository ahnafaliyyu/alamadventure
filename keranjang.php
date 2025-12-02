<?php
require_once 'config/init.php';

// --- 1. AJAX HANDLER (Untuk Update Qty) ---
if (isset($_POST['ajax_update_qty'])) {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $qty = (int) $_POST['qty'];
    if ($qty < 1)
        $qty = 1;

    // Cek Stok Real-time
    $stmt = $conn->prepare("SELECT p.stock, (p.stock - COALESCE((SELECT SUM(qty) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id AND o.rental_status != 'returned' AND o.status != 'cancelled'), 0)) as avail FROM products p WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $realStock = $res['avail'] ?? 0;

    if ($qty > $realStock) {
        echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Tersedia: $realStock", 'reset_qty' => $realStock]);
        exit;
    }

    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = $qty;
        $itemPrice = $_SESSION['cart'][$id]['price'];
        $newSubtotal = $itemPrice * $qty;

        $grandTotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $grandTotal += $item['price'] * $item['qty'];
        }

        echo json_encode(['success' => true, 'new_subtotal_rp' => 'Rp ' . number_format($newSubtotal, 0, ',', '.'), 'new_total_raw' => $grandTotal]);
    }
    exit;
}

// Hapus Item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: keranjang.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Alam Adventure</title>
    <link rel="icon" href="/public/logo.png" type="image/png" />
    <link rel="stylesheet" href="./public/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        /* --- VARIABEL & RESET --- */
        :root {
            --brand: #2c4532;
            --brand-light: #4a6b53;
            --accent: #f9d84a;
            --bg-body: #f4f7f5;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            margin: 0;
            padding-bottom: 40px;
        }

        /* --- WRAPPER --- */
        .cart-wrapper {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
            text-align: center; /* Judul tengah di mobile lebih rapi */
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--brand);
            display: inline-block;
            position: relative;
        }
        
        .page-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--accent);
            margin: 8px auto 0;
            border-radius: 2px;
        }

        /* --- EMPTY STATE --- */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        .empty-cart i { font-size: 64px; color: #d1d5db; margin-bottom: 20px; }
        .empty-cart h3 { color: var(--text-dark); margin-bottom: 10px; }
        .btn-shop {
            background: var(--brand); color: white;
            padding: 12px 24px; border-radius: 50px;
            text-decoration: none; font-weight: 600;
            transition: 0.3s; display: inline-block; margin-top: 15px;
        }
        .btn-shop:hover { background: var(--brand-light); transform: translateY(-2px); }

        /* --- CART ITEMS (TABEL / CARD) --- */
        .cart-container {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th {
            background: #f9fafb;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 16px 24px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .cart-table td {
            padding: 20px 24px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }
        .cart-table tr:last-child td { border-bottom: none; }

        /* Product Cell Styling */
        .product-flex { display: flex; align-items: center; gap: 20px; }
        .cart-img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid #f3f4f6;
        }
        .product-details h4 { margin: 0 0 4px; font-size: 16px; color: var(--text-dark); }
        .price-single { color: var(--text-muted); font-size: 14px; }

        /* Qty Input */
        .qty-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .qty-live {
            width: 50px; text-align: center;
            border: none; padding: 8px 0;
            font-weight: 600; color: var(--text-dark);
            outline: none; -moz-appearance: textfield;
        }
        .qty-live::-webkit-outer-spin-button,
        .qty-live::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .subtotal-text { font-weight: 700; color: var(--brand); font-size: 16px; }
        
        .btn-trash {
            color: #ef4444; background: #fee2e2;
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.2s;
        }
        .btn-trash:hover { background: #ef4444; color: white; }

        /* --- CHECKOUT LAYOUT --- */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr; /* Kiri lebih lebar */
            gap: 30px;
            align-items: start;
        }

        .card-box {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-md);
        }

        .card-box h3 {
            font-family: 'Poppins', sans-serif;
            margin-top: 0; margin-bottom: 20px;
            font-size: 18px; color: var(--brand);
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 15px;
        }
        .card-box h3 i { color: var(--accent); }

        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-dark); }
        .form-control {
            width: 100%; padding: 12px 15px;
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 14px; background: #f9fafb;
            transition: all 0.2s; margin-bottom: 15px;
            box-sizing: border-box; /* Fix padding issue */
        }
        .form-control:focus { border-color: var(--brand); background: white; outline: none; box-shadow: 0 0 0 3px rgba(44,69,50,0.1); }

        /* Delivery Options */
        .delivery-options {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;
        }
        .radio-card {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: 12px; cursor: pointer;
            transition: 0.2s; text-align: center;
            position: relative;
        }
        .radio-card input { display: none; }
        .radio-card:has(input:checked) {
            border-color: var(--brand); background: #f0fdf4; color: var(--brand);
        }
        .radio-card span { font-weight: 600; font-size: 14px; display: block; }
        .radio-card i { font-size: 20px; margin-bottom: 5px; display: block; color: var(--text-muted); }
        .radio-card:has(input:checked) i { color: var(--brand); }

        /* Map Section */
        #map { height: 300px; width: 100%; border-radius: var(--radius-md); border: 2px solid #e5e7eb; margin-bottom: 15px; }
        .shipping-result {
            background: #eff6ff; border-left: 4px solid #3b82f6;
            padding: 12px; border-radius: 6px;
            color: #1e40af; font-size: 14px; margin-top: 10px;
        }

        /* Payment Section */
        .payment-list { display: flex; flex-direction: column; gap: 10px; }
        .pay-option {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 15px; border: 1px solid #e5e7eb;
            border-radius: 8px; cursor: pointer; transition: 0.2s;
        }
        .pay-option:hover { background: #f9fafb; }
        .pay-option:has(input:checked) { border-color: var(--brand); background: #f0fdf4; }
        .pay-option input { accent-color: var(--brand); width: 18px; height: 18px; }

        .summary-row {
            display: flex; justify-content: space-between;
            margin-bottom: 10px; font-size: 14px; color: var(--text-muted);
        }
        .summary-total {
            display: flex; justify-content: space-between;
            margin-top: 20px; padding-top: 15px;
            border-top: 2px dashed #e5e7eb;
            font-size: 18px; font-weight: 700; color: var(--brand);
        }

        .btn-checkout {
            background: linear-gradient(135deg, #f9d84a 0%, #f6c23e 100%);
            color: #1f2937;
            width: 100%; padding: 16px;
            border: none; border-radius: 50px;
            font-weight: 700; font-size: 16px;
            cursor: pointer; margin-top: 25px;
            box-shadow: 0 4px 12px rgba(249, 216, 74, 0.4);
            transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(249, 216, 74, 0.5); }

        /* --- RESPONSIVE MOBILE STYLES --- */
        @media (max-width: 768px) {
            .cart-table thead { display: none; } /* Sembunyikan header tabel */
            
            .cart-table, .cart-table tbody, .cart-table tr, .cart-table td {
                display: block; width: 100%; box-sizing: border-box;
            }

            .cart-table tr {
                background: white; margin-bottom: 15px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }
            .cart-table tr:last-child { border-bottom: none; margin-bottom: 0; }

            .cart-table td {
                padding: 10px 24px;
                text-align: right;
                display: flex; justify-content: space-between; align-items: center;
                border: none;
            }

            /* Kolom Produk Spesial */
            .cart-table td:first-child {
                text-align: left; padding-top: 20px;
                justify-content: flex-start;
            }

            /* Label Data di Kiri untuk Mobile */
            .cart-table td:not(:first-child)::before {
                content: attr(data-label);
                font-weight: 600; color: var(--text-muted); font-size: 13px;
            }

            .product-flex { width: 100%; }
            .cart-img { width: 70px; height: 70px; }
            
            /* Checkout Grid jadi Stack */
            .checkout-grid { grid-template-columns: 1fr; gap: 20px; }
            
            .delivery-options { grid-template-columns: 1fr; }
            .page-title { font-size: 26px; }
        }
    </style>
</head>
<body>
    <nav class="nav"><div class="desktop-nav"><div class="logo"><img src="/public/logo.png" width="30px"></div><ul class="nav-menu"><li><a href="index.php" class="nav-link">Beranda</a></li><li><a href="katalog.php" class="nav-link">Katalog</a></li></ul></div><div class="btn-kanan"><a href="keranjang.php" class="nav-link active"><i class="fas fa-shopping-cart"></i> <span id="navCartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span></a></div></nav>
    
    <div class="cart-wrapper">
        <div class="page-header">
            <h1 class="page-title">Keranjang Saya</h1>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>Keranjang Anda kosong</h3>
                    <p style="color:#6b7280;">Belum ada barang yang disewa. Yuk pilih perlengkapanmu!</p>
                    <a href="katalog.php" class="btn-shop">Mulai Belanja <i class="fas fa-arrow-right"></i></a>
                </div>
        <?php else: ?>
                <form action="process_checkout.php" method="POST" id="checkoutForm">
                
                    <div class="cart-container">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Produk</th>
                                    <th style="width: 15%;">Harga Sewa</th>
                                    <th style="width: 15%;">Jumlah</th>
                                    <th style="width: 15%;">Subtotal</th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalProduk = 0;
                                foreach ($_SESSION['cart'] as $id => $item):
                                    $sub = $item['price'] * $item['qty'];
                                    $totalProduk += $sub;
                                    ?>
                                    <tr>
                                        <td data-label="Produk">
                                            <div class="product-flex">
                                                <img src="<?= htmlspecialchars($item['image']) ?>" class="cart-img" alt="Foto Produk">
                                                <div class="product-details">
                                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                                    <span class="price-single">@ Rp <?= number_format($item['price'], 0, ',', '.') ?> / hari</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Harga">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                        <td data-label="Jumlah">
                                            <div class="qty-control">
                                                <input type="number" value="<?= $item['qty'] ?>" class="qty-live" data-id="<?= $id ?>">
                                            </div>
                                        </td>
                                        <td data-label="Subtotal" class="subtotal-text" id="sub-<?= $id ?>">Rp <?= number_format($sub, 0, ',', '.') ?></td>
                                        <td data-label="Hapus" style="text-align: right;">
                                            <a href="?remove=<?= $id ?>" class="btn-trash" onclick="return confirm('Hapus item ini?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="checkout-grid">
                        <div class="card-box">
                            <h3><i class="fas fa-user-edit"></i> Informasi Penyewa</h3>
                        
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="customer_name" class="form-control" required placeholder="Contoh: Budi Santoso">
                        
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" name="customer_phone" class="form-control" required placeholder="Contoh: 08123456789">
                        
                            <label class="form-label">Durasi Sewa (Hari)</label>
                            <input type="number" name="duration" id="duration" class="form-control" value="1" min="1" required>

                            <h3 style="margin-top: 30px;"><i class="fas fa-truck"></i> Metode Pengambilan</h3>
                            <div class="delivery-options">
                                <label class="radio-card">
                                    <input type="radio" name="delivery_method" value="pickup" checked onchange="toggleMap(false)">
                                    <i class="fas fa-store"></i>
                                    <span>Ambil di Toko</span>
                                </label>
                                <label class="radio-card">
                                    <input type="radio" name="delivery_method" value="delivery" onchange="toggleMap(true)">
                                    <i class="fas fa-motorcycle"></i>
                                    <span>Diantar Kurir</span>
                                </label>
                            </div>

                            <div id="deliverySection" style="display:none;">
                                <label class="form-label">Pilih Lokasi Pengantaran di Peta</label>
                                <div id="map"></div>
                            
                                <label class="form-label">Detail Alamat</label>
                                <textarea name="delivery_address" id="addressStr" class="form-control" rows="2" placeholder="Jalan, Nomor Rumah, Patokan, dll..."></textarea>
                            
                                <input type="hidden" name="lat" id="lat">
                                <input type="hidden" name="long" id="long">
                                <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
                                <input type="hidden" name="distance_km" id="distance_km" value="0">
                            
                                <div class="shipping-result" id="shippingInfo" style="display:none;">
                                    <i class="fas fa-info-circle"></i> Jarak: <strong id="distVal">0</strong> km &bull; Ongkir: <strong id="shipVal">Rp 0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="card-box">
                            <h3><i class="fas fa-wallet"></i> Pembayaran</h3>
                            <div class="payment-list">
                                <label class="pay-option">
                                    <input type="radio" name="payment_method" value="online" checked>
                                    <div>
                                        <div style="font-weight:600;">Transfer / QRIS</div>
                                        <div style="font-size:12px; color:#666;">Otomatis via Midtrans</div>
                                    </div>
                                </label>
                                <label class="pay-option">
                                    <input type="radio" name="payment_method" value="cod">
                                    <div>
                                        <div style="font-weight:600;">Bayar di Tempat (COD)</div>
                                        <div style="font-size:12px; color:#666;">Cash saat terima barang</div>
                                    </div>
                                </label>
                            </div>

                            <div style="margin-top: 25px;">
                                <div class="summary-row">
                                    <span>Total Sewa (per hari)</span>
                                    <span id="totalBarangRaw" data-val="<?= $totalProduk ?>">Rp <?= number_format($totalProduk, 0, ',', '.') ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Ongkos Kirim</span>
                                    <span id="displayOngkir" style="color: #d35400;">Rp 0</span>
                                </div>
                                <div class="summary-total">
                                    <span>Total Bayar</span>
                                    <span id="grandTotalDisplay">Rp <?= number_format($totalProduk, 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <button type="submit" class="btn-checkout">
                                Buat Pesanan <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                            </button>
                        </div>
                    </div>
                </form>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // LOGIKA MAPS SAMA SEPERTI SEBELUMNYA
        const SHOP_LAT = -0.502183; 
        const SHOP_LNG = 117.153801;
        const PRICE_PER_15KM = 20000;

        let map, marker;
        let currentOngkir = 0;

        function initMap() {
            if(map) return; 
            map = L.map('map').setView([SHOP_LAT, SHOP_LNG], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            L.marker([SHOP_LAT, SHOP_LNG]).addTo(map).bindPopup("<b>Toko Alam Adventure</b>").openPopup();

            map.on('click', function(e) {
                if(marker) map.removeLayer(marker);
                marker = L.marker(e.latlng).addTo(map);
                document.getElementById('lat').value = e.latlng.lat;
                document.getElementById('long').value = e.latlng.lng;
                calculateDistance(e.latlng.lat, e.latlng.lng);
            });
        }

        function calculateDistance(userLat, userLng) {
            const R = 6371; 
            const dLat = (userLat - SHOP_LAT) * Math.PI / 180;
            const dLng = (userLng - SHOP_LNG) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(SHOP_LAT * Math.PI / 180) * Math.cos(userLat * Math.PI / 180) * Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const d = R * c; 

            const roundedDist = d.toFixed(1);
            const multiplier = Math.ceil(d / 15);
            const cost = multiplier * PRICE_PER_15KM;

            document.getElementById('distance_km').value = roundedDist;
            document.getElementById('shipping_cost').value = cost;
            document.getElementById('distVal').innerText = roundedDist;
            document.getElementById('shipVal').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(cost);
            document.getElementById('displayOngkir').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(cost);
            document.getElementById('shippingInfo').style.display = 'block';

            currentOngkir = cost;
            calculateGrandTotal();
        }

        function toggleMap(show) {
            const el = document.getElementById('deliverySection');
            if(show) {
                el.style.display = 'block';
                initMap();
                setTimeout(() => { map.invalidateSize(); }, 200);
            } else {
                el.style.display = 'none';
                currentOngkir = 0;
                document.getElementById('shipping_cost').value = 0;
                document.getElementById('displayOngkir').innerText = "Rp 0";
                calculateGrandTotal();
            }
        }

        function calculateGrandTotal() {
            const totalBarang = parseInt(document.getElementById('totalBarangRaw').dataset.val);
            const duration = parseInt(document.getElementById('duration').value) || 1;
            const grandTotal = (totalBarang * duration) + currentOngkir;
            document.getElementById('grandTotalDisplay').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(grandTotal);
        }

        document.getElementById('duration').addEventListener('input', calculateGrandTotal);

        // LIVE QTY UPDATE
        document.querySelectorAll('.qty-live').forEach(input => {
            input.addEventListener('change', function() {
                const id = this.dataset.id;
                const qty = this.value;
                const inputElem = this;
                
                const formData = new FormData();
                formData.append('ajax_update_qty', '1');
                formData.append('id', id);
                formData.append('qty', qty);

                fetch('keranjang.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`sub-`+id).innerText = data.new_subtotal_rp;
                        document.getElementById('totalBarangRaw').dataset.val = data.new_total_raw;
                        document.getElementById('totalBarangRaw').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(data.new_total_raw);
                        calculateGrandTotal();
                    } else {
                        alert(data.message);
                        if(data.reset_qty) inputElem.value = data.reset_qty;
                    }
                });
            });
        });
    </script>
</body>
</html>