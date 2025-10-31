document.addEventListener('DOMContentLoaded', async function () {
  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';

  // CEK ORDER STATUS
  async function checkOrderStatus() {
    try {
      const res = await fetch(`${API_BASE_URL}/check_order.php`, { credentials: 'include' });
      const data = await res.json();
      const form = document.getElementById('reviewForm');
      if (!data.success) form.style.display = 'none';
      else form.style.display = 'block';
    } catch (e) {
      console.error('Gagal cek order:', e);
    }
  }

  checkOrderStatus();

  // AMBIL PRODUK DARI DATABASE
  async function fetchProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.innerHTML = '<p>Memuat produk...</p>';

    try {
      const res = await fetch(`${API_BASE_URL}/products.php`, { credentials: 'include' });
      const data = await res.json();
      grid.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        grid.innerHTML = '<p>Tidak ada produk tersedia.</p>';
        return;
      }

      data.forEach(p => {
        grid.insertAdjacentHTML('beforeend', `
          <div class="product-card">
            <img src="${p.image_url || 'img/default.jpg'}" alt="${p.name}">
            <h3>${p.name}</h3>
            <p>Rp ${new Intl.NumberFormat('id-ID').format(p.price)}</p>
            <div style="display:flex;gap:10px;">
              <button class="btn add-to-cart" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">🛒 Tambah</button>
              <button class="btn checkout-now" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">💳 Beli Sekarang</button>
            </div>
          </div>
        `);
      });

      document.querySelectorAll('.add-to-cart').forEach(b => b.addEventListener('click', addToCart));
      document.querySelectorAll('.checkout-now').forEach(b => b.addEventListener('click', checkoutNow));
    } catch (err) {
      console.error('Error load produk:', err);
      grid.innerHTML = '<p>Gagal memuat produk.</p>';
    }
  }

  fetchProducts();

  async function addToCart(e) {
    const b = e.currentTarget;
    const fd = new FormData();
    fd.append('id', b.dataset.id);
    fd.append('qty', 1);

    const res = await fetch(`${API_BASE_URL}/cart_api.php?action=add`, {
      method: 'POST',
      body: fd,
      credentials: 'include'
    });
    const data = await res.json();
    if (data.success) {
      alert(`${b.dataset.name} berhasil ditambahkan ke keranjang!`);
    } else {
      alert('Gagal menambahkan ke keranjang.');
    }
  }

  async function checkoutNow(e) {
    const b = e.currentTarget;
    const fd = new FormData();
    fd.append('id', b.dataset.id);
    fd.append('qty', 1);

    const res = await fetch(`${API_BASE_URL}/checkout_now.php`, {
      method: 'POST',
      body: fd,
      credentials: 'include'
    });
    const data = await res.json();
    if (data.success) {
      alert('Pesanan berhasil dibuat!');
      window.location.href = 'checkout.html';
    } else {
      alert('Gagal membuat pesanan.');
    }
  }

  function getLoggedInUserId() {
    return 'USER-TEST-1'; // ganti dengan session login nanti
  }

  // ================= LOAD PRODUK =================
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

  async function fetchAndRenderProducts() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;

    productsGrid.innerHTML = '<p>Memuat produk dari admin...</p>';

    try {
      const response = await fetch(`${API_BASE_URL}/products.php`);
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
      productsGrid.innerHTML = '<p class="text-danger">Gagal memuat produk.</p>';
    }
  }

  // ================= HANDLE TAMBAH KE KERANJANG =================
  async function handleAddToCartClick(e) {
    const btn = e.currentTarget;
    const name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);
    const user_id = getLoggedInUserId();

    if (!name || isNaN(price)) {
      alert('Produk tidak valid.');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('user_id', user_id);
      formData.append('name', name);
      formData.append('price', price);
      formData.append('qty', 1);

      const response = await fetch('cart_api.php?action=add', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        alert(`${name} berhasil ditambahkan ke keranjang 🛒`);
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

  // ================= HANDLE BELI SEKARANG =================
  async function handleCheckoutNowClick(e) {
    const btn = e.currentTarget;
    const name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);
    const user_id = getLoggedInUserId();

    if (!name || isNaN(price)) {
      alert('Produk tidak valid.');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('user_id', user_id);
      formData.append('name', name);
      formData.append('price', price);
      formData.append('qty', 1);

      const response = await fetch('cart_api.php?action=add', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        window.location.href = 'cart.html';
      } else {
        alert('Gagal checkout sekarang: ' + data.message);
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan saat checkout sekarang');
    }
  }

  function attachCheckoutListeners() {
    document.querySelectorAll('.checkout-now').forEach(btn => {
      btn.removeEventListener('click', handleCheckoutNowClick);
      btn.addEventListener('click', handleCheckoutNowClick);
    });
  }

  // ================= LOAD TESTIMONI =================
  function generateReviewCardHTML(review) {
    return `
      <div class="testimonial-card">
        <p class="testimonial-text">"${review.comment}"</p>
        <div class="testimonial-author">
          <div class="author-avatar">${review.user_name.charAt(0)}</div>
          <div class="author-info">
            <h4>${review.user_name}</h4>
            <p>Produk: ${review.productName}</p>
          </div>
        </div>
      </div>
    `;
  }

  async function fetchAndRenderReviews() {
    const reviewsGrid = document.getElementById('testimonialsGrid');
    if (!reviewsGrid) return;

    reviewsGrid.innerHTML = '<p>Memuat testimoni...</p>';

    try {
      const res = await fetch(`${API_BASE_URL}/reviews.php?status=approved`);
      const data = await res.json();
      reviewsGrid.innerHTML = '';
      if (!data || data.length === 0) {
        reviewsGrid.innerHTML = '<p>Belum ada testimoni.</p>';
        return;
      }
      data.forEach(r => reviewsGrid.insertAdjacentHTML('beforeend', generateReviewCardHTML(r)));
    } catch (err) {
      console.error('Gagal load testimoni:', err);
      reviewsGrid.innerHTML = '<p>Gagal memuat testimoni.</p>';
    }
  }

  // ================= JALANKAN SAAT HALAMAN DILOAD =================
  fetchAndRenderProducts();
  fetchAndRenderReviews();


});
