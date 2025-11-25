AOS.init({
    once: true,
    mirror: false
});

let activeCategory = 'tenda';

function scrollToCategory(category) {
    const productSection = document.getElementById('productSection');
    productSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    setTimeout(() => {
        filterCategory(category);
    }, 800);
}

function filterCategory(category) {
    activeCategory = category;
    const cards = document.querySelectorAll('.product-card');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const categoryTitle = document.getElementById('categoryTitle');
    
    // Update active button
    filterBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.category === category) {
            btn.classList.add('active');
        }
    });
    
    // Update category title
    const titles = {
        'semua': 'SEMUA PRODUK',
        'tenda': 'TENDA',
        'lampu': 'LAMPU',
        'alat-masak': 'ALAT MASAK',
        'paket': 'PAKET',
        'lainnya': 'LAINNYA'
    };
    categoryTitle.textContent = titles[category];
    
    // Filter cards
    cards.forEach(card => {
        if (category === 'semua') {
            card.style.display = 'block';
        } else if (card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function updateQty(btn, change) {
    const input = btn.parentElement.querySelector('.qty-input');
    let value = parseInt(input.value);
    value = Math.max(1, value + change);
    input.value = value;
    updateTotal(input);
}

function updateTotal(element) {
    const card = element.closest('.product-card');
    const qty = parseInt(card.querySelector('.qty-input').value);
    const days = parseInt(card.querySelector('.jumlah-input').value) || 1;
    const totalBtn = card.querySelector('.total-btn');
    const price = parseInt(totalBtn.dataset.price);
    
    const total = qty * days * price;
    totalBtn.textContent = `Total Rp ${total.toLocaleString('id-ID')}`;
}

function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    
    cards.forEach(card => {
        const productName = card.dataset.name.toLowerCase();
        const matchesSearch = productName.includes(searchTerm);
        const matchesCategory = activeCategory === 'semua' || card.dataset.category === activeCategory;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize with tenda category
document.addEventListener('DOMContentLoaded', () => {
    filterCategory('tenda');
});