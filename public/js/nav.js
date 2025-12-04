// Script Khusus Modal Login
const loginModal = document.getElementById("loginChoiceModal");

function openLoginModal() {
  loginModal.style.display = "flex";
}

function closeLoginModal() {
  loginModal.style.display = "none";
}

// Tutup jika klik di luar area konten
window.onclick = function (event) {
  if (event.target == loginModal) {
    closeLoginModal();
  }
};

document.addEventListener("DOMContentLoaded", () => {
  const hamburger = document.getElementById("hamburger");
  const navMenu = document.getElementById("navMenu");

  // 1. Toggle Menu saat tombol hamburger diklik
  if (hamburger && navMenu) {
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation(); // Mencegah event bubbling
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });
  }

  // 2. Tutup menu saat salah satu link diklik
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
    });
  });

  // 3. Tutup menu jika klik di luar area menu (Outside Click)
  document.addEventListener("click", function (event) {
    const isClickInsideNav = navMenu.contains(event.target);
    const isClickOnHamburger = hamburger.contains(event.target);

    // Jika menu sedang terbuka DAN klik bukan di menu/tombol
    if (
      navMenu.classList.contains("active") &&
      !isClickInsideNav &&
      !isClickOnHamburger
    ) {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
    }
  });
});
