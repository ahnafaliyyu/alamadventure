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
    <link rel="stylesheet" href="/public/css/keranjang.css">
</head>
<body>
    <nav class="nav">
        <div class="desktop-nav">
            <div class="logo">
                <img src="/public/logo.png" width="30px">
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Beranda</a>
            </li><li><a href="katalog.php" class="nav-link">Katalog</a>
        </li>
    </ul>
</div>
      <div class="btn-kanan">
        <a href="keranjang.php" class="nav-link"><i
            class="fas fa-shopping-cart"></i><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></a>
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
          <a href="/admin/index.php">Admin</a>
        <?php else: ?>
          <a href="/admin/login.php">Login</a>
        <?php endif; ?>
      </div>
</nav>
    
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