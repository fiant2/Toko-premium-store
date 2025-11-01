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

//Hitung Pertumbuhan (Pelanggan Bulan Ini vs Bulan Lalu) ---

//Hitung pelanggan yang bergabung **Bulan Ini (M-0)**
$start_of_current_month = date('Y-m-01 00:00:00');
$customers_this_month = 0;

$sql_this_month = "SELECT COUNT(id) AS total_this_month FROM customers 
                   WHERE created_at >= '$start_of_current_month'";
if ($result_this_month = $conn->query($sql_this_month)) {
    $row_this_month = $result_this_month->fetch_assoc();
    $customers_this_month = $row_this_month['total_this_month'];
    $result_this_month->free();
}

//Hitung pelanggan yang bergabung **Bulan Lalu (M-1)**
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

//Perhitungan Persentase Perubahan ---
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

                    // --- B. Hitung Total Pesanan dari Tabel Sales ---
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

                    // --- C. Hitung Total Pendapatan dari Tabel Sales ---
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


                    // --- D. Hitung Produk Terjual (status completed) ---
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
