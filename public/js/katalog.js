// Script Toggle Filter Mobile
const filterBtn = document.getElementById('mobileFilterBtn');
const filterContainer = document.getElementById('filterContainer');

if (filterBtn && filterContainer) {
  filterBtn.addEventListener('click', function() {
    // Toggle class 'active' untuk menampilkan/menyembunyikan filter
    filterContainer.classList.toggle('active');
    
    // Opsional: Ganti icon saat dibuka/tutup
    const icon = this.querySelector('i');
    if (filterContainer.classList.contains('active')) {
      icon.classList.remove('fa-sliders');
      icon.classList.add('fa-xmark'); // Jadi tanda silang
    } else {
      icon.classList.remove('fa-xmark');
      icon.classList.add('fa-sliders'); // Jadi slider lagi
    }
  });
}