// Inisialisasi AOS
AOS.init({
    once: true,
    mirror: false
});

// --- LOGIKA CAROUSEL (DARI LANGKAH SEBELUMNYA) ---
let carouselIndex = 0;

function getScrollAmount() {
    const wrapper = document.querySelector('.carousel-wrapper');
    const track = document.getElementById('carouselTrack');
    const card = track.querySelector('.product-card');
    
    if (!card || !track) return 0;
    const cardWidth = card.offsetWidth;
    const trackStyle = window.getComputedStyle(track);
    const gap = parseInt(trackStyle.gap) || 20;
    return cardWidth + gap;
}

function nextSlide() {
    const wrapper = document.querySelector('.carousel-wrapper');
    const cards = document.querySelectorAll('.product-card');
    const totalCards = cards.length;
    carouselIndex++;
    if (carouselIndex >= totalCards) {
        carouselIndex = 0;
        wrapper.scrollTo({ left: 0, behavior: 'smooth' });
    } else {
        const scrollAmount = getScrollAmount();
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

function prevSlide() {
    const wrapper = document.querySelector('.carousel-wrapper');
    const cards = document.querySelectorAll('.product-card');
    const totalCards = cards.length;
    carouselIndex--;
    if (carouselIndex < 0) {
        carouselIndex = totalCards - 1;
        const scrollAmount = getScrollAmount() * totalCards;
        wrapper.scrollTo({ left: scrollAmount, behavior: 'smooth' });
    } else {
        const scrollAmount = getScrollAmount();
        wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    }
}
// --- AKHIR LOGIKA CAROUSEL ---


// --- FUNGSI MENU MOBILE (GLOBAL) ---
function toggleMenu() {
    const mobileNav = document.getElementById('mobileNav');
    mobileNav.classList.toggle('active');
}


// --- LOGIKA NAVIGASI AKTIF (DIPERBARUI) ---

// Dapatkan nama file halaman saat ini (mis: "katalog.html" atau "index.html")
const currentPage = window.location.pathname.split("/").pop();
const navLinks = document.querySelectorAll('.nav-link');
const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

if (currentPage === '' || currentPage === 'index.html') {
    
    // --- A. LOGIKA SCROLL-SPY (HANYA UNTUK INDEX.HTML) ---
    
    const sections = document.querySelectorAll('section, .main-content');
    
    function setActiveNav() {
        let current = 'home'; // Default ke 'home'
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.pageYOffset >= sectionTop - 200) {
                current = section.getAttribute('id') || current;
            }
        });

        // Desktop nav
        navLinks.forEach(link => {
            link.classList.remove('active');
            const linkHref = link.getAttribute('href');
            if ((linkHref === 'index.html' || linkHref === '/') && current === 'home') {
                link.classList.add('active');
            } else if (linkHref.includes('#' + current)) {
                link.classList.add('active');
            }
        });

        // Mobile nav
        mobileNavLinks.forEach(link => {
            link.classList.remove('active');
            const linkHref = link.getAttribute('href');
            if ((linkHref === 'index.html' || linkHref === '/') && current === 'home') {
                link.classList.add('active');
            } else if (linkHref.includes('#' + current)) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', setActiveNav);
    window.addEventListener('load', setActiveNav);
    document.addEventListener('DOMContentLoaded', () => {
        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.id = 'home';
    });

} else {
    
    // --- B. LOGIKA HALAMAN AKTIF (UNTUK SEMUA HALAMAN LAIN) ---
    
    function setActivePage() {
        // Desktop nav
        navLinks.forEach(link => {
            // Cek jika href link SAMA DENGAN nama file halaman
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Mobile nav
        mobileNavLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    // Jalankan saat halaman dimuat
    window.addEventListener('load', setActivePage);
}