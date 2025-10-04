// admin.js - MENGGUNAKAN API PHP laragon
document.addEventListener('DOMContentLoaded', function() {
    
    // GANTI KE ALAMAT API PHP ANDA
  const API_BASE_URL = 'http://localhost/api_store/api_storeapi';
    
    // --- Modal Functionality (Sama) ---
    const addProductBtn = document.getElementById('addProductBtn');
    const addProductModal = document.getElementById('addProductModal');
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    
    addProductBtn.addEventListener('click', function() {
        addProductModal.style.display = 'flex';
    });
    
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            addProductModal.style.display = 'none';
        });
    });
    
    addProductModal.addEventListener('click', function(e) {
        if (e.target === addProductModal) {
            addProductModal.style.display = 'none';
        }
    });

    // --- 1. HANDLE SIMPAN PRODUK (POST ke PHP) ---
    const saveProductFinalBtn = document.getElementById('saveProductFinalBtn');
    
    if (saveProductFinalBtn) {
        saveProductFinalBtn.addEventListener('click', function(e) {
            e.preventDefault(); 
            
            // 1. Ambil data input
            const productName = document.getElementById('productNameInput').value;
            const productCategory = document.getElementById('productCategoryInput').value;
            const productPrice = document.getElementById('productPriceInput').value;
            const productDesc = document.getElementById('productDescInput').value;
            const productFeatures = document.getElementById('productFeaturesInput').value;
            const productStock = document.getElementById('productStockInput').value;
            const productStatus = document.getElementById('productStatusInput').value;
            const productImage = document.getElementById('productImageInput').value;

            if (!productName || !productPrice || !productStatus) {
                alert("Nama, Harga, dan Status harus diisi!");
                return;
            }

            const editId = addProductModal.getAttribute('data-edit-id');
            let fetchUrl = `${API_BASE_URL}/admin_products.php`;
            let fetchMethod = 'POST';
            
            if (editId) {
                fetchUrl += `?id=${editId}`;
                fetchMethod = 'PUT';
            }

            const productData = {
                name: productName,
                category: productCategory,
                price: parseFloat(productPrice),
                description: productDesc,
                features: productFeatures, // Kirim sebagai string, PHP yang simpan
                stockStatus: productStock,
                status: productStatus,
                imageUrl: productImage || 'https://via.placeholder.com/400x300?text=' + productName.replace(/ /g, '+')
            };

            // GANTI: Menggunakan fetch POST ke admin_products.php
            fetch(`${API_BASE_URL}/admin_products.php`, { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(productData)
            })
            .then(response => {
                if (!response.ok) throw new Error('API server error.');
                return response.json();
            })
            .then(data => {
                alert(`✅ Produk "${productName}" berhasil disimpan! Status: ${productStatus}. ID: ${data.id}`);
                addProductModal.style.display = 'none';
                loadAdminProducts(); 
            })
            .catch(error => {
                console.error('Error saat POST produk:', error);
                alert('❌ Terjadi kesalahan: Gagal terhubung ke API atau data invalid.');
            });
        });
    }

    // --- 2. LOAD PRODUK UNTUK ADMIN (GET dari PHP) ---
    function createAdminProductCard(product) {
        const stockBadge = product.stock_status === 'in-stock' ? 'in-stock' : (product.stock_status === 'low-stock' ? 'low-stock' : 'out-of-stock');
        const stockText = product.stock_status === 'in-stock' ? 'Tersedia' : (product.stock_status === 'low-stock' ? 'Terbatas' : 'Habis');
        const statusBadge = product.status === 'published' ? 'bg-green' : 'bg-orange';
        
        return `
            <div class="product-management-card" data-product-id="${product.id}">
                <div class="product-image">
                    <img src="${product.image_url || 'https://via.placeholder.com/400x300'}" alt="${product.name}">
                </div>
                <div class="product-info">
                    <div class="product-category">${product.category || 'Belum Ada'}</div>
                    <h3 class="product-name">${product.name}</h3>
                    <div class="product-price">Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</div>
                    <div class="product-stock">
                        <span>Status Admin:</span>
                        <span class="stock-badge ${statusBadge}">${product.status}</span>
                    </div>
                    <div class="product-stock">
                        <span>Stok:</span>
                        <span class="stock-badge ${stockBadge}">${stockText}</span>
                    </div>
                    <div class="product-actions">
                        <button class="action-btn btn-edit" data-id="${product.id}">Edit</button>
                        <button class="action-btn btn-delete" data-id="${product.id}">Hapus</button>
                    </div>
                </div>
            </div>
        `;
    }

    function loadAdminProducts() {
        const grid = document.getElementById('adminProductsGrid');
        if (!grid) return;
        grid.innerHTML = '<p>Memuat produk dari server...</p>';

        // GANTI: Menggunakan fetch GET ke admin_products.php
        fetch(`${API_BASE_URL}/admin_products.php`)
        .then(response => response.json())
        .then(products => {
            grid.innerHTML = '';
            if (products.length === 0) {
                grid.innerHTML = '<p>Belum ada produk yang ditambahkan oleh Admin.</p>';
                return;
            }

            products.forEach(product => {
                grid.insertAdjacentHTML('beforeend', createAdminProductCard(product));
            });
            attachProductActionListeners();
        })
        .catch(error => {
            console.error('Error fetching admin products:', error);
            grid.innerHTML = '<p class="text-danger">Gagal memuat produk. Pastikan XAMPP Apache dan MySQL berjalan.</p>';
        });
    }
    
    // --- 3. HAPUS PRODUK (DELETE ke PHP) ---
    function attachProductActionListeners() {
        const deleteButtons = document.querySelectorAll('.product-management-card .btn-delete');
        
        document.querySelectorAll('.product-management-card .btn-edit').forEach(button => {
    button.removeEventListener('click', handleEditProduct);
    button.addEventListener('click', handleEditProduct);
});

function handleEditProduct() {
    const productId = this.getAttribute('data-id');
    fetch(`${API_BASE_URL}/admin_products.php`)
        .then(response => response.json())
        .then(products => {
            const product = products.find(p => p.id == productId);
            if (!product) return alert('Produk tidak ditemukan!');
            document.getElementById('productNameInput').value = product.name || '';
            document.getElementById('productCategoryInput').value = product.category || '';
            document.getElementById('productPriceInput').value = product.price || '';
            document.getElementById('productDescInput').value = product.description || '';
            document.getElementById('productFeaturesInput').value = product.features || '';
            document.getElementById('productStockInput').value = product.stock_status || '';
            document.getElementById('productStatusInput').value = product.status || '';
            document.getElementById('productImageInput').value = product.image_url || '';
            addProductModal.setAttribute('data-edit-id', productId);
            addProductModal.style.display = 'flex';
        });
}

        deleteButtons.forEach(button => {
            button.removeEventListener('click', handleDeleteProduct);
            button.addEventListener('click', handleDeleteProduct);
        });

        function handleDeleteProduct() {
            const productId = this.getAttribute('data-id');
            const productName = this.closest('.product-management-card').querySelector('.product-name').textContent;
            
            if (confirm(`❓ Apakah Anda yakin ingin menghapus ${productName} (ID: ${productId})?`)) {
                // GANTI: Menggunakan fetch DELETE ke admin_products.php?id=...
                fetch(`${API_BASE_URL}/admin_products.php?id=${productId}`, {
                    method: 'DELETE'
                })
                .then(response => {
                    if (response.ok) {
                        alert(`🗑️ Produk ${productName} telah dihapus!`);
                        loadAdminProducts();
                    } else {
                        alert('❌ Gagal menghapus produk. Cek API Server.');
                    }
                })
                .catch(error => console.error('Error DELETE:', error));
            }
        }
    }

    // --- 4. KELOLA ULASAN PENDING (GET & PUT ke PHP) ---
    function loadPendingReviews() {
        const container = document.getElementById('pendingReviewsContainer');
        if (!container) return;
        container.innerHTML = '<p>Memuat ulasan yang menunggu persetujuan...</p>';

        // GANTI: Menggunakan fetch GET status=pending ke reviews.php
        fetch(`${API_BASE_URL}/reviews.php?status=pending`)
        .then(response => response.json())
        .then(pendingReviews => {
            container.innerHTML = '';
            if (pendingReviews.length === 0) {
                container.innerHTML = '<p>🎉 Tidak ada ulasan yang menunggu persetujuan!</p>';
                return;
            }
            
            pendingReviews.forEach(review => {
                const reviewHTML = `
                    <div class="review-item" data-id="${review.id}">
                        <p><strong>Produk:</strong> ${review.productName || 'ID Produk: ' + review.product_id}</p>
                        <p><strong>Pengguna:</strong> ${review.user_name}</p>
                        <p><strong>Ulasan:</strong> "${review.comment}"</p>
                        <div class="review-actions">
                            <button class="action-btn btn-approve bg-green" data-id="${review.id}">Setujui</button>
                            <button class="action-btn btn-reject bg-red" data-id="${review.id}">Tolak</button>
                        </div>
                    </div>
                    <hr/>
                `;
                container.insertAdjacentHTML('beforeend', reviewHTML);
            });
            attachReviewActionListeners();
        })
        .catch(error => {
            console.error('Error fetching reviews:', error);
            container.innerHTML = '<p class="text-danger">Gagal memuat ulasan. Cek API PHP.</p>';
        });
    }

    function attachReviewActionListeners() {
        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', () => updateReviewStatus(btn.getAttribute('data-id'), 'approved'));
        });
        document.querySelectorAll('.btn-reject').forEach(btn => {
            btn.addEventListener('click', () => updateReviewStatus(btn.getAttribute('data-id'), 'rejected'));
        });
    }

    function updateReviewStatus(reviewId, status) {
        if (!confirm(`Yakin ingin ${status === 'approved' ? 'menyetujui' : 'menolak'} ulasan ini?`)) return;

        // GANTI: Menggunakan fetch PUT ke reviews.php/{id}/status
        fetch(`${API_BASE_URL}/reviews.php/${reviewId}/status`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ status: status })
        })
        .then(response => {
            if (response.ok) {
                alert(`Status ulasan ID ${reviewId} diubah menjadi ${status}.`);
                loadPendingReviews(); 
            } else {
                alert('Gagal mengubah status ulasan.');
            }
        })
        .catch(error => console.error('Error update review status:', error));
    }

    // --- Panggil Fungsi Utama ---
    loadAdminProducts(); 
    loadPendingReviews(); 
});