
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
