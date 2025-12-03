// Initialize AOS
AOS.init({
  duration: 1000,
  once: true,
  offset: 100,
});

// Swiper
const swiper = new Swiper("#productSwiper", {
  // Konfigurasi Default (untuk Mobile Terkecil)
  slidesPerView: 1, // Tampilkan 1 kartu penuh
  spaceBetween: 20, // Jarak antar kartu 20px (jangan minus)
  grabCursor: true, // Kursor berubah jadi tangan saat digeser
  centeredSlides: false, // Rata kiri (matikan jika ingin kartu di tengah)

  // Navigasi Panah
  navigation: {
    nextEl: "#nextArrow",
    prevEl: "#prevArrow",
  },

  // Breakpoints (Responsif di berbagai lebar layar)
  breakpoints: {
    // 1. Mobile Kecil (misal: iPhone SE, Galaxy Fold cover)
    320: {
      slidesPerView: 1, // Tetap 1 kartu agar fokus
      spaceBetween: 20,
      centeredSlides: true, // Opsional: Agar kartu pas di tengah layar kecil
    },

    // 2. Mobile Besar / Tablet Kecil (misal: iPhone Pro Max, layar > 480px)
    480: {
      slidesPerView: 2, // Mulai muat 2 kartu
      spaceBetween: 20,
      centeredSlides: false,
    },

    // 3. Tablet (misal: iPad Mini, layar > 768px)
    768: {
      slidesPerView: 3, // Muat 3 kartu
      spaceBetween: 25, // Jarak sedikit diperlebar
    },

    // 4. Laptop Kecil / iPad Pro (layar > 1024px)
    1024: {
      slidesPerView: 4, // Muat 4 kartu
      spaceBetween: 30,
    },

    // 5. Desktop Besar / Monitor Wide (layar > 1280px)
    1380: {
      slidesPerView: 5, // Muat 5 kartu (opsional, atau tetap 4)
      spaceBetween: 30,
    },
  },
});