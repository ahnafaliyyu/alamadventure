# Gunakan image PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install ekstensi PHP yang dibutuhkan untuk koneksi MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Aktifkan mod_rewrite Apache (berguna untuk routing URL jika diperlukan nanti)
RUN a2enmod rewrite

# Set working directory di dalam container
WORKDIR /var/www/html

# Copy semua file proyek ke dalam container
COPY . /var/www/html/

# Ubah permission agar Apache bisa membaca file
RUN chown -R www-data:www-data /var/www/html
