document.addEventListener('DOMContentLoaded', function () {

  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';


  // === Memunculkan jawaban di FAQ ===
const faqQuestions = document.querySelectorAll('.faq-question');
faqQuestions.forEach(question => {
    question.addEventListener('click', function() {
        const answer = this.nextElementSibling; // Elemen .faq-answer berikutnya
        if (answer.style.display === 'none' || answer.style.display === '') {
            answer.style.display = 'block';
        } else {
            answer.style.display = 'none';
        }
    });
});

  // === 1. CEK STATUS CHECKOUT & TAMPILKAN FORM REVIEW ===
  async function checkReviewEligibility() {
    const reviewFormContainer = document.getElementById('reviewFormContainer');
    const reviewNotice = document.getElementById('reviewNotice');

    if (!reviewFormContainer && !reviewNotice) return;

    try {
      const response = await fetch(`${API_BASE_URL}/check_review_eligibility.php`, {
        method: 'GET',
        credentials: 'include'
      });
      const data = await response.json();

      if (data.can_review && data.product_id) {
        // Sembunyikan notice, tampilkan form
        if (reviewNotice) reviewNotice.style.display = 'none';
        if (reviewFormContainer) {
          reviewFormContainer.style.display = 'block';
          // Simpan product_id di form
          const form = document.getElementById('reviewForm');
          const nameSpan = document.getElementById('reviewProductName');
          const select = document.getElementById('reviewProductSelect');
          if (form) form.dataset.productId = data.product_id;
          // If API returned a products list and it has multiple items, show select for user choice
          if (Array.isArray(data.products) && data.products.length > 1) {
            select.innerHTML = '';
            data.products.forEach(p => {
              const opt = document.createElement('option');
              opt.value = p.product_id;
              opt.textContent = p.product_name || ('Produk #' + p.product_id);
              // store image URL on option for quick access
              if (p.image) opt.dataset.image = p.image;
              select.appendChild(opt);
            });
            select.style.display = 'block';
            // initialize with first option
            if (form) form.dataset.productId = select.value;
            const firstOpt = select.options[select.selectedIndex];
            if (nameSpan) nameSpan.textContent = firstOpt ? firstOpt.textContent : '';
            const imgEl = document.getElementById('reviewProductImage');
            if (imgEl && firstOpt && firstOpt.dataset.image) { imgEl.src = firstOpt.dataset.image; imgEl.style.display = 'inline-block'; } else if (imgEl) { imgEl.style.display = 'none'; }

            select.onchange = function () {
              const selected = select.options[select.selectedIndex];
              if (form) form.dataset.productId = selected.value;
              if (nameSpan) nameSpan.textContent = selected.textContent;
              if (imgEl) {
                if (selected.dataset.image) { imgEl.src = selected.dataset.image; imgEl.style.display = 'inline-block'; }
                else { imgEl.style.display = 'none'; }
              }
            };
          } else {
                if (select) select.style.display = 'none';
                if (nameSpan) nameSpan.textContent = data.product_name || ('Produk #' + data.product_id);
                const imgEl = document.getElementById('reviewProductImage');
                if (imgEl) {
                  if (data.products && data.products.length === 1 && data.products[0].image) {
                    imgEl.src = data.products[0].image; imgEl.style.display = 'inline-block';
                  } else if (data.product_name && data.products && data.products.length > 0) {
                    // try use first product image if available
                    if (data.products[0] && data.products[0].image) { imgEl.src = data.products[0].image; imgEl.style.display = 'inline-block'; }
                    else { imgEl.style.display = 'none'; }
                  } else { imgEl.style.display = 'none'; }
                }
          }
        }
      } else if (data.can_review && Array.isArray(data.products) && data.products.length > 0) {
        // Jika API mengembalikan daftar produk (pesanan berisi banyak produk)
        if (reviewNotice) reviewNotice.style.display = 'none';
        if (reviewFormContainer) reviewFormContainer.style.display = 'block';

        const form = document.getElementById('reviewForm');
        const select = document.getElementById('reviewProductSelect');
        const nameSpan = document.getElementById('reviewProductName');

        // Bersihkan select
        select.innerHTML = '';
        data.products.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.product_id;
          opt.textContent = p.product_name || ('Produk #' + p.product_id);
          select.appendChild(opt);
        });

        if (data.products.length === 1) {
          // Hanya 1 produk, tampilkan nama dan sembunyikan select
          select.style.display = 'none';
          const p = data.products[0];
          if (form) form.dataset.productId = p.product_id;
          if (nameSpan) nameSpan.textContent = p.product_name || ('Produk #' + p.product_id);
        } else {
          // Banyak produk: tampilkan select untuk memilih
          select.style.display = 'block';
          // set default selection to first
          const first = data.products[0];
          if (form) form.dataset.productId = first.product_id;
          if (nameSpan) nameSpan.textContent = first.product_name || ('Produk #' + first.product_id);

          select.onchange = function () {
            const selected = select.options[select.selectedIndex];
            if (form) form.dataset.productId = selected.value;
            if (nameSpan) nameSpan.textContent = selected.textContent;
          };
        }
      } else {
          if (reviewNotice) {
            reviewNotice.textContent = data.message || 'Tidak dapat menulis review.';
            reviewNotice.style.display = 'block';
          }
        if (reviewFormContainer) reviewFormContainer.style.display = 'none';
      }
    } catch (err) {
      console.error('Gagal cek kelayakan review:', err);
      if (reviewNotice) reviewNotice.style.display = 'block';
      if (reviewFormContainer) reviewFormContainer.style.display = 'none';
    }
  }

  // === 2. SUBMIT REVIEW ===
  const reviewForm = document.getElementById('reviewForm');
  if (reviewForm) {
    // highlight stars when rating changes
    const ratingInputs = reviewForm.querySelectorAll('input[name="rating"]');
    const ratingLabels = Array.from(document.querySelectorAll('#ratingStars label'));
    function updateStars() {
      const checked = Array.from(ratingInputs).find(i => i.checked);
      const val = checked ? parseInt(checked.value) : 0;
      // labels are in left-to-right order 1..5 — color labels with index < val
      ratingLabels.forEach((lbl, idx) => {
        lbl.style.opacity = (idx < val) ? '1' : '0.35';
      });
    }
    ratingInputs.forEach(i => i.addEventListener('change', updateStars));
    // initialize stars state
    updateStars();
    reviewForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const ratingInputs = document.querySelectorAll('input[name="rating"]');
      const rating = Array.from(ratingInputs).find(i => i.checked)?.value;
      const comment = document.getElementById('reviewText').value.trim();
      const product_id = reviewForm.dataset.productId;

      if (!rating) {
        alert('Pilih rating terlebih dahulu!');
        return;
      }
      if (!product_id) {
        alert('Pilih produk yang ingin diulas.');
        return;
      }
      if (!comment) {
        alert('Tulis ulasan terlebih dahulu!');
        return;
      }

      const payload = {
        rating: parseInt(rating, 10),
        comment: comment,
        product_id: parseInt(product_id, 10)
      };

      try {
        const response = await fetch(`${API_BASE_URL}/review.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          credentials: 'include'
        });

        const result = await response.json();

        const successMsg = document.getElementById('reviewSuccess');
        if (result.success) {
          successMsg.style.display = 'block';
          reviewForm.reset();
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          alert(result.message || 'Gagal mengirim ulasan');
        }
      } catch (err) {
        console.error('Error submit review:', err);
        alert('Terjadi kesalahan jaringan');
      }
    });
  }

  // === 3. LOAD JUMLAH KERANJANG ===
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

  // === 4. RENDER PRODUK ===
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
          <button class="btn add-to-cart" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}">Tambah ke Keranjang</button>
          <button class="btn checkout-now" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}">Beli Sekarang</button>
        </div>
      </div>
    `;
  }

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

  // === 5. TAMBAH KE KERANJANG ===
  async function handleAddToCartClick(e) {
    const btn = e.currentTarget;
    const name = btn.dataset.name;
    const price = parseInt(btn.dataset.price);

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
        headers: { "Content-Type": "application/json" },
        credentials: "include"
      });

      const data = await response.json();
      if (data.success) {
        alert(`${name} berhasil ditambahkan ke keranjang`);
        loadCartCountFromDB();
      } else {
        alert('Gagal: ' + data.message);
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan');
    }
  }

  function attachAddToCartListeners() {
    document.querySelectorAll('.add-to-cart').forEach(btn => {
      btn.removeEventListener('click', handleAddToCartClick);
      btn.addEventListener('click', handleAddToCartClick);
    });
  }

  // === 6. BELI SEKARANG ===
function handleCheckoutNowClick(e) {
    if (!window.isLoggedIn) {
        alert('Silakan login terlebih dahulu untuk melakukan pembelian.');
        window.location.href = 'loginuser.html';
        return;
    }

    const card = e.target.closest('.product-card');
    const id = card.dataset.productId;
    const name = card.querySelector('.product-name').textContent.trim();
    const priceText = card.querySelector('.product-price').textContent;
    const price = parseFloat(priceText.replace(/[^0-9]/g, ''));

    const checkoutItems = [{
        product_id: id,
        product_name: name,
        price: price,
        qty: 1
    }];

    sessionStorage.setItem('checkoutItems', JSON.stringify(checkoutItems));
    window.location.href = 'checkout.html';
}

  function attachCheckoutListeners() {
    document.querySelectorAll('.checkout-now').forEach(btn => {
      btn.removeEventListener('click', handleCheckoutNowClick);
      btn.addEventListener('click', handleCheckoutNowClick);
    });
  }

  // === 7. RENDER TESTIMONI ===
  async function fetchAndRenderReviews() {
  const reviewsGrid = document.getElementById('testimonialsGrid');
  if (!reviewsGrid) return;
  reviewsGrid.innerHTML = '<p style="text-align:center;color:#ccc;">Memuat testimoni...</p>';

  try {
    const res = await fetch(`${API_BASE_URL}/reviews.php?status=approved`, { credentials: 'include' });
    const data = await res.json();

    reviewsGrid.innerHTML = '';
    if (!data || data.length === 0) {
      reviewsGrid.innerHTML = '<p style="text-align:center;color:#ccc;">Belum ada testimoni.</p>';
      return;
    }

    // Di dalam fetchAndRenderReviews(), ganti bagian insertAdjacentHTML
data.forEach((r, index) => {
  const stars = '★★★★★'.substring(0, r.rating) + '☆☆☆☆☆'.substring(r.rating);

  const bubble = `
    <div class="review-bubble" data-index="${index}" style="
      background: linear-gradient(135deg, #ffd1dc, #ffe4e1);
      border-radius: 20px;
      padding: 20px;
      margin: 16px auto;
      max-width: 420px;
      box-shadow: 0 6px 16px rgba(255, 105, 180, 0.18);
      border: 1.5px solid #ff99bb;
      position: relative;
      cursor: pointer;
      transition: all 0.3s ease;
      animation: fadeInUp 0.6s ease-out ${index * 0.2}s both;
      font-family: 'Poppins', sans-serif;
      user-select: none;
    ">
      <div style="font-weight: 600; color: #d81b60; font-size: 16px; margin-bottom: 8px;">
        ${r.user_name}
      </div>
      <div style="color: #ffb800; font-size: 19px; margin-bottom: 8px; letter-spacing: 1px;">
        ${stars}
      </div>
      <div style="font-size: 13px; color: #e91e63; margin-bottom: 10px;">
        Produk: ${r.product_name}
      </div>
      <div style="
        font-style: italic;
        color: #c2185b;
        font-size: 15px;
        line-height: 1.6;
        position: relative;
        padding-left: 24px;
      ">
        <span style="position: absolute; left: 0; top: -2px; color: #ff4081; font-size: 22px;">“</span>
        ${r.comment}
        <span style="color: #ff4081; font-size: 22px; margin-left: 4px;">”</span>
      </div>
    </div>
  `;

  reviewsGrid.insertAdjacentHTML('beforeend', bubble);
});

    // === TAMBAH EVENT KLIK ===
    document.querySelectorAll('.review-bubble').forEach(bubble => {
      bubble.addEventListener('click', function () {
        if (this.classList.contains('animating')) return;
        this.classList.add('animating');
        this.style.animation = 'popRotate 1s ease-out forwards';
        setTimeout(() => {
          this.classList.remove('animating');
          this.style.animation = '';
        }, 1000);
      });
    });

  } catch (err) {
    console.error('Gagal load testimoni:', err);
    reviewsGrid.innerHTML = '<p style="text-align:center;color:#ff4081;">Gagal memuat testimoni.</p>';
  }
} // ← AKHIR fetchAndRenderReviews()

// === 8. JALANKAN SEMUA ===
checkReviewEligibility();
fetchAndRenderProducts();
fetchAndRenderReviews();
loadCartCountFromDB();

// Refresh cart count tiap 10 detik
setInterval(loadCartCountFromDB, 10000);

}); 