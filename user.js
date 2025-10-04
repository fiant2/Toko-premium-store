// user.js - MENGGUNAKAN API PHP XAMPP
document.addEventListener('DOMContentLoaded', function() {
    
    // GANTI KE ALAMAT API PHP ANDA
const API_BASE_URL = 'http://localhost/api_store/api_storeapi';
    
    // --- Helper Function: SIMULASI Get User ID ---
    // ID 'USER-TEST-1' dianggap sudah memiliki pembelian di reviews.php!
    function getLoggedInUserId() {
        return 'USER-TEST-1'; 
    }
    
    // --- 1. Load Produk yang Diterbitkan Admin (GET dari PHP) ---
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
                    <button class="add-to-cart" data-id="${product.id}">Beli Sekarang</button>
                </div>
            </div>
        `;
    }

    function fetchAndRenderProducts() {
        const productsGrid = document.getElementById('productsGrid');
        if (!productsGrid) return;
        
        productsGrid.innerHTML = '<p>Memuat produk yang disetujui Admin...</p>';
        
        // GANTI: Menggunakan fetch GET dari products.php
        fetch(`${API_BASE_URL}/products.php`)
        .then(response => {
            if (!response.ok) throw new Error('API Produk Gagal dimuat.');
            return response.json();
        })
        .then(products => {
            productsGrid.innerHTML = '';
            if (products.length === 0) {
                productsGrid.innerHTML = '<p>Maaf, belum ada produk yang tersedia saat ini.</p>';
                return;
            }
            
            products.forEach(product => {
                productsGrid.insertAdjacentHTML('beforeend', generateProductCardHTML(product));
            });
            attachAddToCartListeners();
        })
        .catch(error => {
            console.error('Error memuat produk:', error);
            productsGrid.innerHTML = '<p class="text-danger">Produk gagal dimuat. Pastikan XAMPP Apache dan MySQL berjalan.</p>';
        });
    }

    // --- 2. Load Ulasan yang Disetujui Admin (GET dari PHP) ---
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
        
        reviewsGrid.innerHTML = '<p>Memuat ulasan yang disetujui Admin...</p>';
        
        // GANTI: Menggunakan fetch GET status=approved dari reviews.php
        fetch(`${API_BASE_URL}/reviews.php?status=approved`)
        .then(response => response.json())
        .then(approvedReviews => {
            reviewsGrid.innerHTML = '';
            if (approvedReviews.length === 0) {
                reviewsGrid.innerHTML = '<p>Belum ada testimoni yang disetujui Admin.</p>';
                return;
            }
            
            approvedReviews.forEach(review => {
                reviewsGrid.insertAdjacentHTML('beforeend', generateReviewCardHTML(review));
            });
        })
        .catch(error => {
            console.error('Error memuat ulasan:', error);
            reviewsGrid.innerHTML = '<p class="text-danger">Testimoni gagal dimuat.</p>';
        });
    }

    // --- 3. Handle Pengiriman Ulasan (POST ke PHP) ---
    const reviewForm = document.getElementById('reviewForm'); 

    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = getLoggedInUserId(); 
            const productId = reviewForm.getAttribute('data-product-id') || 'P1'; // Ganti P1 dengan ID produk yang ingin diulas
            const comment = document.getElementById('reviewText').value;
            const userName = document.getElementById('reviewName').value || 'Pelanggan ' + userId;

            const reviewData = {
                userId: userId,
                productId: productId,
                comment: comment,
                userName: userName
            };

            // GANTI: Menggunakan fetch POST ke reviews.php
            fetch(`${API_BASE_URL}/reviews.php`, { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(reviewData)
            })
            .then(response => response.json())
            .then(data => {
                alert(`[Server Respon] ${data.message}`); 
                if (data.status === 'success') {
                    reviewForm.reset();
                }
                fetchAndRenderReviews();
            })
            .catch(error => console.error('Error saat POST ulasan:', error));
        });
    }

    // --- Fungsionalitas Lama (Sama) ---
    function attachAddToCartListeners() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.removeEventListener('click', handleAddToCart); 
            button.addEventListener('click', handleAddToCart);
        });
    }

    function handleAddToCart() {
        const productName = this.closest('.product-card').querySelector('.product-name').textContent;
        alert(`🛒 Produk ${productName} telah ditambahkan ke keranjang!`);
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Panggil fungsi utama saat DOM siap
    fetchAndRenderProducts();
    fetchAndRenderReviews();
});