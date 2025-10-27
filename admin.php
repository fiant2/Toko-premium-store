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
} 

// --- Hitung Total Pesanan dari Tabel Sales ---
$total_orders = 0;
$sql_orders = "SELECT COUNT(id) AS total_orders FROM sales";
if ($result_orders = $conn->query($sql_orders)) {
    $row_orders = $result_orders->fetch_assoc();
    $total_orders = $row_orders['total_orders'];
    $result_orders->free();
}

// --- Hitung Pertumbuhan Pesanan (Bulan Ini vs Bulan Lalu) ---
// Gunakan variabel yang sudah ada dari customers (start_of_current_month, dll.)
$orders_this_month = 0;
$sql_orders_this = "SELECT COUNT(id) AS total_this FROM sales WHERE created_at >= '$start_of_current_month'";
if ($result_orders_this = $conn->query($sql_orders_this)) {
    $row_orders_this = $result_orders_this->fetch_assoc();
    $orders_this_month = $row_orders_this['total_this'];
    $result_orders_this->free();
}

$orders_last_month = 0;
$sql_orders_last = "SELECT COUNT(id) AS total_last FROM sales WHERE created_at BETWEEN '$start_of_last_month' AND '$end_of_last_month'";
if ($result_orders_last = $conn->query($sql_orders_last)) {
    $row_orders_last = $result_orders_last->fetch_assoc();
    $orders_last_month = $row_orders_last['total_last'];
    $result_orders_last->free();
}

// Persentase perubahan
$orders_percentage_change = 0;
if ($orders_last_month > 0) {
    $orders_percentage_change = (($orders_this_month - $orders_last_month) / $orders_last_month) * 100;
} elseif ($orders_this_month > 0) {
    $orders_percentage_change = 100;
}
$orders_formatted_percentage = number_format(abs($orders_percentage_change), 1) . '%';
$orders_growth_status = $orders_percentage_change > 0 ? 'positive' : ($orders_percentage_change < 0 ? 'negative' : 'neutral');
$orders_growth_text = $orders_percentage_change > 0 ? '+' . $orders_formatted_percentage . ' dari bulan lalu' : ($orders_percentage_change < 0 ? '-' . $orders_formatted_percentage . ' dari bulan lalu' : '0.0% dari bulan lalu');

// --- Hitung Total Pendapatan dari Tabel Sales ---
$total_revenue = 0;
$sql_revenue = "SELECT SUM(total) AS total_revenue FROM sales WHERE status = 'completed'";
if ($result_revenue = $conn->query($sql_revenue)) {
    $row_revenue = $result_revenue->fetch_assoc();
    $total_revenue = $row_revenue['total_revenue'] ? $row_revenue['total_revenue'] : 0;
    $result_revenue->free();
}

// --- Hitung Pertumbuhan Pendapatan (Bulan Ini vs Bulan Lalu) ---
$start_of_current_month = date('Y-m-01 00:00:00');
$revenue_this_month = 0;
$sql_this_month = "SELECT SUM(total) AS revenue_this_month FROM sales WHERE status = 'completed' and created_at >= '$start_of_current_month'";
if ($result_this_month = $conn->query($sql_this_month)) {
    $row_this_month = $result_this_month->fetch_assoc();
    $revenue_this_month = $row_this_month['revenue_this_month'] ? $row_this_month['revenue_this_month'] : 0;
    $result_this_month->free();
}

$start_of_last_month = date('Y-m-01 00:00:00', strtotime('last month'));
$end_of_last_month = date('Y-m-t 23:59:59', strtotime('last month'));
$revenue_last_month = 0;
$sql_last_month = "SELECT SUM(total) AS revenue_last_month FROM sales WHERE status = 'completed' and created_at BETWEEN '$start_of_last_month' AND '$end_of_last_month'";
if ($result_last_month = $conn->query($sql_last_month)) {
    $row_last_month = $result_last_month->fetch_assoc();
    $revenue_last_month = $row_last_month['revenue_last_month'] ? $row_last_month['revenue_last_month'] : 0;
    $result_last_month->free();
}

// Hitung persentase perubahan
$revenue_percentage_change = 0;
if ($revenue_last_month > 0) {
    $revenue_percentage_change = (($revenue_this_month - $revenue_last_month) / $revenue_last_month) * 100;
} elseif ($revenue_this_month > 0) {
    $revenue_percentage_change = 100;  // Jika bulan lalu 0, anggap 100% pertumbuhan
}
$revenue_formatted_percentage = number_format(abs($revenue_percentage_change), 1) . '%';
$revenue_growth_status = $revenue_percentage_change > 0 ? 'positive' : ($revenue_percentage_change < 0 ? 'negative' : 'neutral');
$revenue_growth_text = $revenue_percentage_change > 0 ? '+' . $revenue_formatted_percentage . ' dari bulan lalu' : 
                       ($revenue_percentage_change < 0 ? '-' . $revenue_formatted_percentage . ' dari bulan lalu' : '0.0% dari bulan lalu');


// --- Hitung Produk Terjual (status completed) ---
$total_sold_products = 0;
$sql_sold = "SELECT COUNT(id) AS total_sold FROM sales WHERE status = 'completed'";
if ($result_sold = $conn->query($sql_sold)) {
    $row_sold = $result_sold->fetch_assoc();
    $total_sold_products = $row_sold['total_sold'];
    $result_sold->free();
}

// Hitung pertumbuhan dari bulan lalu
$sold_this_month = 0;
$sql_sold_this = "SELECT COUNT(id) AS sold_this FROM sales 
                  WHERE status = 'completed' 
                  AND created_at >= '$start_of_current_month'";
if ($res_this = $conn->query($sql_sold_this)) {
    $row = $res_this->fetch_assoc();
    $sold_this_month = $row['sold_this'];
    $res_this->free();
}

$sold_last_month = 0;
$sql_sold_last = "SELECT COUNT(id) AS sold_last FROM sales 
                  WHERE status = 'completed' 
                  AND created_at BETWEEN '$start_of_last_month' AND '$end_of_last_month'";
if ($res_last = $conn->query($sql_sold_last)) {
    $row = $res_last->fetch_assoc();
    $sold_last_month = $row['sold_last'];
    $res_last->free();
}

$sold_percentage_change = 0;
if ($sold_last_month > 0) {
    $sold_percentage_change = (($sold_this_month - $sold_last_month) / $sold_last_month) * 100;
} elseif ($sold_this_month > 0) {
    $sold_percentage_change = 100;
}
$sold_formatted_percentage = number_format(abs($sold_percentage_change), 1) . '%';
$sold_growth_status = $sold_percentage_change > 0 ? 'positive' : ($sold_percentage_change < 0 ? 'negative' : 'neutral');
$sold_growth_text = $sold_percentage_change > 0 ? '+' . $sold_formatted_percentage . ' dari bulan lalu' : 
                    ($sold_percentage_change < 0 ? '-' . $sold_formatted_percentage . ' dari bulan lalu' : '0.0% dari bulan lalu');

?>


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
<div class="stat-card stat-link-overlay" id="statTotalRevenue" style="cursor:pointer;">
    <div class="stat-header">
        <div class="stat-title">Total Pendapatan</div>
        <div class="stat-icon bg-green">💰</div>
    </div>
    <div class="stat-value">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
    <div class="stat-change <?php echo $revenue_growth_status; ?>">
        <?php echo $revenue_growth_text; ?>
    </div>
</div>
<!-- stat card Total Pesanan: -->
<div class="stat-card stat-link-overlay" id="statTotalOrders" style="cursor:pointer;">
    <div class="stat-header">
        <div class="stat-title">Total Pesanan</div>
        <div class="stat-icon bg-blue">🛒</div>
    </div>
    <div class="stat-value"><?php echo number_format($total_orders, 0, ',', '.'); ?></div>
    <div class="stat-change <?php echo $orders_growth_status; ?>">
        <?php echo $orders_growth_text; ?>
    </div>
</div>
                
<div class="stat-card stat-link-overlay" id="statTotalCustomers" style="cursor:pointer;">
    <div class="stat-header">
        <div class="stat-title">Total Pelanggan</div>
        <div class="stat-icon bg-orange">👥</div>
    </div>
    <div class="stat-value"><?php echo number_format($total_customers, 0, ',', '.'); ?></div>
    <div class="stat-change <?php echo $growth_status; ?>">
            <?php echo $growth_text; ?>   
    </div>
</div>
<div class="stat-card stat-link-overlay" id="statProductsSold" style="cursor:pointer;">
    <div class="stat-header">
        <div class="stat-title">Produk Terjual</div>
        <div class="stat-icon bg-purple">📦</div>
    </div>
    <div class="stat-value">
        <?php 
        $total_sold = 0;
        $sql_sold = "SELECT COUNT(id) AS total_sold FROM sales WHERE status='completed'";
        if ($result_sold = $conn->query($sql_sold)) {
            $row_sold = $result_sold->fetch_assoc();
            $total_sold = $row_sold['total_sold'] ?? 0;
            $result_sold->free();
        }

        $sold_this_month = 0;
        $sql_sold_this = "SELECT COUNT(id) AS total_this 
                          FROM sales 
                          WHERE status='completed' 
                          AND created_at >= '$start_of_current_month'";
        if ($r = $conn->query($sql_sold_this)) {
            $sold_this_month = ($r->fetch_assoc())['total_this'] ?? 0;
            $r->free();
        }

        $sold_last_month = 0;
        $sql_sold_last = "SELECT COUNT(id) AS total_last 
                          FROM sales 
                          WHERE status='completed' 
                          AND created_at BETWEEN '$start_of_last_month' AND '$end_of_last_month'";
        if ($r = $conn->query($sql_sold_last)) {
            $sold_last_month = ($r->fetch_assoc())['total_last'] ?? 0;
            $r->free();
        }

        $sold_change = 0;
        if ($sold_last_month > 0) {
            $sold_change = (($sold_this_month - $sold_last_month) / $sold_last_month) * 100;
        } elseif ($sold_this_month > 0) {
            $sold_change = 100;
        }

        $sold_growth_status = $sold_change >= 0 ? 'positive' : 'negative';
        $sold_growth_text = ($sold_change >= 0 ? '+' : '-') . number_format(abs($sold_change), 1) . '% dari bulan lalu';

        echo number_format($total_sold, 0, ',', '.');
        ?>
    </div>
    <div class="stat-change <?= $sold_growth_status; ?>">
        <?= $sold_growth_text; ?>
    </div>
</div>
            </div>

            

            <div class="card" id="orders-section" style="display:none">
    <div class="card-header">
        <h3 class="card-title">Pesanan Terbaru</h3>
        <span class="card-action" onclick="showSection('sales')">Lihat Semua Penjualan</span>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Id Pelanggan</th>
                    <th>Produk</th>
                    <th>Metode Pembayaran</th>
                    <th>Catatan</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <tr><td colspan="6">Memuat pesanan terbaru...</td></tr>
            </tbody>
        </table>
    </div>
</div>

            <div class="card" id="products-list" style="display:none">
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

            <div class="card" id="reviews-pending" style="display:none">
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
                      <th>ID</th>
      <th>Nomor Order</th>
      <th>ID Pelanggan</th>
      <th>Produk</th>
      <th>Total</th>
      <th>Status</th>
      <th>Metode Pembayaran</th>
      <th>Catatan</th>
      <th>Tanggal</th>
      <th>Aksi</th>
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