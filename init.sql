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