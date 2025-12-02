// Initialize AOS
AOS.init({
  duration: 1000,
  once: true,
  offset: 100,
});

// Initialize Swiper
const swiper = new Swiper("#productSwiper", {
  slidesPerView: "auto",
  spaceBetween: -180,
  grabCursor: true,
  navigation: {
    nextEl: "#nextArrow",
    prevEl: "#prevArrow",
  },
  breakpoints: {
    320: {
      slidesPerView: 1,
      spaceBetween: -180,
    },
    640: {
      slidesPerView: 2,
      spaceBetween: -180,
    },
    1024: {
      slidesPerView: 3,
      spaceBetween: -180,
    },
    1280: {
      slidesPerView: 4,
      spaceBetween: -180,
    },
  },
});
