let currentFilters = {
  category: "semua",
  search: "",
  sort: "terbaru",
  page: 1,
};
let searchTimeout = null;

document.addEventListener("DOMContentLoaded", function () {
  // --- 1. FILTER KATEGORI (Sync Desktop & Mobile) ---
  const allFilterButtons = document.querySelectorAll(".filter-btn, .cat-pill");

  allFilterButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      allFilterButtons.forEach((b) => b.classList.remove("active"));
      const val = this.getAttribute("data-val");
      document
        .querySelectorAll(
          `.filter-btn[data-val="${val}"], .cat-pill[data-val="${val}"]`
        )
        .forEach((b) => {
          b.classList.add("active");
        });
      currentFilters.category = val;
      currentFilters.page = 1;
      loadProducts();
    });
  });

  // --- 2. SEARCH (Sync Desktop & Mobile) ---
  const desktopSearch = document.getElementById("searchInput");
  const mobileSearch = document.getElementById("mobileSearchInput");

  function handleSearch(e) {
    clearTimeout(searchTimeout);
    const val = e.target.value;
    if (desktopSearch && desktopSearch !== e.target) desktopSearch.value = val;
    if (mobileSearch && mobileSearch !== e.target) mobileSearch.value = val;

    searchTimeout = setTimeout(() => {
      currentFilters.search = val;
      currentFilters.page = 1;
      loadProducts();
    }, 500);
  }

  if (desktopSearch) desktopSearch.addEventListener("input", handleSearch);
  if (mobileSearch) mobileSearch.addEventListener("input", handleSearch);

  // Attach Listener Awal
  attachCartButtonListeners();
});

// --- FUNGSI LOAD & RENDER ---
function loadProducts() {
  const grid = document.getElementById("productGrid");
  if (grid) grid.style.opacity = "0.5";

  const params = new URLSearchParams({
    ajax_load_products: "1",
    category: currentFilters.category,
    q: currentFilters.search,
    sort: currentFilters.sort,
    page: currentFilters.page,
  });

  fetch(`katalog.php?${params.toString()}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        renderProducts(data.products);
        renderPagination(data.pagination);
        if (typeof updateCartCount === "function")
          updateCartCount(data.cartCount || 0);
      }
    })
    .catch((err) => console.error(err))
    .finally(() => {
      if (grid) grid.style.opacity = "1";
    });
}

function renderProducts(products) {
  const grid = document.getElementById("productGrid");
  grid.innerHTML = "";

  if (products.length === 0) {
    grid.innerHTML =
      '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#666;">Produk tidak ditemukan.</div>';
    return;
  }

  products.forEach((p) => {
    const price = new Intl.NumberFormat("id-ID").format(p.price_per_day);
    const isAvailable = p.available_stock > 0;

    const card = document.createElement("div");
    card.className = `product-card ${!isAvailable ? "out-of-stock" : ""}`;

    let stockBadge = isAvailable
      ? `<div style="position:absolute; top:10px; left:10px; background:#e8f5e9; color:#2e7d32; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">Stok: ${p.available_stock}</div>`
      : `<div style="position:absolute; top:10px; left:10px; background:#ffebee; color:#c62828; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:2;">Habis / Disewa</div>`;

    let btnHtml = isAvailable
      ? `<button class="cart-btn add-to-cart-btn" data-product-id="${p.id}"><i class="fa-solid fa-cart-plus"></i></button>`
      : `<button class="cart-btn" disabled style="background:#ccc; cursor:not-allowed;"><i class="fa-solid fa-ban"></i></button>`;

    card.innerHTML = `
            ${stockBadge}
            <div class="product-image"><img src="${
              p.image_url || "/public/logo.png"
            }" alt="${p.name}"></div>
            <div class="description">
                <h2>${p.name}</h2>
                <p>${
                  p.description
                    ? p.description.substring(0, 60) + "..."
                    : "Deskripsi belum tersedia"
                }</p>
            </div>
            <div class="product-actions"><span>Rp ${price}</span>${btnHtml}</div>
        `;
    grid.appendChild(card);
  });

  attachCartButtonListeners();
}

function renderPagination(meta) {
  const container = document.getElementById("paginationContainer");
  container.innerHTML = "";
  if (meta.total_pages <= 1) return;

  for (let i = 1; i <= meta.total_pages; i++) {
    const btn = document.createElement("a");
    btn.href = "#";
    btn.className = `pagination-link ${
      i === meta.current_page ? "active" : ""
    }`;
    btn.innerText = i;
    btn.onclick = (e) => {
      e.preventDefault();
      currentFilters.page = i;
      loadProducts();
      window.scrollTo({ top: 0, behavior: "smooth" });
    };
    container.appendChild(btn);
  }
}

// --- LOGIKA UTAMA TOMBOL KERANJANG (DIPERBAIKI) ---
function attachCartButtonListeners() {
  document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
    // Clone untuk reset event listener
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    newBtn.addEventListener("click", function (e) {
      e.preventDefault();
      const productId = this.getAttribute("data-product-id");
      const buttonIcon = this.querySelector("i");
      const originalIconClass = "fa-solid fa-cart-plus";

      // 1. Loading State (ICON BERPUTAR)
      this.disabled = true;
      buttonIcon.className = "fas fa-spinner fa-spin";

      const formData = new FormData();
      formData.append("ajax_add_to_cart", "1");
      formData.append("product_id", productId);

      fetch("katalog.php", { method: "POST", body: formData })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            // Update Badge Navbar
            if (typeof updateCartCount === "function")
              updateCartCount(data.cartCount);

            // 2. SUKSES: Stop Putaran SEGERA & Ganti ke Icon Asli
            // CSS .added-success akan menimpa icon ini dengan Centang (Check) secara visual
            buttonIcon.className = originalIconClass;

            // Trigger Animasi Pop & Warna Hijau
            this.classList.add("added-success");

            // Hapus class sukses setelah animasi selesai (500ms)
            setTimeout(() => {
              this.classList.remove("added-success");
            }, 500);
          } else {
            showToast(data.message, true);
            // Jika gagal, kembalikan icon segera
            buttonIcon.className = originalIconClass;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("Terjadi kesalahan", true);
          buttonIcon.className = originalIconClass;
        })
        .finally(() => {
          this.disabled = false;
        });
    });
  });
}

function updateCartCount(count) {
  const el = document.getElementById("cartCount");
  const link = document.getElementById("cartLink");
  if (el) el.innerText = count;

  // Animasi Keranjang Navbar
  if (link) {
    link.classList.remove("nav-cart-animate");
    void link.offsetWidth; // Force Reflow
    link.classList.add("nav-cart-animate");
  }
}
