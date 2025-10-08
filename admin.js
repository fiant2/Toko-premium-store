// admin.js - MENGGUNAKAN API PHP laragon
document.addEventListener('DOMContentLoaded', function() {
    
    //  ALAMAT API PHP
  const API_BASE_URL = 'http://localhost/Semester%203/Toko%20premium%20store/api_store/api_storeapi';
    
    // --- Modal Functionality ---
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

            // Menggunakan fetch POST ke admin_products.php
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

    // ---------------- Customers & Sales functionality (CRUD lengkap) ----------------

  // helper escape
  function escapeHtml(str) { if (!str) return ''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  // sections map
const sectionsMap = {
  dashboard: document.querySelector('.stats-grid'),
  orders: document.getElementById('orders-section'), // tambahkan ini
  products: document.getElementById('products-list'),
  reviews: document.getElementById('reviews-pending'),
  customers: document.getElementById('customers-section'),
  sales: document.getElementById('sales-section')
};

function showSection(name) {
  Object.values(sectionsMap).forEach(sec => {
    if (sec) sec.style.display = 'none';
  });

  if (name === 'dashboard') {
    sectionsMap.dashboard.style.display = 'grid';
    sectionsMap.orders.style.display = 'block'; // tampilkan pesanan terbaru hanya di dashboard
  } else if (sectionsMap[name]) {
    sectionsMap[name].style.display = 'block';
  }

  if (name === 'products') loadAdminProducts();
  if (name === 'reviews') loadPendingReviews();
  if (name === 'customers') loadCustomers();
  if (name === 'sales') loadSales();
}



// Sidebar link handling (dengan highlight aktif)
const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

sidebarLinks.forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();

    // hapus semua highlight dulu
    sidebarLinks.forEach(l => l.classList.remove('active'));

    // tambahkan highlight ke menu yang diklik
    this.classList.add('active');

    // tampilkan section sesuai menu
    const txt = this.textContent.trim().toLowerCase();
    if (txt.includes('pelanggan')) showSection('customers');
    else if (txt.includes('penjualan')) showSection('sales');
    else if (txt.includes('produk')) showSection('products');
    else if (txt.includes('ulasan')) showSection('reviews');
    else showSection('dashboard');
  });
});


  // Customers
  const customersTableBody = document.querySelector('#customersTable tbody');
  const customerModal = document.getElementById('customerModal');
  const customerForm = document.getElementById('customerForm');

  document.getElementById('addCustomerBtn').addEventListener('click', () => {
    document.getElementById('customerModalTitle').textContent = 'Tambah Pelanggan';
    document.getElementById('customerId').value = '';
    document.getElementById('customerName').value = '';
    document.getElementById('customerEmail').value = '';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerStatus').value = 'active';
    customerModal.style.display = 'flex';
  });

  document.getElementById('customerModalClose').addEventListener('click', () => customerModal.style.display = 'none');
  document.getElementById('cancelCustomerBtn').addEventListener('click', (e) => { e.preventDefault(); customerModal.style.display = 'none'; });

  document.getElementById('saveCustomerBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const id = document.getElementById('customerId').value;
    const payload = {
      name: document.getElementById('customerName').value.trim(),
      email: document.getElementById('customerEmail').value.trim(),
      phone: document.getElementById('customerPhone').value.trim(),
      status: document.getElementById('customerStatus').value
    };
    if (!payload.name || !payload.email) return alert('Nama & Email harus diisi.');

    const url = id ? `${API_BASE_URL}/admin_customers.php?id=${id}` : `${API_BASE_URL}/admin_customers.php`;
    const method = id ? 'PUT' : 'POST';

    fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success' || res.id) {
          alert('Sukses menyimpan data pelanggan.');
          customerModal.style.display = 'none';
          loadCustomers();
        } else alert('Error: ' + (res.message || JSON.stringify(res)));
      }).catch(err => { console.error(err); alert('Terjadi kesalahan saat menyimpan.'); });
  });

  function loadCustomers() {
    if (!customersTableBody) return;
    customersTableBody.innerHTML = `<tr><td colspan="7">Memuat pelanggan...</td></tr>`;
    fetch(`${API_BASE_URL}/admin_customers.php`)
      .then(r => r.json())
      .then(list => {
        if (!Array.isArray(list) || list.length === 0) {
          customersTableBody.innerHTML = `<tr><td colspan="7">Belum ada pelanggan.</td></tr>`; return;
        }
        customersTableBody.innerHTML = '';
        list.forEach(c => {
          customersTableBody.insertAdjacentHTML('beforeend', `
            <tr data-id="${c.id}">
              <td>${c.id}</td>
              <td>${escapeHtml(c.name)}</td>
              <td>${escapeHtml(c.email)}</td>
              <td>${escapeHtml(c.phone || '-')}</td>
              <td>${c.created_at}</td>
              <td><span class="status ${c.status === 'active' ? 'status-completed' : 'status-pending'}">${c.status}</span></td>
              <td>
                <button class="action-btn btn-view-customer" data-id="${c.id}">Lihat</button>
                <button class="action-btn btn-edit-customer" data-id="${c.id}">Edit</button>
                <button class="action-btn btn-delete-customer" data-id="${c.id}">Hapus</button>
              </td>
            </tr>`);
        });
        attachCustomerListeners();
      }).catch(err => { console.error(err); customersTableBody.innerHTML = `<tr><td colspan="7">Gagal memuat pelanggan.</td></tr>`; });
  }

  function attachCustomerListeners() {
    document.querySelectorAll('.btn-view-customer').forEach(b => { b.removeEventListener('click', handleViewCustomer); b.addEventListener('click', handleViewCustomer); });
    document.querySelectorAll('.btn-edit-customer').forEach(b => { b.removeEventListener('click', handleEditCustomer); b.addEventListener('click', handleEditCustomer); });
    document.querySelectorAll('.btn-delete-customer').forEach(b => { b.removeEventListener('click', handleDeleteCustomer); b.addEventListener('click', handleDeleteCustomer); });
  }

  function handleViewCustomer() {
    const id = this.getAttribute('data-id');
    fetch(`${API_BASE_URL}/admin_customers.php?id=${id}`)
      .then(r => r.json())
      .then(c => {
        alert(`ID: ${c.id}\nNama: ${c.name}\nEmail: ${c.email}\nTelepon: ${c.phone}\nStatus: ${c.status}\nTerdaftar: ${c.created_at}`);
      });
  }

  function handleEditCustomer() {
    const id = this.getAttribute('data-id');
    fetch(`${API_BASE_URL}/admin_customers.php?id=${id}`)
      .then(r => r.json())
      .then(c => {
        document.getElementById('customerModalTitle').textContent = 'Edit Pelanggan';
        document.getElementById('customerId').value = c.id;
        document.getElementById('customerName').value = c.name || '';
        document.getElementById('customerEmail').value = c.email || '';
        document.getElementById('customerPhone').value = c.phone || '';
        document.getElementById('customerStatus').value = c.status || 'active';
        customerModal.style.display = 'flex';
      });
  }

  function handleDeleteCustomer() {
    const id = this.getAttribute('data-id');
    if (!confirm('Hapus pelanggan ini?')) return;
    fetch(`${API_BASE_URL}/admin_customers.php?id=${id}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') { alert('Dihapus'); loadCustomers(); } else alert('Gagal: ' + (res.message || JSON.stringify(res)));
      }).catch(err => console.error(err));
  }

  // Event klik pada kartu "Total Pelanggan" agar buka section pelanggan
document.getElementById('statTotalCustomers')?.addEventListener('click', function() {
  // Highlight menu sidebar "Pelanggan"
  document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
  const pelangganMenu = Array.from(document.querySelectorAll('.sidebar-menu a')).find(a => a.textContent.toLowerCase().includes('pelanggan'));
  if (pelangganMenu) pelangganMenu.classList.add('active');
  // Tampilkan section pelanggan
  showSection('customers');
});

  // Sales
  const salesTableBody = document.querySelector('#salesTable tbody');
  const saleModal = document.getElementById('saleModal');

  document.getElementById('addSaleBtn').addEventListener('click', () => {
    // Form sederhana via prompt (bisa diganti modal form lebih lengkap)
    const customerId = prompt('Masukkan ID pelanggan (kosong = tanpa pelanggan):');
    const orderNumber = prompt('Masukkan No. Order (kosong = auto):') || '';
    const total = prompt('Total (angka):', '0');
    let productsInput = prompt('Produk JSON (contoh: [{"product_id":"P1","name":"Spotify","price":25000,"qty":1}])', '[]');
    try { productsInput = JSON.parse(productsInput); } catch(e) { alert('JSON produk tidak valid'); return; }

    const payload = { order_number: orderNumber || ('ORD-' + Date.now()), customer_id: customerId ? parseInt(customerId): null, products: productsInput, total: parseFloat(total), status: 'pending' };
    fetch(`${API_BASE_URL}/admin_sales.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') { alert('Order dibuat: ' + res.order_number); loadSales(); }
        else alert('Gagal membuat order: ' + (res.message || JSON.stringify(res)));
      }).catch(err => console.error(err));
  });

  document.getElementById('saleModalClose').addEventListener('click', () => saleModal.style.display = 'none');
  document.getElementById('closeSaleModal').addEventListener('click', () => saleModal.style.display = 'none');

  document.getElementById('changeStatusBtn').addEventListener('click', () => {
    const id = document.getElementById('saleModal').getAttribute('data-current-id');
    if (!id) return alert('Tidak ada order aktif.');
    const newStatus = prompt('Masukkan status baru (pending, processing, completed, refunded):');
    if (!newStatus) return;
    fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status:newStatus}) })
      .then(r => r.json()).then(res => {
        if (res.status === 'success') { alert('Status diperbarui'); loadSales(); saleModal.style.display = 'none'; }
        else alert('Gagal: ' + (res.message || JSON.stringify(res)));
      }).catch(err => console.error(err));
  });

  function loadSales() {
    if (!salesTableBody) return;
    salesTableBody.innerHTML = `<tr><td colspan="7">Memuat data penjualan...</td></tr>`;
    fetch(`${API_BASE_URL}/admin_sales.php`)
      .then(r => r.json())
      .then(list => {
        if (!Array.isArray(list) || list.length === 0) { salesTableBody.innerHTML = `<tr><td colspan="7">Belum ada order.</td></tr>`; return; }
        salesTableBody.innerHTML = '';
        list.forEach(s => {
          salesTableBody.insertAdjacentHTML('beforeend', `
            <tr data-id="${s.id}">
              <td>${s.id}</td>
              <td>${escapeHtml(s.order_number)}</td>
              <td>${escapeHtml(s.customer_name || 'Guest')}</td>
              <td>Rp ${new Intl.NumberFormat('id-ID').format(parseFloat(s.total || 0))}</td>
              <td><span class="status ${s.status === 'completed' ? 'status-completed' : 'status-pending'}">${s.status}</span></td>
              <td>${s.created_at}</td>
              <td>
                <button class="action-btn btn-view-sale" data-id="${s.id}">Lihat</button>
                <button class="action-btn btn-delete-sale" data-id="${s.id}">Hapus</button>
              </td>
            </tr>`);
        });
        attachSaleListeners();
      }).catch(err => { console.error(err); salesTableBody.innerHTML = `<tr><td colspan="7">Gagal memuat penjualan.</td></tr>`; });
  }

  function attachSaleListeners() {
    document.querySelectorAll('.btn-view-sale').forEach(b => { b.removeEventListener('click', handleViewSale); b.addEventListener('click', handleViewSale); });
    document.querySelectorAll('.btn-delete-sale').forEach(b => { b.removeEventListener('click', handleDeleteSale); b.addEventListener('click', handleDeleteSale); });
  }

  function handleViewSale() {
    const id = this.getAttribute('data-id');
    fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`)
      .then(r => r.json())
      .then(s => {
        document.getElementById('saleModalTitle').textContent = `Order ${s.order_number || s.id}`;
        let products = [];
        try { products = s.products ? JSON.parse(s.products) : []; } catch(e) { products = []; }
        let html = `<p><strong>Order:</strong> ${escapeHtml(s.order_number || '')}</p>
                    <p><strong>Pelanggan:</strong> ${escapeHtml(s.customer_name || 'Guest')}</p>
                    <p><strong>Total:</strong> Rp ${new Intl.NumberFormat('id-ID').format(parseFloat(s.total || 0))}</p>
                    <p><strong>Status:</strong> ${escapeHtml(s.status)}</p>
                    <p><strong>Payment:</strong> ${escapeHtml(s.payment_method || '-')}</p>
                    <p><strong>Catatan:</strong> ${escapeHtml(s.note || '-')}</p>
                    <hr><h4>Produk</h4>`;
        if (Array.isArray(products) && products.length) {
          html += `<ul>` + products.map(p => `<li>${escapeHtml(p.name || p.product_id || 'Unnamed')} — qty: ${p.qty||1} — Rp ${new Intl.NumberFormat('id-ID').format(parseFloat(p.price||0))}</li>`).join('') + `</ul>`;
        } else html += `<p>- Tidak ada produk tersimpan -</p>`;
        document.getElementById('saleModalBody').innerHTML = html;
        saleModal.setAttribute('data-current-id', s.id);
        saleModal.style.display = 'flex';
      });
  }

  function handleDeleteSale() {
    const id = this.getAttribute('data-id');
    if (!confirm('Hapus order ini?')) return;
    fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') { alert('Order dihapus'); loadSales(); } else alert('Gagal: ' + (res.message || JSON.stringify(res)));
      }).catch(err => console.error(err));
  }

    // --- Panggil Fungsi Utama ---
    loadAdminProducts(); 
    loadPendingReviews(); 
});