document.addEventListener('DOMContentLoaded', function () {

  // ========== CEK STATUS CHECKOUT ==========
  async function checkOrderStatus() {
    try {
      const response = await fetch('check_order.php');
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
  checkOrderStatus();

  // ========== KONFIGURASI API ==========
  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';

  function getLoggedInUserId() {
    return 'USER-TEST-1';  // Ini bisa dihapus jika tidak digunakan, atau ganti dengan session check
  }

  // ========== LOAD CART COUNT DARI DATABASE ==========
  function loadCartCountFromDB() {
    fetch(`${API_BASE_URL}/get_cart.php`)
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

  // ========== LOAD PRODUK DARI API ==========
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
        </div>
      </div>
    `;
  }

  function fetchAndRenderProducts() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;

    productsGrid.innerHTML = '<p>Memuat produk dari admin...</p>';

    fetch(`${API_BASE_URL}/products.php`)
      .then(response => response.json())
      .then(products => {
        productsGrid.innerHTML = '';
        if (!products || products.length === 0) {
          productsGrid.innerHTML = '<p>Belum ada produk.</p>';
          return;
        }

        products.forEach(p => {
          productsGrid.insertAdjacentHTML('beforeend', generateProductCardHTML(p));
        });

        // Pasang event listener setelah produk dimuat
        attachAddToCartListeners();
        attachCheckoutListeners();
      })
      .catch(err => {
        console.error('Gagal load produk:', err);
        productsGrid.innerHTML = '<p class="text-danger">Gagal memuat produk.</p>';
      });
  }

  // ========== HANDLE TAMBAH KE KERANJANG (VIA API) ==========
  function handleAddToCartClick(e) {
    const btn = e.currentTarget;
    const product_id = btn.dataset.id;  // Pastikan ini ada dari generateProductCardHTML
    const product_name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);

    if (!product_id || !product_name || isNaN(price)) {
      alert('Produk tidak valid.');
      return;
    }

    // Panggil API add_to_cart.php
    fetch(`${API_BASE_URL}/add_to_cart.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ product_id, product_name, price, qty: 1 }),
      credentials: 'include' 
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(`${product_name} berhasil ditambahkan ke keranjang 🛒`);
        loadCartCountFromDB();  // Update count setelah tambah
      } else {
        alert('Gagal menambah ke keranjang: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      console.error('Error adding to cart:', err);
      alert('Terjadi kesalahan saat menambah ke keranjang.');
    });
  }

  function attachAddToCartListeners() {
    document.querySelectorAll('.add-to-cart, .add-to-cart-standalone').forEach(btn => {
      btn.removeEventListener('click', handleAddToCartClick);
      btn.addEventListener('click', handleAddToCartClick);
    });
  }

  // ========== HANDLE BELI SEKARANG (VIA API) ==========
  function handleCheckoutNowClick(e) {
    const btn = e.currentTarget;
    const product_id = btn.dataset.id;
    const product_name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);

    if (!product_id || !product_name || isNaN(price)) {
      alert('Produk tidak valid.');
      return;
    }

    // Tambah ke cart via API dulu
    fetch(`${API_BASE_URL}/add_to_cart.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ product_id, product_name, price, qty: 1 })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.href = 'cart.html';  // Redirect setelah berhasil
      } else {
        alert('Gagal menambah produk untuk checkout.');
      }
    })
    .catch(err => {
      console.error('Error checkout now:', err);
      alert('Terjadi kesalahan saat checkout.');
    });
  }

  function attachCheckoutListeners() {
    document.querySelectorAll('.checkout-now').forEach(btn => {
      btn.removeEventListener('click', handleCheckoutNowClick);
      btn.addEventListener('click', handleCheckoutNowClick);
    });
  }

  // ========== LOAD TESTIMONI ==========
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

  function fetchAndRenderReviews() {
    const reviewsGrid = document.getElementById('testimonialsGrid');
    if (!reviewsGrid) return;

    reviewsGrid.innerHTML = '<p>Memuat testimoni...</p>';

    fetch(`${API_BASE_URL}/reviews.php?status=approved`)
      .then(res => res.json())
      .then(data => {
        reviewsGrid.innerHTML = '';
        if (!data || data.length === 0) {
          reviewsGrid.innerHTML = '<p>Belum ada testimoni.</p>';
          return;
        }
        data.forEach(r => reviewsGrid.insertAdjacentHTML('beforeend', generateReviewCardHTML(r)));
      })
      .catch(err => {
        console.error('Gagal load testimoni:', err);
        reviewsGrid.innerHTML = '<p>Gagal memuat testimoni.</p>';
      });
  }

  // ========== JALANKAN SAAT HALAMAN DILOAD ==========
  fetchAndRenderProducts();
  fetchAndRenderReviews();
  loadCartCountFromDB();  // Load count cart di awal

});
