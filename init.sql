-- 1. TABEL USER (Harus dibuat sebelum orders karena ada Relasi)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABEL ADMIN
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABEL PRODUK
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image_url VARCHAR(255)
);

-- 4. TABEL ORDERS (Semua kolom ALTER sudah digabung disini)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(15, 2) NOT NULL,
    duration_days INT DEFAULT 1,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    
    -- Kolom Pembayaran & Status Sewa
    payment_method ENUM('online', 'cod') DEFAULT 'online',
    rental_status ENUM('pending_pickup', 'ongoing', 'returned') DEFAULT 'pending_pickup',
    actual_return_date DATETIME NULL,
    fine_amount DECIMAL(15, 2) DEFAULT 0,
    snap_token VARCHAR(255),
    
    -- Kolom Pengiriman
    delivery_method ENUM('pickup', 'delivery') DEFAULT 'pickup',
    delivery_address TEXT NULL,
    delivery_lat VARCHAR(50) NULL,
    delivery_long VARCHAR(50) NULL,
    shipping_cost DECIMAL(15, 2) DEFAULT 0,
    
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key ke Users
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. TABEL ORDER ITEMS
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 6. TABEL INVOICES (Semua kolom ALTER sudah digabung)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL,
    order_code VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'midtrans',
    customer_name VARCHAR(100),
    order_type VARCHAR(50) DEFAULT 'Peminjaman Barang',
    duration VARCHAR(50),
    total_qty INT DEFAULT 0,
    signature_admin VARCHAR(50) DEFAULT 'Otomatis',
    signature_customer VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_code) REFERENCES orders(order_code) ON DELETE CASCADE
);

-- 7. TABEL SETTINGS
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255)
);

-- SEEDING DATA (DATA AWAL)

-- Data Admin Default (Password: admin123)
-- Harap ganti hash ini dengan hash password yang baru jika ingin mengubah password
INSERT INTO admins (username, password) VALUES 
('admin', 'password_hash_generated_here..')
ON DUPLICATE KEY UPDATE username=username;

-- Data Settings Default
INSERT INTO settings (setting_key, setting_value, description) VALUES
('shop_name', 'Alam Adventure', 'Nama Toko'),
('shop_phone', '6282241559607', 'Nomor WA Admin (Format 62...)'),
('shop_address', 'Jl. Contoh No. 123, Samarinda, Kalimantan Timur', 'Alamat Lengkap Toko'),
('shop_maps', 'https://www.google.com/maps?q=-0.502183,117.153801', 'Link Google Maps Toko'),
('rental_fine', '50000', 'Denda Keterlambatan per Hari'),
('shipping_rate', '20000', 'Biaya Ongkir Dasar'),
('landing_title', 'Sewa Alat Camping<br>Terpercaya di Samarinda', 'Judul Hero'),
('landing_desc', 'Perlengkapan camping lengkap dan terawat.', 'Deskripsi Hero'),
('landing_bg_image', 'public/main-background.jpg', 'Hero Background'),
('stats_title', 'Kenapa Memilih Alam Adventure?', 'Judul Stats'),
('stats_desc', 'Layanan terbaik untuk petualangan Anda.', 'Deskripsi Stats'),
('stat_1_num', '330+', 'Angka Stat 1'),
('stat_1_label', 'Pelanggan Puas', 'Label Stat 1'),
('stat_2_num', '4 Tahun', 'Angka Stat 2'),
('stat_2_label', 'Pengalaman', 'Label Stat 2'),
('stat_3_num', '50+', 'Angka Stat 3'),
('stat_3_label', 'Produk Tersedia', 'Label Stat 3')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

ALTER TABLE orders ADD COLUMN actual_pickup_date DATETIME NULL AFTER rental_status;