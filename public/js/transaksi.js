// ===================================
// TRANSAKSI.JS - EIGER STYLE
// Auto Calculation & Payment System
// ===================================

// Global Variables
let currentProduct = {};
let orderQuantity = 1;
let orderDays = 1;
let selectedPayment = 'gopay';
let cartOrders = []; // Store cart orders globally

// Tab Switching
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.querySelectorAll('.tab');
  const panels = document.querySelectorAll('.tab-panel');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const targetTab = this.getAttribute('data-tab');
      
      // Remove active class from all tabs
      tabs.forEach(t => t.classList.remove('active'));
      
      // Hide all panels
      panels.forEach(p => p.style.display = 'none');
      
      // Add active class to clicked tab
      this.classList.add('active');
      
      // Show target panel
      const targetPanel = document.getElementById(`tab-${targetTab}`);
      if (targetPanel) {
        targetPanel.style.display = 'block';
      }
    });
  });
  
  // Set today as minimum date
  const today = new Date().toISOString().split('T')[0];
  const dateInput = document.getElementById('modalTanggalPesan');
  if (dateInput) {
    dateInput.setAttribute('min', today);
    dateInput.value = today;
  }
  
  // Listen to payment method changes
  const paymentInputs = document.querySelectorAll('input[name="payment"]');
  paymentInputs.forEach(input => {
    input.addEventListener('change', function() {
      selectedPayment = this.value;
    });
  });
  
  // Initialize order list (demo)
  loadDemoOrders();
});

// Load Demo Orders
function loadDemoOrders() {
  const ordersList = document.getElementById('ordersList');
  if (!ordersList) return;
  
  // Initialize cart orders if empty
  if (cartOrders.length === 0) {
    cartOrders = [
      {
        id: 1,
        name: 'TENDA DOME 4 ORANG',
        image: '/public/tenda_dome.png',
        price: 35000,
        quantity: 1,
        days: 3,
        startDate: '2025-03-15',
        endDate: '2025-03-18',
        status: 'keranjang',
        total: 105000
      },
      {
        id: 2,
        name: 'SLEEPING BAG',
        image: '/public/sleeping_bag.png',
        price: 15000,
        quantity: 2,
        days: 2,
        startDate: '2025-03-20',
        endDate: '2025-03-22',
        status: 'keranjang',
        total: 60000
      }
    ];
  }
  
  ordersList.innerHTML = cartOrders.map(order => createOrderCard(order)).join('');
}

// Create Order Card HTML
function createOrderCard(order) {
  const statusConfig = {
    keranjang: { label: 'Di Keranjang', class: 'pending', icon: 'bx-cart' },
    resi: { label: 'Menunggu Konfirmasi', class: 'active', icon: 'bx-receipt' },
    selesai: { label: 'Selesai', class: 'completed', icon: 'bx-check-circle' }
  };
  
  const status = statusConfig[order.status] || statusConfig.keranjang;
  
  return `
    <div class="order-card">
      <div class="order-status-badge ${status.class}">
        <i class='bx ${status.icon}'></i>
        <span>${status.label}</span>
      </div>
      <div class="order-card-content">
        <div class="img-wrap">
          <img src="${order.image}" alt="${order.name}" />
        </div>
        <div class="order-details">
          <h3>${order.name}</h3>
          <div class="order-meta">
            <span class="price">Rp ${order.price.toLocaleString('id-ID')} <small>/hari</small></span>
            ${order.status === 'keranjang' ? `
              <div class="cart-quantity-control">
                <button onclick="updateCartQty(${order.id}, 'decrease', 'qty')">
                  <i class='bx bx-minus'></i>
                </button>
                <input 
                  type="number" 
                  id="cart-qty-${order.id}"
                  value="${order.quantity}" 
                  min="1" 
                  max="99"
                  onchange="updateCartInput(${order.id}, this.value, 'qty')"
                />
                <button onclick="updateCartQty(${order.id}, 'increase', 'qty')">
                  <i class='bx bx-plus'></i>
                </button>
                <span class="unit-label">unit</span>
              </div>
              <div class="cart-quantity-control">
                <button onclick="updateCartQty(${order.id}, 'decrease', 'days')">
                  <i class='bx bx-minus'></i>
                </button>
                <input 
                  type="number" 
                  id="cart-days-${order.id}"
                  value="${order.days}" 
                  min="1" 
                  max="365"
                  onchange="updateCartInput(${order.id}, this.value, 'days')"
                />
                <button onclick="updateCartQty(${order.id}, 'increase', 'days')">
                  <i class='bx bx-plus'></i>
                </button>
                <span class="unit-label">hari</span>
              </div>
            ` : `
              <span class="quantity">× ${order.quantity} unit</span>
              <span class="duration">× ${order.days} hari</span>
            `}
          </div>
          <div class="order-dates">
            <div class="date-item">
              <i class='bx bx-calendar'></i>
              <span>Sewa: <strong>${formatDate(order.startDate)}</strong></span>
            </div>
            <div class="date-item">
              <i class='bx bx-calendar-check'></i>
              <span>Kembali: <strong>${formatDate(order.endDate)}</strong></span>
            </div>
          </div>
          <div class="order-total-inline">
            <span>Total Biaya:</span>
            <strong id="cart-total-${order.id}">Rp ${order.total.toLocaleString('id-ID')}</strong>
          </div>
        </div>
      </div>
      <div class="order-actions">
        ${order.status === 'keranjang' ? `
          <button class="btn-secondary" onclick="removeFromCart(${order.id})">
            <i class='bx bx-trash'></i>
            Hapus
          </button>
          <button class="btn-primary" onclick="checkout(${order.id})">
            <i class='bx bx-check-circle'></i>
            Checkout
          </button>
        ` : ''}
        ${order.status === 'resi' ? `
          <button class="btn-secondary" onclick="viewDetail()">
            <i class='bx bx-receipt'></i>
            Lihat Resi
          </button>
        ` : ''}
        ${order.status === 'selesai' ? `
          <button class="btn-secondary" onclick="viewDetail()">
            <i class='bx bx-receipt'></i>
            Lihat Detail
          </button>
          <button class="btn-primary" onclick="reorder(${order.id})">
            <i class='bx bx-refresh'></i>
            Pesan Ulang
          </button>
        ` : ''}
      </div>
    </div>
  `;
}

// Format Date
function formatDate(dateString) {
  const date = new Date(dateString);
  const options = { day: 'numeric', month: 'short', year: 'numeric' };
  return date.toLocaleDateString('id-ID', options);
}

// Open Modal for New Order
function openModal(product) {
  currentProduct = product;
  orderQuantity = 1;
  orderDays = 1;
  
  // Set product info
  document.getElementById('modalImg').src = product.image || '/public/tenda_dome.png';
  document.getElementById('modalName').textContent = product.name || 'Produk';
  document.getElementById('modalHarga').textContent = (product.price || 0).toLocaleString('id-ID');
  
  // Reset inputs
  document.getElementById('modalJumlah').value = 1;
  document.getElementById('modalDurasi').value = 1;
  
  // Set dates
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  document.getElementById('modalTanggalPesan').value = today.toISOString().split('T')[0];
  document.getElementById('modalTanggalKembali').value = tomorrow.toISOString().split('T')[0];
  
  // Calculate total
  calculateTotal();
  
  // Show modal
  document.getElementById('overlay').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

// Close Modal
function closeModal() {
  document.getElementById('overlay').style.display = 'none';
  document.body.style.overflow = 'auto';
}

// Cancel from Modal
function cancelFromModal() {
  closeModal();
}

// Quantity Controls - Both Manual and Button
function increaseQty() {
  orderQuantity++;
  if (orderQuantity > 99) orderQuantity = 99;
  document.getElementById('modalJumlah').value = orderQuantity;
  calculateTotal();
}

function decreaseQty() {
  if (orderQuantity > 1) {
    orderQuantity--;
    document.getElementById('modalJumlah').value = orderQuantity;
    calculateTotal();
  }
}

function increaseDays() {
  orderDays++;
  if (orderDays > 365) orderDays = 365;
  document.getElementById('modalDurasi').value = orderDays;
  updateReturnDate();
  calculateTotal();
}

function decreaseDays() {
  if (orderDays > 1) {
    orderDays--;
    document.getElementById('modalDurasi').value = orderDays;
    updateReturnDate();
    calculateTotal();
  }
}

// Handle Manual Input
function handleManualInput() {
  // Get values from inputs
  const quantityInput = document.getElementById('modalJumlah');
  const daysInput = document.getElementById('modalDurasi');
  
  let quantity = parseInt(quantityInput.value) || 1;
  let days = parseInt(daysInput.value) || 1;
  
  // Validate and limit values
  if (quantity < 1) quantity = 1;
  if (quantity > 99) quantity = 99;
  if (days < 1) days = 1;
  if (days > 365) days = 365;
  
  // Update inputs if needed
  quantityInput.value = quantity;
  daysInput.value = days;
  
  // Update global variables
  orderQuantity = quantity;
  orderDays = days;
  
  // Update return date
  updateReturnDate();
  
  // Recalculate total
  calculateTotal();
}

// Update Return Date
function updateReturnDate() {
  const startDate = new Date(document.getElementById('modalTanggalPesan').value);
  const days = parseInt(document.getElementById('modalDurasi').value) || 1;
  
  const returnDate = new Date(startDate);
  returnDate.setDate(returnDate.getDate() + days);
  
  document.getElementById('modalTanggalKembali').value = returnDate.toISOString().split('T')[0];
}

// Calculate Total
function calculateTotal() {
  const price = currentProduct.price || 0;
  const quantity = parseInt(document.getElementById('modalJumlah').value) || 1;
  const days = parseInt(document.getElementById('modalDurasi').value) || 1;
  
  const total = price * quantity * days;
  
  // Update displays
  document.getElementById('calcHarga').textContent = 'Rp ' + price.toLocaleString('id-ID');
  document.getElementById('modalTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Start Payment
function startPayment() {
  const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
  
  // Show waiting notification
  showNotification('waiting');
  
  // Close modal
  closeModal();
  
  // Simulate payment process
  setTimeout(() => {
    hideNotification('waiting');
    showNotification('success');
    
    // Hide success notification after 3 seconds
    setTimeout(() => {
      hideNotification('success');
    }, 3000);
  }, 2000);
}

// Show Notification
function showNotification(type) {
  const card = type === 'waiting' ? document.getElementById('waitingCard') : document.getElementById('successCard');
  card.setAttribute('aria-hidden', 'false');
}

// Hide Notification
function hideNotification(type) {
  const card = type === 'waiting' ? document.getElementById('waitingCard') : document.getElementById('successCard');
  card.setAttribute('aria-hidden', 'true');
}

// Cancel Order
function removeFromCart(orderId) {
  if (confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')) {
    alert('Item dihapus dari keranjang');
    // Reload orders
    loadDemoOrders();
  }
}

// Checkout
function checkout(orderId) {
  // In real app, this would open checkout modal with order data
  const product = {
    id: orderId,
    name: 'TENDA DOME 4 ORANG',
    image: '/public/tenda_dome.png',
    price: 35000
  };
  openModal(product);
}

// View Detail
function viewDetail() {
  alert('Menampilkan detail pesanan');
}

// Reorder
function reorder(orderId) {
  alert('Memproses pesanan ulang untuk Order ID: ' + orderId);
}

// Reorder Sample (for completed orders)
function reorderSample() {
  alert('Memproses pesanan ulang');
}

// Update Cart Quantity/Days (Button Click)
function updateCartQty(orderId, action, type) {
  // Find the order
  const order = cartOrders.find(o => o.id === orderId);
  if (!order) return;
  
  // Update quantity or days
  if (type === 'qty') {
    if (action === 'increase' && order.quantity < 99) {
      order.quantity++;
    } else if (action === 'decrease' && order.quantity > 1) {
      order.quantity--;
    }
  } else if (type === 'days') {
    if (action === 'increase' && order.days < 365) {
      order.days++;
    } else if (action === 'decrease' && order.days > 1) {
      order.days--;
    }
  }
  
  // Recalculate total
  order.total = order.price * order.quantity * order.days;
  
  // Update return date
  const startDate = new Date(order.startDate);
  const returnDate = new Date(startDate);
  returnDate.setDate(returnDate.getDate() + order.days);
  order.endDate = returnDate.toISOString().split('T')[0];
  
  // Update UI
  updateCartUI(order, type);
}

// Update Cart Input (Manual Input)
function updateCartInput(orderId, value, type) {
  // Find the order
  const order = cartOrders.find(o => o.id === orderId);
  if (!order) return;
  
  let val = parseInt(value) || 1;
  
  if (type === 'qty') {
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    order.quantity = val;
  } else if (type === 'days') {
    if (val < 1) val = 1;
    if (val > 365) val = 365;
    order.days = val;
  }
  
  // Recalculate total
  order.total = order.price * order.quantity * order.days;
  
  // Update return date
  const startDate = new Date(order.startDate);
  const returnDate = new Date(startDate);
  returnDate.setDate(returnDate.getDate() + order.days);
  order.endDate = returnDate.toISOString().split('T')[0];
  
  // Update UI
  updateCartUI(order, type);
}

// Update Cart UI
function updateCartUI(order, type) {
  // Update input values
  const qtyInput = document.getElementById(`cart-qty-${order.id}`);
  const daysInput = document.getElementById(`cart-days-${order.id}`);
  const totalElement = document.getElementById(`cart-total-${order.id}`);
  
  if (qtyInput) qtyInput.value = order.quantity;
  if (daysInput) daysInput.value = order.days;
  if (totalElement) totalElement.textContent = 'Rp ' + order.total.toLocaleString('id-ID');
}

// Export functions for use in HTML
window.openModal = openModal;
window.closeModal = closeModal;
window.cancelFromModal = cancelFromModal;
window.increaseQty = increaseQty;
window.decreaseQty = decreaseQty;
window.increaseDays = increaseDays;
window.decreaseDays = decreaseDays;
window.handleManualInput = handleManualInput;
window.updateReturnDate = updateReturnDate;
window.startPayment = startPayment;
window.removeFromCart = removeFromCart;
window.checkout = checkout;
window.viewDetail = viewDetail;
window.reorder = reorder;
window.reorderSample = reorderSample;
window.updateCartQty = updateCartQty;
window.updateCartInput = updateCartInput;