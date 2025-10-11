<?php
// admin.php - Bagian Paling Atas File

// 1. Panggil file koneksi database
require_once 'api_store/api_storeapi/db_config.php';

// --- A. Hitung Total Pelanggan Keseluruhan ---
$total_customers = 0;
$sql_total = "SELECT COUNT(id) AS total FROM customers";
if ($result_total = $conn->query($sql_total)) {
    $row_total = $result_total->fetch_assoc();
    $total_customers = $row_total['total'];
    $result_total->free();
}

// --- B. Hitung Pertumbuhan (Pelanggan Bulan Ini vs Bulan Lalu) ---

// 2. Hitung pelanggan yang bergabung **Bulan Ini (M-0)**
$start_of_current_month = date('Y-m-01 00:00:00');
$customers_this_month = 0;

$sql_this_month = "SELECT COUNT(id) AS total_this_month FROM customers 
                   WHERE created_at >= '$start_of_current_month'";
if ($result_this_month = $conn->query($sql_this_month)) {
    $row_this_month = $result_this_month->fetch_assoc();
    $customers_this_month = $row_this_month['total_this_month'];
    $result_this_month->free();
}

// 3. Hitung pelanggan yang bergabung **Bulan Lalu (M-1)**
$start_of_last_month = date('Y-m-01 00:00:00', strtotime('last month'));
$end_of_last_month = date('Y-m-t 23:59:59', strtotime('last month'));

$customers_last_month = 0;
$sql_last_month = "SELECT COUNT(id) AS total_last_month FROM customers 
                   WHERE created_at BETWEEN '$start_of_last_month' AND '$end_of_last_month'";
if ($result_last_month = $conn->query($sql_last_month)) {
    $row_last_month = $result_last_month->fetch_assoc();
    $customers_last_month = $row_last_month['total_last_month'];
    $result_last_month->free();
}

// --- C. Perhitungan Persentase Perubahan ---
$percentage_change = 0;
$growth_status = 'neutral'; 
$growth_text = '0.0% dari bulan lalu';

if ($customers_last_month > 0) {
    $percentage_change = (($customers_this_month - $customers_last_month) / $customers_last_month) * 100;
} elseif ($customers_this_month > 0) {
    // Jika bulan lalu 0, tapi bulan ini ada (pertumbuhan tak terhingga, kita tampilkan 100%+)
    $percentage_change = 100; 
}

$formatted_percentage = number_format(abs($percentage_change), 1) . '%';

if ($percentage_change > 0) {
    $growth_status = 'up'; // Class CSS untuk tren naik (biasanya hijau)
    $growth_text = '+' . $formatted_percentage . ' dari bulan lalu';
} elseif ($percentage_change < 0) {
    $growth_status = 'down'; // Class CSS untuk tren turun (biasanya merah)
    $growth_text = '-' . $formatted_percentage . ' dari bulan lalu';
} ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard | Akun Premium Store</title>
    <link rel="stylesheet" href="admin.css" />
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h2>Admin<span>Panel</span></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><span class="sidebar-menu-icon">📊</span> Dashboard</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">🛒</span> Pesanan</a></li>
                <li><a href="#reviews"><span class="sidebar-menu-icon">⭐</span> Ulasan (Pending)</a></li>
                <li><a href="#products-list"><span class="sidebar-menu-icon">📦</span> Produk</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">👥</span> Pelanggan</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">💰</span> Penjualan</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">⚙</span> Pengaturan</a></li>
                <li><a href="#"><span class="sidebar-menu-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="admin-header">
                <h1>Dashboard Admin</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" placeholder="Cari...">
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar">A</div>
                        <div class="user-info">
                            <span class="user-name">Admin User</span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Pendapatan</div>
                        <div class="stat-icon bg-green">💰</div>
                    </div>
                    <div class="stat-value">Rp 12.450.000</div>
                    <div class="stat-change positive">+12.5% dari bulan lalu</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Pesanan</div>
                        <div class="stat-icon bg-blue">🛒</div>
                    </div>
                    <div class="stat-value">328</div>
                    <div class="stat-change positive">+8.2% dari bulan lalu</div>
                </div>
                
    <div class="stat-card stat-link-overlay" id="statTotalCustomers" style="cursor:pointer;">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Total Pelanggan</div>
            <div class="stat-icon bg-orange">👥</div>
        </div>
        <div class="stat-value"><?php echo number_format($total_customers, 0, ',', '.'); ?></div>
        <div class="stat-change <?php echo $growth_class; ?>">
            <?php echo $growth_text; ?>
        </div>
    </div>
</div>
</a>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Produk Terjual</div>
                        <div class="stat-icon bg-purple">📦</div>
                    </div>
                    <div class="stat-value">415</div>
                    <div class="stat-change positive">+10.3% dari bulan lalu</div>
                </div>
            </div>

            <div class="card" id="orders-section">
                <div class="card-header" >
                    <h3 class="card-title">Pesanan Terbaru</h3>
                    <span class="card-action">Lihat Semua</span>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Produk</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- contoh statis; admin.js akan memuat data khusus di section Penjualan -->
                            <tr>
                                <td>#ORD-7821</td>
                                <td>
                                    <div class="customer">
                                        <div class="customer-avatar">R</div>
                                        <div class="customer-info">
                                            <span class="customer-name">Rizki Abdullah</span>
                                            <span class="customer-email">rizki@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>YouTube Premium</td>
                                <td>12 Nov 2024</td>
                                <td>Rp 30.000</td>
                                <td><span class="status status-completed">Selesai</span></td>
                                <td>
                                    <button class="action-btn btn-view">Lihat</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" id="products-list">
                <div class="card-header">
                    <h3 class="card-title">Kelola Produk</h3>
                    <span class="card-action" id="addProductBtn">Tambah Produk</span>
                </div>
                <div class="card-body">
                    <div class="products-grid" id="adminProductsGrid">
                        <p>Memuat produk...</p>
                    </div>
                </div>
            </div>

            <div class="card" id="reviews-pending">
                <div class="card-header">
                    <h3 class="card-title">Ulasan Menunggu Persetujuan</h3>
                </div>
                <div class="card-body">
                    <div class="reviews-list" id="pendingReviewsContainer">
                        <p>Memuat ulasan pending...</p>
                    </div>
                </div>
            </div>

            <!-- ======== Pelanggan Section ======== -->
            <div class="card" id="customers-section" style="display:none;">
              <div class="card-header">
                <h3 class="card-title">Daftar Pelanggan</h3>
                <span class="card-action" id="addCustomerBtn">Tambah Pelanggan</span>
              </div>
              <div class="card-body" id="customersBody">
                <div class="table-wrapper">
                  <table class="table" id="customersTable">
                    <thead>
                      <tr>
                        <th>ID</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Terdaftar</th><th>Status</th><th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><td colspan="7">Memuat pelanggan...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- ======== Penjualan Section ======== -->
            <div class="card" id="sales-section" style="display:none;">
              <div class="card-header">
                <h3 class="card-title">Data Penjualan</h3>
                <span class="card-action" id="addSaleBtn">Buat Order Manual</span>
              </div>
              <div class="card-body" id="salesBody">
                <table class="table" id="salesTable">
                  <thead>
                    <tr>
                      <th>ID</th><th>No. Order</th><th>Pelanggan</th><th>Produk</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td colspan="7">Memuat data penjualan...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

        </main>
    </div>

    <!-- Modal: Tambah Produk (sudah ada dari sebelumnya) -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Produk Baru</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <div class="form-group">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" class="form-input" id="productNameInput" placeholder="Contoh: Spotify Premium">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="productImageInput">Link Gambar Produk</label>
                        <input type="text" class="form-input" id="productImageInput" placeholder="https://contoh.com/gambar.jpg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" id="productCategoryInput">
                            <option value="">Pilih Kategori</option>
                            <option value="music">Musik & Streaming</option>
                            <option value="video">Video & Entertainment</option>
                            <option value="gaming">Gaming</option>
                            <option value="software">Software</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-input" id="productPriceInput" placeholder="Contoh: 25000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-textarea" id="productDescInput" placeholder="Deskripsi produk..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fitur</label>
                        <textarea class="form-textarea" id="productFeaturesInput" placeholder="Fitur produk (satu per baris)"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Stok</label>
                        <select class="form-select" id="productStockInput">
                            <option value="in-stock">Tersedia</option>
                            <option value="low-stock">Terbatas</option>
                            <option value="out-of-stock">Habis</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Penerbitan</label>
                        <select class="form-select" id="productStatusInput">
                            <option value="draft">Draft (Tidak Tampil di User)</option>
                            <option value="published">Terbitkan (Tampil di User)</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                    <button class="btn-primary" id="saveProductFinalBtn">Simpan Produk</button>
                     <button class="modal-close">Batal</button>
                    </div>

                    </form>
            </div>

    </div>

    <!-- Modal: Customer (Tambah/Edit) -->
    <div class="modal-overlay" id="customerModal" style="display:none;">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title" id="customerModalTitle">Tambah Pelanggan</h3>
          <button class="modal-close" id="customerModalClose">&times;</button>
        </div>
        <div class="modal-body">
          <form id="customerForm">
            <input type="hidden" id="customerId">
            <div class="form-group">
              <label class="form-label">Nama</label>
              <input class="form-input" id="customerName" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input class="form-input" id="customerEmail" required type="email">
            </div>
            <div class="form-group">
              <label class="form-label">Telepon</label>
              <input class="form-input" id="customerPhone">
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-select" id="customerStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="banned">Banned</option>
              </select>
            </div>
            <div class="modal-footer">
              <button class="btn-primary" id="saveCustomerBtn">Simpan</button>
              <button class="modal-close" id="cancelCustomerBtn">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal: Sale Detail & Edit -->
    <div class="modal-overlay" id="saleModal" style="display:none;">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title" id="saleModalTitle">Detail Order</h3>
          <button class="modal-close" id="saleModalClose">&times;</button>
        </div>
        <div class="modal-body" id="saleModalBody">
          <p>Memuat detail...</p>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" id="changeStatusBtn">Ubah Status</button>
          <button class="modal-close" id="closeSaleModal">Tutup</button>
        </div>
      </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>
