<?php
require_once 'config/init.php';

// --- 1. AJAX HANDLER (Untuk Update Qty) ---
if (isset($_POST['ajax_update_qty'])) {
    header('Content-Type: application/json');
    $id = $_POST['id'];
    $qty = (int) $_POST['qty'];
    if ($qty < 1) $qty = 1;

    // Cek Stok Real-time
    $stmt = $conn->prepare("SELECT p.stock, (p.stock - COALESCE((SELECT SUM(qty) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id AND o.rental_status != 'returned' AND o.status != 'cancelled'), 0)) as avail FROM products p WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $realStock = $res['avail'] ?? 0;

    if ($qty > $realStock) {
        echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Tersedia hanya: $realStock unit", 'reset_qty' => $realStock]);
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <link rel="stylesheet" href="/public/css/keranjang.css">
    <style>
        /* CSS TAMBAHAN UNTUK MAP & ALERT BARU */
        .map-wrapper {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            background: #fff;
            margin-bottom: 15px;
        }
        .map-wrapper.fullscreen {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 99999; border-radius: 0; border: none;
        }
        #map { height: 350px; width: 100%; z-index: 1; margin-bottom: 0; }
        .map-wrapper.fullscreen #map { height: 100%; }
        
        /* Tombol di atas peta */
        .btn-map-toggle {
            position: absolute; top: 15px; right: 15px; z-index: 1000;
            background: white; border: none; border-radius: 8px;
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); color: #333;
        }
        .btn-map-toggle:hover { background: #f9f9f9; transform: scale(1.05); }

        /* Styling Input Alamat */
        .address-group { margin-bottom: 15px; }
        .address-group label { font-size: 13px; font-weight: 600; color: #555; display: block; margin-bottom: 5px; }
        .address-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; resize: vertical; box-sizing: border-box; }
        
        /* Input Group Pencarian */
        .search-group { display: flex; gap: 8px; }
        .search-group input { 
            flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; 
        }
        .search-group button {
            background: var(--brand); color: white; border: none; border-radius: 8px; padding: 0 20px; font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .search-group button:hover { background: var(--brand-light); }

        /* Sembunyikan instruksi teks routing */
        .leaflet-routing-container { display: none !important; }

        /* Styling Generic Modal (Alert/Confirm) agar seragam */
        .generic-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6); z-index: 99999;
            display: none; justify-content: center; align-items: center;
            backdrop-filter: blur(3px);
        }
        .generic-box {
            background: white; padding: 30px; border-radius: 20px;
            width: 100%; max-width: 350px; text-align: center;
            margin: 0 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: popUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .generic-icon { font-size: 40px; margin-bottom: 15px; color: #f9d84a; }
        .generic-icon.danger { color: #ef4444; }
        .generic-title { font-size: 20px; font-weight: 800; color: #2c4532; margin-bottom: 10px; }
        .generic-text { font-size: 14px; color: #666; margin-bottom: 25px; line-height: 1.5; }
        .generic-buttons { display: flex; gap: 10px; justify-content: center; }
        .btn-generic {
            padding: 10px 20px; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; flex: 1;
        }
        .btn-primary-modal { background: #2c4532; color: white; }
        .btn-secondary-modal { background: #eee; color: #555; }
        
        @keyframes popUp { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
    <nav class="nav">
      <div class="desktop-nav">
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
          <span></span><span></span><span></span>
        </button>
        <div class="logo"><img src="public/logo.png" width="30px" alt="Logo" /></div>
        <ul class="nav-menu" id="navMenu">
          <li><a href="index.php" class="nav-link">Beranda</a></li>
          <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
          <li><a href="katalog.php" class="nav-link">Katalog</a></li>
          <li><a href="kontak.php" class="nav-link">Kontak</a></li>
        </ul>
      </div>
      <div class="btn-kanan">
        <a href="keranjang.php" class="nav-link" id="cartLink">
          <i class="fas fa-shopping-cart"></i>
          <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
        </a>
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
          <a href="admin/index.php" style="background:#d35400; color:white;"><i class="fas fa-user-shield"></i> Panel</a>
        <?php elseif (isset($_SESSION['user_id'])): ?>
          <a href="riwayat.php" title="Akun Saya"><i class="fas fa-user"></i></a>
        <?php else: ?>
          <button onclick="openLoginModal()"><i class="fas fa-sign-in-alt"></i></button>
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
                    <p style="color:#6b7280;">Belum ada barang yang disewa.</p>
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
                                            <button type="button" class="btn-trash" onclick="confirmRemove('<?= $id ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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

                            <div id="deliverySection" style="display:none; animation: fadeIn 0.3s;">
                                <label class="form-label" style="margin-top: 15px;">
                                    Cari Alamat Pengantaran
                                    <small style="color:#666; font-weight:normal;">(Ketik nama jalan & tekan Enter / Cari)</small>
                                </label>

                                <div class="search-group" style="margin-bottom: 10px;">
                                    <input type="text" id="mapSearchInput" placeholder="Contoh: Jl. Pahlawan No. 5, Samarinda..." onkeypress="handleEnter(event)">
                                    <button type="button" onclick="searchLocation()" class="btn-search-loc">
                                        <i class="fas fa-search-location"></i> Cari
                                    </button>
                                </div>

                                <div id="mapWrapper" class="map-wrapper">
                                    <div id="map"></div>
                                    <button type="button" class="btn-map-toggle" onclick="toggleFullScreen()" title="Layar Penuh">
                                        <i class="fas fa-expand" id="iconResize"></i>
                                    </button>
                                </div>

                                <div class="address-group" style="margin-top: 15px;">
                                    <label>Detail Patokan / Info Tambahan (Wajib Diisi)</label>
                                    <textarea id="addressDetail" class="form-control" rows="2" placeholder="Contoh: Pagar hitam, depan warung bakso, masuk gang..."></textarea>
                                </div>

                                <input type="hidden" name="delivery_address" id="finalAddress">
                                <input type="hidden" name="lat" id="lat">
                                <input type="hidden" name="long" id="long">
                                <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
                                <input type="hidden" name="distance_km" id="distance_km" value="0">

                                <div class="shipping-result" id="shippingInfo" style="display:none; margin-top:15px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span><i class="fas fa-route"></i> Jarak: <strong id="distVal">0</strong> km</span>
                                        <span><i class="fas fa-tag"></i> Ongkir: <strong id="shipVal" style="font-size:16px;">Rp 0</strong></span>
                                    </div>
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

    <div class="confirm-overlay" id="confirmModal" style="display:none;">
        <div class="confirm-box">
            <div class="confirm-icon"><i class="fas fa-clipboard-check"></i></div>
            <h3 class="confirm-title">Periksa Pesanan Anda</h3>
            <p class="confirm-text">Pastikan data nama, nomor HP, dan alamat sudah benar.</p>
            <button id="btnFinalConfirm" class="btn-confirm-final" disabled>Mohon Tunggu (3)</button>
            <br>
            <button class="btn-cancel-popup" onclick="closeConfirmModal()">Batal & Periksa Lagi</button>
        </div>
    </div>

    <div class="generic-overlay" id="genericModal">
        <div class="generic-box">
            <div class="generic-icon" id="genericIcon"><i class="fas fa-exclamation-circle"></i></div>
            <h3 class="generic-title" id="genericTitle">Perhatian</h3>
            <p class="generic-text" id="genericText">Pesan alert disini...</p>
            <div class="generic-buttons" id="genericBtns">
                <button class="btn-generic btn-primary-modal" onclick="closeGenericModal()">OK</button>
            </div>
        </div>
    </div>

    <div id="loginChoiceModal" class="login-modal-overlay">
        <div class="login-modal-content">
            <button class="btn-close-modal" onclick="closeLoginModal()">&times;</button>
            <div class="login-modal-header">
                <h3>Selamat Datang!</h3>
                <p>Silakan pilih cara masuk Anda</p>
            </div>
            <a href="login.php" class="option-user"><i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan</a>
            <div class="modal-divider"><span>ATAU</span></div>
            <a href="admin/login.php" class="option-admin"><i class="fas fa-lock"></i> Masuk sebagai Admin</a>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <script>
        // --- KONFIGURASI GLOBAL ---
        const SHOP_LAT = -0.5454512191833396; 
        const SHOP_LNG = 117.11993488175007;
        const PRICE_PER_15KM = 20000;
        let map, marker, routingControl;
        let currentOngkir = 0;

        // --- BATAS WILAYAH KALIMANTAN TIMUR (Approximate) ---
        // SouthWest: -2.5, 113.5 | NorthEast: 2.5, 119.5
        const KALTIM_BOUNDS = L.latLngBounds(
            L.latLng(-3.0, 113.5), 
            L.latLng(3.0, 120.0)
        );

        // --- SISTEM ALERT & CONFIRM CUSTOM ---
        const genericModal = document.getElementById('genericModal');
        const genericIcon = document.getElementById('genericIcon');
        const genericTitle = document.getElementById('genericTitle');
        const genericText = document.getElementById('genericText');
        const genericBtns = document.getElementById('genericBtns');

        function showCustomAlert(title, message, isError = false) {
            genericIcon.innerHTML = isError ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
            genericIcon.className = isError ? 'generic-icon danger' : 'generic-icon';
            genericTitle.innerText = title;
            genericText.innerText = message;
            genericBtns.innerHTML = `<button class="btn-generic btn-primary-modal" onclick="closeGenericModal()">Tutup</button>`;
            genericModal.style.display = 'flex';
        }

        function showCustomConfirm(title, message, onYes) {
            genericIcon.innerHTML = '<i class="fas fa-question-circle"></i>';
            genericIcon.className = 'generic-icon';
            genericTitle.innerText = title;
            genericText.innerText = message;
            genericBtns.innerHTML = `
                <button class="btn-generic btn-secondary-modal" onclick="closeGenericModal()">Batal</button>
                <button class="btn-generic btn-primary-modal" id="btnConfirmYes">Ya, Lanjutkan</button>
            `;
            genericModal.style.display = 'flex';
            
            document.getElementById('btnConfirmYes').onclick = function() {
                closeGenericModal();
                onYes();
            };
        }

        function closeGenericModal() {
            genericModal.style.display = 'none';
        }

        // --- FUNGSI HAPUS ITEM (Custom Confirm) ---
        function confirmRemove(id) {
            showCustomConfirm(
                'Hapus Item?', 
                'Apakah Anda yakin ingin menghapus barang ini dari keranjang?', 
                function() {
                    window.location.href = '?remove=' + id;
                }
            );
        }

        // --- MAPS LOGIC & ICONS ---
        const greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        function initMap() {
            if(map) return; 
            
            map = L.map('map', {
                center: [SHOP_LAT, SHOP_LNG],
                zoom: 13,
                maxBounds: KALTIM_BOUNDS, // Kunci peta di Kaltim
                maxBoundsViscosity: 1.0,
                minZoom: 7
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

            // Marker Toko (Hijau)
            L.marker([SHOP_LAT, SHOP_LNG], {icon: greenIcon}).addTo(map).bindPopup("<b>Toko Alam Adventure</b>").openPopup();

            // Klik Peta -> Set User Marker (Dengan Validasi Wilayah)
            map.on('click', function(e) {
                if (!KALTIM_BOUNDS.contains(e.latlng)) {
                    showCustomAlert("Di Luar Jangkauan", "Maaf, kami hanya melayani pengantaran di wilayah Kalimantan Timur.", true);
                    return;
                }
                updateUserMarker(e.latlng.lat, e.latlng.lng);
                getAddressFromCoordinates(e.latlng.lat, e.latlng.lng); // Reverse Geocoding
            });
        }

        function toggleFullScreen() {
            const wrap = document.getElementById('mapWrapper');
            const icon = document.getElementById('iconResize');
            wrap.classList.toggle('fullscreen');
            if (wrap.classList.contains('fullscreen')) {
                icon.classList.replace('fa-expand', 'fa-compress');
            } else {
                icon.classList.replace('fa-compress', 'fa-expand');
            }
            setTimeout(() => { map.invalidateSize(); }, 300);
        }

        function handleEnter(e) {
            if(e.key === 'Enter') {
                e.preventDefault(); // Cegah submit form
                searchLocation();
            }
        }

        // Update Pin Merah + Buat Garis Rute (Routing)
        function updateUserMarker(lat, lng) {
            // Hapus routing control lama jika ada
            if (routingControl) {
                map.removeControl(routingControl);
            }
            // Hapus marker user lama jika ada
            if (marker) {
                map.removeLayer(marker);
            }

            // Tambahkan Routing Machine
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(SHOP_LAT, SHOP_LNG), // Toko
                    L.latLng(lat, lng)            // Pembeli
                ],
                routeWhileDragging: false,
                draggableWaypoints: false,
                addWaypoints: false,
                createMarker: function(i, wp, nWps) {
                    if (i === 0) {
                        return L.marker(wp.latLng, {icon: greenIcon}).bindPopup("Toko");
                    } else {
                        return L.marker(wp.latLng, {icon: redIcon}).bindPopup("Lokasi Anda");
                    }
                },
                lineOptions: {
                    styles: [{color: '#007bff', opacity: 0.7, weight: 5}]
                },
                show: false // Sembunyikan panel teks instruksi
            }).addTo(map);

            // Update Input Hidden
            document.getElementById('lat').value = lat;
            document.getElementById('long').value = lng;
            calculateDistance(lat, lng);
        }

        // Reverse Geocoding: Koordinat -> Nama Jalan
        async function getAddressFromCoordinates(lat, lng) {
            const searchInput = document.getElementById('mapSearchInput');
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                if (data && data.display_name) {
                    searchInput.value = data.display_name;
                }
            } catch (error) {
                console.error("Gagal reverse geocoding", error);
            }
        }

        // Forward Geocoding: Nama Jalan -> Koordinat (Restricted to East Kalimantan)
        async function searchLocation() {
            const query = document.getElementById('mapSearchInput').value;
            const btn = document.querySelector('.btn-search-loc');
            const originalText = btn.innerHTML;

            if(!query) {
                showCustomAlert("Info", "Silakan ketik nama jalan atau daerah terlebih dahulu.");
                return;
            }

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            try {
                // Gunakan viewbox untuk memprioritaskan/membatasi pencarian di Kaltim
                // Format viewbox: x1,y1,x2,y2 (Left,Top,Right,Bottom)
                const viewbox = "113.5,3.0,120.0,-3.0";
                
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&viewbox=${viewbox}&bounded=1`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    
                    // Validasi manual apakah hasil pencarian benar di dalam bounds Kaltim
                    if (!KALTIM_BOUNDS.contains([lat, lon])) {
                        showCustomAlert("Lokasi Jauh", "Lokasi ditemukan di luar Kalimantan Timur. Mohon cari alamat lokal.", true);
                        return;
                    }

                    updateUserMarker(lat, lon);
                    map.flyTo([lat, lon], 16);
                } else {
                    showCustomAlert('Tidak Ditemukan', 'Lokasi tidak ditemukan di area Kalimantan Timur. Coba kata kunci lain.', true);
                }
            } catch (error) {
                showCustomAlert('Error', 'Gagal mencari lokasi. Cek koneksi internet.', true);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
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
                        showCustomAlert('Stok Kurang', data.message, true);
                        if(data.reset_qty) inputElem.value = data.reset_qty;
                    }
                });
            });
        });

        // --- SUBMIT CHECKOUT LOGIC ---
        const checkoutForm = document.getElementById('checkoutForm');
        const confirmModal = document.getElementById('confirmModal');
        const btnFinal = document.getElementById('btnFinalConfirm');
        let timerInterval;

        if(checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault(); 
                
                // GABUNGKAN ALAMAT: Pencarian Peta + Patokan
                const method = document.querySelector('input[name="delivery_method"]:checked').value;
                if(method === 'delivery') {
                    const addrMap = document.getElementById('mapSearchInput').value;
                    const addrDetail = document.getElementById('addressDetail').value;
                    
                    if(addrMap.trim() === "") {
                        showCustomAlert("Alamat Belum Dipilih", "Silakan cari lokasi pengantaran di peta.", true);
                        return;
                    }
                    if(addrDetail.trim() === "") {
                        showCustomAlert("Detail Kosong", "Mohon isi detail patokan alamat (pagar, warna rumah, dll) agar kurir tidak tersesat.", true);
                        return;
                    }
                    // Gabung string ke input hidden final
                    document.getElementById('finalAddress').value = addrMap + " [PATOKAN: " + addrDetail + "]";
                }

                openConfirmModal();
            });
        }

        function openConfirmModal() {
            confirmModal.style.display = 'flex';
            let timeLeft = 3;
            btnFinal.disabled = true;
            btnFinal.classList.remove('ready');
            btnFinal.innerHTML = `Mohon Tunggu (${timeLeft})`;

            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                if(timeLeft > 0) {
                    btnFinal.innerHTML = `Mohon Tunggu (${timeLeft})`;
                } else {
                    clearInterval(timerInterval);
                    btnFinal.disabled = false;
                    btnFinal.classList.add('ready');
                    btnFinal.innerHTML = `Ya, Data Sudah Benar <i class="fas fa-check"></i>`;
                }
            }, 1000);
        }

        function closeConfirmModal() {
            confirmModal.style.display = 'none';
            clearInterval(timerInterval);
        }

        if(btnFinal) {
            btnFinal.addEventListener('click', function() {
                if(!this.disabled) {
                    checkoutForm.submit();
                }
            });
        }
    </script>
    <script src="public/js/nav.js"></script>
</body>
</html>