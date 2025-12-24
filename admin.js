// admin.js - MENGGUNAKAN API PHP laragon
document.addEventListener('DOMContentLoaded', function() {
    
    //  ALAMAT API PHP
  const API_BASE_URL = window.location.origin + '/Semester 3/Toko premium store/api_store/api_storeapi';

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
        fetch(`${API_BASE_URL}/reviews.php`, {
            method: 'PUT',
            credentials: 'include',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: reviewId, status: status })
        })
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (response.ok && data && data.success) {
                alert(`Status ulasan ID ${reviewId} diubah menjadi ${status}.`);
                loadPendingReviews();
            } else {
                const msg = data && data.message ? data.message : 'Gagal mengubah status ulasan.';
                alert(`Gagal: ${msg}`);
                console.error('Update failed', response.status, data);
            }
        })
        .catch(error => console.error('Error update review status:', error));
    }

    // ---------------- Customers & Sales functionality (CRUD lengkap) ----------------

  // helper escape
  function escapeHtml(str) { if (!str) return ''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  // Format products JSON/text to "id(name)" string. Handles arrays or single object.
  function formatProducts(productsRaw) {
    if (!productsRaw) return '-';
    try {
      const products = typeof productsRaw === 'string' ? JSON.parse(productsRaw) : productsRaw;
      if (Array.isArray(products) && products.length > 0) {
        return products.map(p => {
          const id = p.product_id ?? p.productId ?? p.id ?? '?';
          const name = p.product_name ?? p.name ?? ('Produk #' + id);
          return `${id}(${name})`;
        }).join(', ');
      } else if (typeof products === 'object' && products !== null && (products.product_id || products.product_name || products.name)) {
        const id = products.product_id ?? products.productId ?? products.id ?? '?';
        const name = products.product_name ?? products.name ?? ('Produk #' + id);
        return `${id}(${name})`;
      } else if (typeof products === 'string') {
        return products;
      }
      return '-';
    } catch (e) {
      return productsRaw || '-';
    }
  }

  // sections map
const sectionsMap = {
  dashboard: document.querySelector('.stats-grid'),
  orders: document.getElementById('orders-section'), // tambahkan ini
  products: document.getElementById('products-list'),
  reviews: document.getElementById('reviews-pending'),
  customers: document.getElementById('customers-section'),
  sales: document.getElementById('sales-section')
};




// # nama db nya sales, tombolnya pesanan
// Tambah 'orders' ke sectionsMap jika belum ada
sectionsMap.orders = document.getElementById('orders-section');

// Function untuk load Pesanan Terbaru (load semua, filter 5 terbaru di JS)
function loadOrders() {
  const tableBody = document.getElementById('ordersTableBody');
  if (!tableBody) return;
  tableBody.innerHTML = `<tr><td colspan="9">Memuat pesanan terbaru...</td></tr>`;

  fetch(`${API_BASE_URL}/admin_sales.php`)
    .then(r => r.json())
    .then(list => {
      if (!Array.isArray(list) || list.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="9">Belum ada pesanan.</td></tr>`;
        return;
      }

      const recentOrders = list
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 5);

      tableBody.innerHTML = '';

      recentOrders.forEach(order => {
        const statusClass =
          order.status === 'completed' ? 'status-completed' :
          order.status === 'processing' ? 'status-processing' :
          order.status === 'refunded' ? 'status-refunded' :
          'status-pending';

        const totalValue = Math.round(parseFloat(order.total)) || 0;
        const formattedTotal = new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(totalValue);

        // Tampilkan sebagai: id(nama) atau teks bila tidak bisa di-parse
        let produkDisplay = formatProducts(order.products);

        tableBody.insertAdjacentHTML('beforeend', `
          <tr data-id="${order.id}">
            <td>#${order.order_number || order.id}</td>
            <td>${order.customer_id || '-'}</td>
            <td>${escapeHtml(produkDisplay)}</td>
            <td>${escapeHtml(order.payment_method || '-')}</td>
            <td>${escapeHtml(order.note || '-')}</td>
            <td>${formattedTotal}</td>
            <td>${new Date(order.created_at).toLocaleDateString('id-ID')}</td>
            <td><span class="status ${statusClass}">${escapeHtml(order.status)}</span></td>
            <td>
              <button class="action-btn btn-view-order" data-id="${order.id}">Lihat</button>
              <button class="action-btn btn-edit-order" data-id="${order.id}">Edit</button>
              <button class="action-btn btn-delete-order" data-id="${order.id}">Hapus</button>
            </td>
          </tr>
        `);
      });

      // ✅ Pastikan listener dipasang setelah render selesai
      attachOrderListeners();
    })
    .catch(err => {
      console.error('Error load orders:', err);
      tableBody.innerHTML = `<tr><td colspan="9">Gagal memuat pesanan. Cek Laragon Apache/MySQL.</td></tr>`;
    });
}
  

// --- Lihat Detail Order ---
function handleViewOrder() {
  const id = this.getAttribute('data-id');
  fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`)
    .then(r => r.json())
    .then(order => {
      // Format total ke Rupiah
      const totalFormatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(parseFloat(order.total || 0));

      // Parsing produk menjadi id(nama) — qty — harga
      let produkList = '';
      try {
        const products = typeof order.products === 'string' ? JSON.parse(order.products) : order.products;
        if (Array.isArray(products) && products.length > 0) {
          produkList = '<ul>' + products.map(p => {
            const id = p.product_id ?? p.productId ?? p.id ?? '?';
            const name = p.product_name ?? p.name ?? ('Produk #' + id);
            return `<li>${id}(${escapeHtml(String(name))}) — qty: ${p.qty || 1} — Rp ${new Intl.NumberFormat('id-ID').format(p.price || 0)}</li>`;
          }).join('') + '</ul>';
        } else produkList = '-';
      } catch { produkList = escapeHtml(order.products || '-'); }

      // Buat modal tampilan
      const modalHTML = `
        <div id="orderDetailModal" class="modal" style="display:flex;">
          <div class="modal-content" style="max-width:600px;">
            <span class="modal-close" id="closeOrderDetail">&times;</span>
            <h3>Detail Pesanan ${escapeHtml(order.order_number || '')}</h3>
            <p><strong>Pelanggan:</strong> ${escapeHtml(order.customer_name || order.customer_id || '-')}</p>
            <p><strong>Status:</strong> ${escapeHtml(order.status)}</p>
            <p><strong>Total:</strong> ${totalFormatted}</p>
            <p><strong>Metode Pembayaran:</strong> ${escapeHtml(order.payment_method || '-')}</p>
            <p><strong>Catatan:</strong> ${escapeHtml(order.note || '-')}</p>
            <hr>
            <h4>Produk</h4>
            ${produkList}
            <hr>
            <button id="closeOrderBtn" class="action-btn">Tutup</button>
          </div>
        </div>
      `;
      
      // Hapus modal lama jika ada
      const existingModal = document.getElementById('orderDetailModal');
      if (existingModal) existingModal.remove();

      document.body.insertAdjacentHTML('beforeend', modalHTML);

      // Tombol tutup modal
      document.getElementById('closeOrderDetail').onclick = () => document.getElementById('orderDetailModal').remove();
      document.getElementById('closeOrderBtn').onclick = () => document.getElementById('orderDetailModal').remove();
    })
    .catch(err => console.error('Error lihat order:', err));
}

// --- Edit Order ---
function handleEditOrder() {
  const id = this.getAttribute('data-id');
  const newStatus = prompt('Masukkan status baru (pending, processing, completed, refunded):');
  if (!newStatus) return alert('Edit dibatalkan.');

  fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status: newStatus })
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        alert('✅ Status pesanan diperbarui.');
        loadOrders();
      } else {
        alert('❌ Gagal memperbarui: ' + (res.message || JSON.stringify(res)));
      }
    })
    .catch(err => console.error('Error edit order:', err));
}

// --- Hapus Order ---
function handleDeleteOrder() {
  const id = this.getAttribute('data-id');
  if (!confirm('Yakin ingin menghapus pesanan ini?')) return;

  fetch(`${API_BASE_URL}/admin_sales.php?id=${id}`, {
    method: 'DELETE'
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        alert('🗑️ Pesanan dihapus.');
        loadOrders();
      } else {
        alert('❌ Gagal menghapus pesanan: ' + (res.message || JSON.stringify(res)));
      }
    })
    .catch(err => console.error('Error hapus order:', err));
}


function attachOrderListeners() {
  document.querySelectorAll('.btn-view-order').forEach(b => {
    b.removeEventListener('click', handleViewOrder);
    b.addEventListener('click', handleViewOrder);
  });
  document.querySelectorAll('.btn-edit-order').forEach(b => {
    b.removeEventListener('click', handleEditOrder);
    b.addEventListener('click', handleEditOrder);
  });
  document.querySelectorAll('.btn-delete-order').forEach(b => {
    b.removeEventListener('click', handleDeleteOrder);
    b.addEventListener('click', handleDeleteOrder);
  });
}

// Update showSection untuk handle 'orders'
function showSection(name) {
    Object.values(sectionsMap).forEach(sec => {
        if (sec) sec.style.display = 'none';
    });

    if (name === 'dashboard') {
        sectionsMap.dashboard.style.display = 'grid';

    } else if (name === 'orders') {
        if (sectionsMap.orders) sectionsMap.orders.style.display = 'block';
        loadOrders();  // Load data saat section orders dibuka
    } else if (sectionsMap[name]) {
        sectionsMap[name].style.display = 'block';
    }

    // Load data lain (existing)
    if (name === 'products') loadAdminProducts();
    if (name === 'reviews') loadPendingReviews();
    if (name === 'customers') loadCustomers();
    if (name === 'sales') loadSales();
    if (name === 'orders') loadOrders();
}

// Update event listener sidebar (tambah kondisi untuk "Pesanan")
const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
sidebarLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        sidebarLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');

        const txt = this.textContent.trim().toLowerCase();
        if (txt.includes('pesanan')) {  // Match menu "Pesanan"
            showSection('orders');
        } else if (txt.includes('pelanggan')) {
            showSection('customers');
        } else if (txt.includes('penjualan')) {
            showSection('sales');
        } else if (txt.includes('produk')) {
            showSection('products');
        } else if (txt.includes('ulasan')) {
            showSection('reviews');
        } else {
            showSection('dashboard');
        }
    });
});

// Event listener untuk klik statTotalOrders (pindah ke section Pesanan)
document.getElementById('statTotalOrders')?.addEventListener('click', function() {
    // Highlight menu sidebar "Pesanan"
    sidebarLinks.forEach(l => l.classList.remove('active'));
    const pesananMenu = Array.from(sidebarLinks).find(a => a.textContent.toLowerCase().includes('pesanan'));
    if (pesananMenu) pesananMenu.classList.add('active');
    // Tampilkan section orders
    showSection('orders');
});

  // # Customers atau pelanggan
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

  // Sales atau penjualan
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
    console.log('loadSales dipanggil');  // Tambahkan ini
  const salesTableBody = document.querySelector('#salesTable tbody');  // Atau #salesTableBody tbody jika diubah
  console.log('salesTableBody ditemukan:', salesTableBody); 

  if (!salesTableBody) return;
  salesTableBody.innerHTML = `<tr><td colspan="10">Memuat data penjualan...</td></tr>`;

  fetch(`${API_BASE_URL}/admin_sales.php`)
    .then(r => r.json())
    .then(list => {
       console.log('Data dari API (mentah):', list);  // Tambahkan ini
    console.log('Jumlah data:', list.length); 
      if (!Array.isArray(list) || list.length === 0) {
        salesTableBody.innerHTML = `<tr><td colspan="10">Belum ada data penjualan.</td></tr>`;
        return;
      }
      

      // 🔍 tampilkan semua (atau bisa filter kalau mau)
      const completedSales = list.filter(s => String(s.status).trim().toLowerCase() === 'completed');

      if (completedSales.length === 0) {
        salesTableBody.innerHTML = `<tr><td colspan="10">Belum ada penjualan yang selesai (completed).</td></tr>`;
        return;
      }
      console.log('Data setelah filter (completedSales):', completedSales);  // Tambahkan ini
    console.log('Jumlah data yang akan ditampilkan:', completedSales.length);

      salesTableBody.innerHTML = '';

      completedSales.forEach(s => {
        const totalValue = Math.round(parseFloat(s.total)) || 0;
        const formattedTotal = new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(totalValue);

        const statusClass =
          s.status === 'completed' ? 'status-completed' :
          s.status === 'refunded' ? 'status-refunded' :
          'status-pending';

        salesTableBody.insertAdjacentHTML('beforeend', `
          <tr data-id="${s.id}">
            <td>${s.id}</td>
            <td>${escapeHtml(s.order_number || '-')}</td>
            <td>${escapeHtml(s.customer_id || '-')}</td>
            <td>${escapeHtml(formatProducts(s.products))}</td>
            <td>${formattedTotal}</td>
            <td><span class="status ${statusClass}">${escapeHtml(s.status)}</span></td>
            <td>${escapeHtml(s.payment_method || '-')}</td>
            <td>${escapeHtml(s.note || '-')}</td>
            <td>${s.created_at}</td>
            <td>
              <button class="action-btn btn-view-sale" data-id="${s.id}">Lihat</button>
              <button class="action-btn btn-delete-sale" data-id="${s.id}">Hapus</button>
            </td>
          </tr>
        `);
      });

      attachSaleListeners();
    })
    .catch(err => {
      console.error('Error load sales:', err);
      salesTableBody.innerHTML = `<tr><td colspan="10">Gagal memuat data penjualan.</td></tr>`;
    });
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
          html += `<ul>` + products.map(p => {
            const id = p.product_id ?? p.productId ?? p.id ?? '?';
            const name = p.product_name ?? p.name ?? ('Produk #' + id);
            return `<li>${escapeHtml(String(id))}(${escapeHtml(String(name))}) — qty: ${p.qty||1} — Rp ${new Intl.NumberFormat('id-ID').format(parseFloat(p.price||0))}</li>`;
          }).join('') + `</ul>`;
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

        // Event listener untuk Total Pendapatan agar buka section penjualan
document.getElementById('statTotalRevenue')?.addEventListener('click', function() {
  console.log('Tombol Total Pendapatan diklik!');
    // Highlight menu sidebar "Penjualan"
    document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
    const penjualanMenu = Array.from(document.querySelectorAll('.sidebar-menu a')).find(a => a.textContent.toLowerCase().includes('penjualan'));
    if (penjualanMenu) penjualanMenu.classList.add('active');
    // Tampilkan section penjualan
    showSection('sales');
});

// Event listener untuk Produk Terjual agar buka section penjualan
document.getElementById('statProductsSold')?.addEventListener('click', function() {
    document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
    const penjualanMenu = Array.from(document.querySelectorAll('.sidebar-menu a')).find(a => a.textContent.toLowerCase().includes('penjualan'));
    if (penjualanMenu) penjualanMenu.classList.add('active');
    showSection('sales');
});

// Event listener untuk tombol 'Lihat Semua Penjualan' di Dashboard
document.getElementById('viewAllSalesBtn')?.addEventListener('click', function(e) {
    // Mencegah browser berpindah halaman atau me-refresh
    e.preventDefault(); 
    
    // 1. Hapus status 'active' dari semua menu sidebar
    document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
    
    // 2. Cari menu sidebar "Penjualan" dan berikan class 'active'
    // Ini penting agar pengguna tahu di menu mana dia berada
    const penjualanMenu = Array.from(document.querySelectorAll('.sidebar-menu a')).find(a => a.textContent.toLowerCase().includes('penjualan'));
    if (penjualanMenu) penjualanMenu.classList.add('active');
    
    // 3. Tampilkan section penjualan
    showSection('sales');
});

   // --- FITUR SEARCH/CARI (GLOBAL DI DASHBOARD, FILTER DI SECTION LAIN) ---
   const searchInput = document.querySelector('.search-box input');
   if (searchInput) {
       searchInput.addEventListener('keydown', function(event) {
           if (event.key === 'Enter') {
               const query = this.value.toLowerCase().trim();
               if (!query) return;  // Jika kosong, jangan lakukan apa-apa

               // Cari section aktif
               const activeSection = Object.keys(sectionsMap).find(key => {
                   const section = sectionsMap[key];
                   return section && section.style.display !== 'none';
               });

               if (activeSection === 'dashboard') {
                   // Jika di dashboard, cari global dan redirect
                   performGlobalSearch(query);
               } else {
                   // Jika di section lain, filter seperti sebelumnya
                   performSearch(query, activeSection);
               }
           }
       });
   }

   // Fungsi pencarian global di dashboard (cari di semua data via API)
   async function performGlobalSearch(query) {
       // Cari di produk dulu (contoh prioritas)
       try {
           const products = await fetch(`${API_BASE_URL}/admin_products.php`).then(r => r.json());
           const foundProduct = products.find(p => p.name.toLowerCase().includes(query) || p.category.toLowerCase().includes(query));
           if (foundProduct) {
               // Pindah ke section produk, load, dan filter
               showSection('products');
               loadAdminProducts().then(() => searchProducts(query));
               return;
           }
       } catch (err) {
           console.error('Error fetching products:', err);
       }

       // Jika tidak ditemukan di produk, cari di pelanggan
       try {
           const customers = await fetch(`${API_BASE_URL}/admin_customers.php`).then(r => r.json());
           const foundCustomer = customers.find(c => c.name.toLowerCase().includes(query) || c.email.toLowerCase().includes(query));
           if (foundCustomer) {
               showSection('customers');
               loadCustomers().then(() => searchCustomers(query));
               return;
           }
       } catch (err) {
           console.error('Error fetching customers:', err);
       }

       // Jika tidak ditemukan di pelanggan, cari di penjualan
       try {
           const sales = await fetch(`${API_BASE_URL}/admin_sales.php`).then(r => r.json());
           const foundSale = sales.find(s => s.order_number.toLowerCase().includes(query) || s.customer_id.toString().includes(query));
           if (foundSale) {
               showSection('sales');
               loadSales().then(() => searchSales(query));
               return;
           }
       } catch (err) {
           console.error('Error fetching sales:', err);
       }

       // Jika tidak ditemukan di mana pun, beri pesan
       alert(`Tidak ditemukan hasil untuk "${query}". Coba kata kunci lain.`);
   }

   // Fungsi pencarian/filter di section tertentu (untuk section selain dashboard)
   function performSearch(query, activeSection) {
       switch (activeSection) {
           case 'products':
               searchProducts(query);
               break;
           case 'customers':
               searchCustomers(query);
               break;
           case 'sales':
               searchSales(query);
               break;
           case 'orders':
               searchOrders(query);
               break;
           case 'reviews':
               searchReviews(query);
               break;
           default:
               console.log('Search tidak didukung di section ini');
       }
   }

   // Fungsi filter untuk Produk
   function searchProducts(query) {
       const grid = document.getElementById('adminProductsGrid');
       if (!grid) return;
       const cards = grid.querySelectorAll('.product-management-card');
       cards.forEach(card => {
           const name = card.querySelector('.product-name').textContent.toLowerCase();
           const category = card.querySelector('.product-category').textContent.toLowerCase();
           card.style.display = (name.includes(query) || category.includes(query)) ? 'block' : 'none';
       });
   }

   // Fungsi filter untuk Pelanggan
   function searchCustomers(query) {
       const tableBody = document.querySelector('#customersTable tbody');
       if (!tableBody) return;
       const rows = tableBody.querySelectorAll('tr');
       rows.forEach(row => {
           const name = row.cells[1].textContent.toLowerCase();
           const email = row.cells[2].textContent.toLowerCase();
           row.style.display = (name.includes(query) || email.includes(query)) ? 'table-row' : 'none';
       });
   }

   // Fungsi filter untuk Penjualan
   function searchSales(query) {
       const tableBody = document.querySelector('#salesTable tbody');
       if (!tableBody) return;
       const rows = tableBody.querySelectorAll('tr');
       rows.forEach(row => {
           const orderNumber = row.cells[1].textContent.toLowerCase();
           const customerId = row.cells[2].textContent.toLowerCase();
           row.style.display = (orderNumber.includes(query) || customerId.includes(query)) ? 'table-row' : 'none';
       });
   }

   // Fungsi filter untuk Pesanan
   function searchOrders(query) {
       const tableBody = document.getElementById('ordersTableBody');
       if (!tableBody) return;
       const rows = tableBody.querySelectorAll('tr');
       rows.forEach(row => {
           const orderId = row.cells[0].textContent.toLowerCase();
           const customerId = row.cells[1].textContent.toLowerCase();
           row.style.display = (orderId.includes(query) || customerId.includes(query)) ? 'table-row' : 'none';
       });
   }

   // Fungsi filter untuk Ulasan
   function searchReviews(query) {
       const container = document.getElementById('pendingReviewsContainer');
       if (!container) return;
       const items = container.querySelectorAll('.review-item');
       items.forEach(item => {
           const productName = item.querySelector('p').textContent.toLowerCase();
           const comment = item.querySelector('p:nth-child(2)').textContent.toLowerCase();
           item.style.display = (productName.includes(query) || comment.includes(query)) ? 'block' : 'none';
       });
   }
   

    // --- Panggil Fungsi Utama ---
    loadAdminProducts(); 
    loadPendingReviews(); 
    
});