<?php
require_once 'env_loader.php';
loadEnv(__DIR__ . '/.env');
// gunakan custom session handler agar session yang disimpan di DB bisa dibaca
include 'db_aktivitas_login.php';
include 'db_config.php';

// CORS headers - izinkan origin localhost dan kirim cookie/session
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
header('Content-Type: application/json');

// Cek user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'can_review' => false,
        'product_id' => null,
        'message' => 'Login dulu untuk menulis review'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
// name used to detect already-submitted reviews (reviews table stores user_name)
$user_name = $_SESSION['user_name'] ?? null;
// Jika product_id tidak disediakan, ambil pesanan completed terakhir
$product_id = $_GET['product_id'] ?? null;

// Build a list of all distinct products the user has purchased (completed orders)
if (!$product_id) {
    $products_info = [];
    $seen = [];
    $stmt_all = $conn->prepare("SELECT products FROM sales WHERE customer_id = ? AND status = 'completed' ORDER BY created_at DESC");
    $stmt_all->bind_param('i', $user_id);
    $stmt_all->execute();
    $res_all = $stmt_all->get_result();
    while ($row_all = $res_all ? $res_all->fetch_assoc() : null) {
        if (empty($row_all) || empty($row_all['products'])) continue;
        $decoded = json_decode($row_all['products'], true);
        if (!is_array($decoded)) continue;
        foreach ($decoded as $it) {
            $pid = isset($it['product_id']) ? intval($it['product_id']) : null;
                if (!$pid || isset($seen[$pid])) continue;
                // skip if user already reviewed this product
                if ($user_name) {
                    $dq = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE product_id = ? AND user_name = ?");
                    $dq->bind_param('is', $pid, $user_name);
                    $dq->execute();
                    $dr = $dq->get_result()->fetch_assoc();
                    $dq->close();
                    if ($dr && $dr['cnt'] > 0) continue;
                }
            $seen[$pid] = true;
            $pname = $it['product_name'] ?? null;
            $image = $it['image'] ?? null;
            // try to fetch product name/image from products table if missing
            if ($pid && (!$pname || !$image)) {
                $q = $conn->prepare("SELECT name, image_url FROM products WHERE id = ? LIMIT 1");
                $q->bind_param('i', $pid);
                $q->execute();
                $r = $q->get_result()->fetch_assoc();
                if ($r) {
                    if (!$pname && !empty($r['name'])) $pname = $r['name'];
                    if (!$image && !empty($r['image_url'])) $image = $r['image_url'];
                }
                $q->close();
            }
            $products_info[] = ['product_id' => $pid, 'product_name' => $pname ?: ('Produk #' . ($pid ?? '')), 'image' => $image ?? null];
        }
    }
    $stmt_all->close();

    // default selected product is the most recent product from completed orders (if any)
    if (!empty($products_info)) {
        $product_id = $products_info[0]['product_id'];
    }
}

if (!$product_id) {
    // Jika tetap tidak ada product_id, cukup cek apakah ada pesanan completed
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM sales WHERE customer_id = ? AND status = 'completed'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $cntRow = $res ? $res->fetch_assoc() : ['cnt' => 0];

    if ($cntRow['cnt'] > 0) {
        if (empty($products_info)) {
            echo json_encode([
                'can_review' => false,
                'product_id' => null,
                'products' => [],
                'message' => 'Semua produk sudah pernah diulas'
            ]);
        } else {
            echo json_encode([
                'can_review' => true,
                'product_id' => null,
                'products' => $products_info,
                'message' => 'Boleh menulis review (pilih produk jika perlu)'
            ]);
        }
    } else {
        echo json_encode([
            'can_review' => false,
            'product_id' => null,
            'message' => 'Anda belum membeli produk ini'
        ]);
    }

    exit;
}

// Cek apakah user pernah membeli produk tersebut (status completed)
// products kolom menyimpan JSON teks. Cek dua pola: product_id sebagai string atau number
$sql = "SELECT COUNT(*) as cnt FROM sales WHERE customer_id = ? AND status = 'completed' AND (products LIKE ? OR products LIKE ?)";
$stmt = $conn->prepare($sql);
$pattern_str = '%"product_id":"' . $conn->real_escape_string($product_id) . '"%';
$pattern_num = '%"product_id":' . $conn->real_escape_string($product_id) . '%';
$stmt->bind_param('iss', $user_id, $pattern_str, $pattern_num);
$stmt->execute();
$res = $stmt->get_result();
$result = $res ? $res->fetch_assoc() : ['cnt' => 0];

if ($result['cnt'] > 0) {
    // Check if already reviewed by this user
    if ($user_name) {
        $dup = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE product_id = ? AND user_name = ?");
        $dup->bind_param('is', $product_id, $user_name);
        $dup->execute();
        $dupR = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($dupR && $dupR['cnt'] > 0) {
            echo json_encode([
                'can_review' => false,
                'product_id' => null,
                'message' => 'Anda sudah menulis ulasan untuk produk ini.'
            ]);
            exit;
        }
    }
    // ambil nama produk untuk ditampilkan di frontend
    $product_name = null;
    $q = $conn->prepare("SELECT name FROM products WHERE id = ? LIMIT 1");
    $q->bind_param('i', $product_id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    if ($r && !empty($r['name'])) $product_name = $r['name'];
    $q->close();

    // Jika belum ada daftar produk ($products_info), coba ambil order yang berisi product_id
    if (empty($products_info)) {
        $stmt2 = $conn->prepare("SELECT products FROM sales WHERE customer_id = ? AND status = 'completed' AND (products LIKE ? OR products LIKE ?) ORDER BY created_at DESC LIMIT 1");
        $pattern_str2 = '%"product_id":"' . $conn->real_escape_string($product_id) . '"%';
        $pattern_num2 = '%"product_id":' . $conn->real_escape_string($product_id) . '%';
        $stmt2->bind_param('iss', $user_id, $pattern_str2, $pattern_num2);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $row2 = $res2 ? $res2->fetch_assoc() : null;
        if ($row2 && !empty($row2['products'])) {
            $decoded2 = json_decode($row2['products'], true);
                if (is_array($decoded2)) {
                    $products_info = [];
                    foreach ($decoded2 as $it) {
                        $pid2 = isset($it['product_id']) ? intval($it['product_id']) : null;
                        $pname2 = $it['product_name'] ?? null;
                        if (!$pid2) continue;
                        // skip if already reviewed
                        if ($user_name) {
                            $dq2 = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE product_id = ? AND user_name = ?");
                            $dq2->bind_param('is', $pid2, $user_name);
                            $dq2->execute();
                            $dr2 = $dq2->get_result()->fetch_assoc();
                            $dq2->close();
                            if ($dr2 && $dr2['cnt'] > 0) continue;
                        }
                        if ($pid2 && !$pname2) {
                            $q2 = $conn->prepare("SELECT name FROM products WHERE id = ? LIMIT 1");
                            $q2->bind_param('i', $pid2);
                            $q2->execute();
                            $r2 = $q2->get_result()->fetch_assoc();
                            if ($r2 && !empty($r2['name'])) $pname2 = $r2['name'];
                            $q2->close();
                        }
                        $products_info[] = ['product_id' => $pid2, 'product_name' => $pname2 ?: ('Produk #' . ($pid2 ?? ''))];
                    }
                }
        }
        $stmt2->close();
    }

    echo json_encode([
        'can_review' => true,
        'product_id' => (int)$product_id,
        'product_name' => $product_name,
        'products' => $products_info ?? [],
        'message' => 'Boleh menulis review'
    ]);
} else {
    echo json_encode([
        'can_review' => false,
        'product_id' => null,
        'message' => 'Anda belum membeli produk ini'
    ]);
}
?>
