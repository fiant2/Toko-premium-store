document.addEventListener('DOMContentLoaded', function () {

  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';

  // ✅ Cek status checkout (opsional)
  async function checkOrderStatus() {
    try {
      const response = await fetch('check_order.php', { credentials: 'include' });
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

  // 🛍️ Load Produk
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

    fetch(`${API_BASE_URL}/products.php`, { credentials: 'include' })
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


        attachAddToCartListeners();
        
      })
      .catch(err => {
        console.error('Gagal load produk:', err);
        productsGrid.innerHTML = '<p class="text-danger">Gagal memuat produk.</p>';
      });
  }

  // 🧩 Tambah ke Keranjang
  function handleAddToCartClick(e) {
    const btn = e.currentTarget;
    const product_id = btn.dataset.id;
    const product_name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);

    // Cek session dulu
    fetch(`${API_BASE_URL}/check_session.php`, { credentials: 'include' })
      .then(res => res.json())
      .then(session => {
        if (!session.logged_in) {
          alert('Silakan login terlebih dahulu untuk menambah produk ke keranjang.');
          window.location.href = 'loginuser.html';
          return;
        }

        // Jika sudah login, tambahkan ke keranjang
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
            loadCartCountFromDB();
          } else {
            alert('Gagal menambah ke keranjang: ' + (data.error || 'Unknown error'));
          }
        });
      });
  }

  function attachAddToCartListeners() {
    document.querySelectorAll('.add-to-cart').forEach(btn => {
      btn.removeEventListener('click', handleAddToCartClick);
      btn.addEventListener('click', handleAddToCartClick);
    });
  }

  // 💬 Load Testimoni
  function fetchAndRenderReviews() {
    const reviewsGrid = document.getElementById('testimonialsGrid');
    if (!reviewsGrid) return;

    reviewsGrid.innerHTML = '<p>Memuat testimoni...</p>';

    fetch(`${API_BASE_URL}/reviews.php?status=approved`, { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
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
      })
      .catch(err => {
        console.error('Gagal load testimoni:', err);
        reviewsGrid.innerHTML = '<p>Gagal memuat testimoni.</p>';
      });
  }

  // 🚀 Jalankan
  fetchAndRenderProducts();
  fetchAndRenderReviews();
  loadCartCountFromDB();
});
