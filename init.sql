CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Opsional: Masukkan data dummy awal
INSERT INTO products (name, description, price_per_day, stock, image_url) VALUES 
('Tenda Dome 4P', 'Tenda kapasitas 4 orang anti bocor', 25000, 10, 'public/tenda.png'),
('Kompor Portable', 'Kompor camping praktis', 15000, 15, 'public/komporportable.png');
