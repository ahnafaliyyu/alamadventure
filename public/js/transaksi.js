/***********************
     * DATA & INITIAL STATE
     ***********************/
    const products = [
      {
        id: 'p1',
        name: 'LAMPU TENDA',
        pricePerDay: 5000,
        image: '/public/lampu_tenda.jpg',
        qty: 4,
        durasi: 3
      },
      {
        id: 'p2',
        name: 'LAMPU LENTERA',
        pricePerDay: 10000,
        image: 'public/lentera.jpeg',
        qty: 6,
        durasi: 3
      },
      {
        id: 'p3',
        name: 'PAKET HEALING SINGLE',
        pricePerDay: 50000,
        image: '/public/paket_healing.jpg',
        qty: 6,
        durasi: 3
      }
    ];

    // orders array to simulate placed orders (simple in-memory)
    const orders = [];

    /***********************
     * RENDER FUNCTIONS
     ***********************/
    function money(num){
      return num.toLocaleString('id-ID');
    }

    function calcTotal(item){
      return item.pricePerDay * item.qty * item.durasi;
    }

    function buildOrderCard(item){
      // create card DOM
      const card = document.createElement('div');
      card.className = 'order-card';
      card.id = 'card-' + item.id;

      card.innerHTML = `
        <div class="img-wrap"><img src="${item.image}" alt="${escapeHtml(item.name)}"></div>
        <div class="order-main">
          <h3>${escapeHtml(item.name)}</h3>
          <div class="meta"><div>Rp. ${money(item.pricePerDay)}/DAY</div></div>

          <div class="controls">
            <div class="qty-box">
              <button title="Kurangi" onclick="changeDurasi('${item.id}', -1)">−</button>
              <div style="min-width:8px"></div>
              <div style="font-weight:600"> <span id="durasi-${item.id}">${item.durasi}</span> Hari </div>
              <button title="Tambah" onclick="changeDurasi('${item.id}', 1)">+</button>

              <div style="flex:1"></div>

              <div style="min-width:120px;text-align:center">
                <div style="font-size:13px;color:var(--muted)">Jumlah</div>
                <div style="font-weight:800" id="qty-${item.id}">${item.qty}</div>
              </div>
            </div>
          </div>

          <div style="margin-top:8px;color:var(--muted);font-size:14px">
            Harga: Rp. ${money(item.pricePerDay)}
          </div>

          <div style="margin-top:10px;color:var(--muted);font-size:13px" id="dates-${item.id}">
            Tanggal Pemesanan: <span style="font-weight:700">13-02-2025</span><br>
            Tanggal Pengembalian: <span style="font-weight:700">16-02-2025</span>
          </div>
        </div>

        <div class="order-side">
          <div style="text-align:right">
            <div style="color:var(--muted);font-size:14px">Total Pesanan:</div>
            <div class="price-big">Rp. <span id="total-${item.id}">${money(calcTotal(item))}</span></div>
          </div>
          <div>
            <button class="btn-yellow" onclick="openModal('${item.id}')">Pesan</button>
          </div>
        </div>
      `;

      return card;
    }

    function renderList(){
      const list = document.getElementById('ordersList');
      list.innerHTML = '';
      for(const p of products){
        list.appendChild(buildOrderCard(p));
      }
    }

    // escape helper for names
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.innerText = text;
      return div.innerHTML;
    }

    /***********************
     * INTERACTIONS
     ***********************/
    function changeDurasi(id, delta){
      const prod = products.find(p=>p.id===id);
      if(!prod) return;
      prod.durasi = Math.max(1, prod.durasi + delta);
      document.getElementById('durasi-' + id).innerText = prod.durasi;
      document.getElementById('total-' + id).innerText = money(calcTotal(prod));
    }

    function openModal(id){
      const prod = products.find(p=>p.id===id);
      if(!prod) return;
      // fill modal
      document.getElementById('modalImg').src = prod.image;
      document.getElementById('modalName').innerText = prod.name;
      document.getElementById('modalHarga').innerText = money(prod.pricePerDay);
      document.getElementById('modalDurasi').innerText = prod.durasi + ' Hari';
      document.getElementById('modalJumlah').innerText = prod.qty;
      // fixed dates like in screenshot
      document.getElementById('modalTanggalPesan').innerText = '13-02-2025';
      document.getElementById('modalTanggalKembali').innerText = '16-02-2025';
      document.getElementById('modalTotal').innerText = 'Rp. ' + money(calcTotal(prod));

      // show modal
      document.getElementById('overlay').classList.add('show');
      currentModalProduct = prod;
    }

    function closeModal(){
      document.getElementById('overlay').classList.remove('show');
    }

    function cancelFromModal(){
      if(confirm('Apakah Anda ingin membatalkan pemesanan ini?')){
        closeModal();
      }
    }

    let currentModalProduct = null;
    let paymentTimer = null;

    function startPayment(){
      // close modal
      closeModal();

      // show waiting small card
      const wait = document.getElementById('waitingCard');
      wait.classList.add('show');
      document.getElementById('waitBar').style.width = '0%';

      // fill orders array with a simple entry
      if(currentModalProduct){
        const o = {
          id: 'order_' + Date.now(),
          productId: currentModalProduct.id,
          name: currentModalProduct.name,
          qty: currentModalProduct.qty,
          durasi: currentModalProduct.durasi,
          total: calcTotal(currentModalProduct),
          status: 'waiting'
        };
        orders.push(o);
      }

      // animate progress bar and then show success
      let progress = 0;
      clearInterval(paymentTimer);
      paymentTimer = setInterval(()=>{
        progress += Math.random()*12 + 6;
        if(progress >= 100){
          progress = 100;
          document.getElementById('waitBar').style.width = '100%';
          clearInterval(paymentTimer);
          setTimeout(()=>showSuccess(), 500);
        } else {
          document.getElementById('waitBar').style.width = progress + '%';
        }
      }, 220);
    }

    function showSuccess(){
      // hide waiting
      const wait = document.getElementById('waitingCard');
      wait.classList.remove('show');

      // show success
      const s = document.getElementById('successCard');
      s.classList.add('show');

      // after a short delay, hide success and move order to "Daftar Resi" (simulation)
      setTimeout(()=>{
        s.classList.remove('show');
        // mark last order as paid
        if(orders.length) orders[orders.length-1].status = 'paid';
        // optionally move to daftar resi or selesai; here we'll keep in memory
        alert('Pembayaran berhasil untuk pesanan: ' + (orders.length ? orders[orders.length-1].name : ''));
      }, 1400);
    }

    function reorderSample(){
      alert('Fungsi pesan ulang dipanggil (contoh).');
    }

    /***********************
     * Tabs
     ***********************/
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(t=>{
      t.addEventListener('click', ()=> {
        document.querySelector('.tab.active').classList.remove('active');
        t.classList.add('active');
        const which = t.getAttribute('data-tab');
        document.getElementById('tab-all').style.display = (which==='all' ? 'block' : 'none');
        document.getElementById('tab-resi').style.display = (which==='resi' ? 'block' : 'none');
        document.getElementById('tab-selesai').style.display = (which==='selesai' ? 'block' : 'none');
      });
    });

    /***********************
     * INIT
     ***********************/
    document.addEventListener('DOMContentLoaded', ()=>{
      renderList();
    });

    // small accessibility / escape handler for modal
    document.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){
        document.getElementById('overlay').classList.remove('show');
        document.getElementById('waitingCard').classList.remove('show');
        document.getElementById('successCard').classList.remove('show');
      }
    });