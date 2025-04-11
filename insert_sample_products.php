<?php
require_once 'config/database.php';

try {
    // Insert sample products
    $stmt = $pdo->prepare("
        INSERT INTO products (name, slug, description, price, stock, category_id, image) VALUES
        ('Ultraboost 21', 'ultraboost-21', 'Chạy với phong cách và hiệu suất.', 180.00, 10, 1, 'https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg'),
        ('NMD R1', 'nmd-r1', 'Giày thể thao phong cách.', 140.00, 15, 1, 'https://images.pexels.com/photos/1598505/pexels-photo-1598505.jpeg'),
        ('Stan Smith', 'stan-smith', 'Giày cổ điển cho mọi dịp.', 90.00, 20, 1, 'https://images.pexels.com/photos/1456706/pexels-photo-1456706.jpeg')
    ");
    $stmt->execute();
    echo "Đã chèn sản phẩm mẫu thành công.";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
