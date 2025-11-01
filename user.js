document.addEventListener('DOMContentLoaded', function () {

  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';

  // ✅ Cek status checkout (opsional)
  async function checkOrderStatus() {
    try {
      const response = await fetch(`${API_BASE_URL}/check_order.php`, { credentials: 'include' });
      const data = await response.json();
      const form = document.getElementById('reviewForm');
      const notice = document.getElementById('reviewNotice');

      if (!data.success) {
        form && (form.style.display = 'none');
        notice && (notice.style.display = 'block');
      } else {
        form && (form.style.display = 'block');
        notice && (notice.style.display = 'none');
      }
    } catch (error) {
      console.error('Gagal cek status checkout:', error);
    }
  }

  // 🛒 Load jumlah item di keranjang
  function loadCartCountFromDB() {
    fetch(`${API_BASE_URL}/get_cart.php`, { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        const cart = data.cart || [];
        const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
        const cartCountEl = document.getElementById("cartCount");
        if (cartCountEl) {
          cartCountEl.textContent = totalItems;
          cartCountEl.style.display = totalItems > 0 ? 'inline' : 'none';
        }
      })
      .catch(err => console.error('Error loading cart count:', err));
  }

  // 🔑 Simulasi user login (sementara)
  function getLoggedInUserId() {
    return 'USER-TEST-1'; // nanti diganti session
  }

  // 🛍️ Generate tampilan produk
  function generateProductCardHTML(product) {
    return `
      <div class="product-card" data-product-id="${product.id}">
        <div class="product-badge">${product.stock_status === 'in-stock' ? 'Tersedia' : 'Habis'}</div>
        <div class="product-image">
          <img src="${product.image_url || 'https://via.placeholder.com/400x300'}" alt="${product.name}">
        </div>
        <div class="product-info">
          <div class="product-category">${product.category || 'Streaming'}</div>
          <h3 class="product-name">${product.name}</h3>
          <p class="product-desc">${product.description.substring(0, 100)}...</p>
          <div class="product-price">Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</div>
          <button class="btn add-to-cart" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}">🛒 Tambah ke Keranjang</button>
          <button class="btn checkout-now" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}">💳 Beli Sekarang</button>
        </div>
      </div>
    `;
  }

  // 🧩 Ambil produk dari API
  async function fetchAndRenderProducts() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;
    productsGrid.innerHTML = '<p>Memuat produk...</p>';

    try {
      const response = await fetch(`${API_BASE_URL}/products.php`, { credentials: 'include' });
      const products = await response.json();

      productsGrid.innerHTML = '';
      if (!products || products.length === 0) {
        productsGrid.innerHTML = '<p>Belum ada produk.</p>';
        return;
      }

      products.forEach(p => {
        productsGrid.insertAdjacentHTML('beforeend', generateProductCardHTML(p));
      });

      attachAddToCartListeners();
      attachCheckoutListeners();

    } catch (err) {
      console.error('Gagal load produk:', err);
      productsGrid.innerHTML = '<p>Gagal memuat produk.</p>';
    }
  }

  // 🛒 Tambah ke Keranjang
  async function handleAddToCartClick(e) {
    const btn = e.currentTarget;
    const name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);
    const user_id = getLoggedInUserId();

    try {
    const payload = {
  product_id: btn.dataset.id,
  product_name: name,
  price: price,
  qty: 1
};

const response = await fetch(`${API_BASE_URL}/add_to_cart.php`, {
  method: "POST",
  body: JSON.stringify(payload),
  headers: {
    "Content-Type": "application/json"
  },
  credentials: "include"
});

      const data = await response.json();
      if (data.success) {
        alert(`${name} berhasil ditambahkan ke keranjang 🛒`);
        loadCartCountFromDB();
      } else {
        alert('Gagal menambahkan ke keranjang: ' + data.message);
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan saat menambahkan ke keranjang');
    }
  }

  function attachAddToCartListeners() {
    document.querySelectorAll('.add-to-cart').forEach(btn => {
      btn.removeEventListener('click', handleAddToCartClick);
      btn.addEventListener('click', handleAddToCartClick);
    });
  }
   
// 🛒 Handle klik "Beli Sekarang"
async function handleCheckoutNowClick(e) {
  const card = e.target.closest('.product-card');
  const id = card.dataset.productId;
  const name = card.querySelector('.product-name').textContent.trim();
  const price = parseFloat(card.querySelector('.product-price').textContent.replace(/[^0-9]/g, ''));

  try {
    const res = await fetch(`${API_BASE_URL}/buy_now.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: id, product_name: name, price: price, qty: 1 }),
      credentials: 'include'
    });

    const data = await res.json();

    if (data.success) {
      // alert(`${name} siap untuk checkout!`); // Opsional: Hapus jika tidak ingin alert
      window.location.href = 'checkout.html';  // Redirect langsung ke checkout
    } else {
      alert('Gagal memproses beli sekarang: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    console.error('Error buy now:', err);
    alert('Terjadi kesalahan saat beli sekarang.');
  }
}

// Attach listener (sudah ada, tapi pastikan dipanggil)
function attachCheckoutListeners() {
  document.querySelectorAll('.checkout-now').forEach(btn => {
    btn.removeEventListener('click', handleCheckoutNowClick);
    btn.addEventListener('click', handleCheckoutNowClick);
  });
}

  // 💬 Testimoni
  async function fetchAndRenderReviews() {
    const reviewsGrid = document.getElementById('testimonialsGrid');
    if (!reviewsGrid) return;
    reviewsGrid.innerHTML = '<p>Memuat testimoni...</p>';

    try {
      const res = await fetch(`${API_BASE_URL}/reviews.php?status=approved`, { credentials: 'include' });
      const data = await res.json();

      reviewsGrid.innerHTML = '';
      if (!data || data.length === 0) {
        reviewsGrid.innerHTML = '<p>Belum ada testimoni.</p>';
        return;
      }

      data.forEach(r => {
        reviewsGrid.insertAdjacentHTML('beforeend', `
          <div class="testimonial-card">
            <p class="testimonial-text">"${r.comment}"</p>
            <div class="testimonial-author">
              <div class="author-avatar">${r.user_name.charAt(0)}</div>
              <div class="author-info">
                <h4>${r.user_name}</h4>
                <p>Produk: ${r.product_name}</p>
              </div>
            </div>
          </div>
        `);
      });

    } catch (err) {
      console.error('Gagal load testimoni:', err);
      reviewsGrid.innerHTML = '<p>Gagal memuat testimoni.</p>';
    }
  }

  // 🚀 Jalankan semua fungsi utama
  checkOrderStatus();
  fetchAndRenderProducts();
  fetchAndRenderReviews();
  loadCartCountFromDB();

});