<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetchColumn();
    echo "Tổng số sản phẩm trong cơ sở dữ liệu: " . $product_count;
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
