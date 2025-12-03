CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image_url VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(15, 2) NOT NULL,
    duration_days INT DEFAULT 1,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    snap_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- kolom untuk mendukung COD dan Manajemen Pengembalian
ALTER TABLE orders
ADD COLUMN payment_method ENUM('online', 'cod') DEFAULT 'online',
ADD COLUMN rental_status ENUM('pending_pickup', 'ongoing', 'returned') DEFAULT 'pending_pickup',
ADD COLUMN actual_return_date DATETIME NULL,
ADD COLUMN fine_amount DECIMAL(15, 2) DEFAULT 0;

-- buat ongkirnyah
ALTER TABLE orders
ADD COLUMN delivery_method ENUM('pickup', 'delivery') DEFAULT 'pickup',
ADD COLUMN delivery_address TEXT NULL,
ADD COLUMN delivery_lat VARCHAR(50) NULL,
ADD COLUMN delivery_long VARCHAR(50) NULL,
ADD COLUMN shipping_cost DECIMAL(15, 2) DEFAULT 0;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL,
    order_code VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'midtrans',
    signature_admin VARCHAR(50) DEFAULT 'Otomatis',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_code) REFERENCES orders(order_code) ON DELETE CASCADE
);

ALTER TABLE invoices 
ADD COLUMN customer_name VARCHAR(100),
ADD COLUMN order_type VARCHAR(50) DEFAULT 'Peminjaman Barang',
ADD COLUMN duration VARCHAR(50),
ADD COLUMN total_qty INT DEFAULT 0,
ADD COLUMN signature_customer VARCHAR(100);

-- Sistem baru dengan User

-- 1. Buat tabel user untuk pembeli
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1. Tambahkan kolom expires_at (Perbaikan: tidak menggunakan AFTER order_date)
ALTER TABLE orders ADD COLUMN expires_at DATETIME NULL;

-- 2. Pastikan kolom user_id juga sudah ada (jika belum)
ALTER TABLE orders ADD COLUMN user_id INT NULL;

-- 3. (Opsional) Hubungkan foreign key jika tabel users sudah dibuat
-- Pastikan tabel 'users' sudah dibuat sebelumnya
ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE users 
ADD COLUMN is_verified TINYINT(1) DEFAULT 0, 
ADD COLUMN verification_token VARCHAR(255) NULL,
ADD COLUMN reset_token VARCHAR(255) NULL,
ADD COLUMN reset_expires DATETIME NULL;