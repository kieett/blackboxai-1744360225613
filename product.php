<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$translations = include 'lang/' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en') . '.php';

$error = '';
$message = '';
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    addToCart($product_id, $quantity);
    $message = $translations['add_to_cart']; // Use translation
}

try {
    // Get product details with category
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name,
        (SELECT GROUP_CONCAT(image_url) FROM product_images WHERE product_id = p.id) as additional_images
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception($translations['product_not_found']); // Use translation
    }

    // Get related products from same category
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND id != ? 
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : $translations['product_not_found']; ?> - Adidas Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        .zoom-hover:hover { transform: scale(1.05); transition: transform 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-black text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0">
                        <h1 class="text-2xl font-bold">ADIDAS</h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <form action="search.php" method="GET" class="flex">
                        <input type="text" name="q" placeholder="<?php echo $translations['search_placeholder']; ?>"
                               class="rounded-l-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-black text-gray-900">
                        <button type="submit" class="bg-white text-black px-4 py-2 rounded-r-md hover:bg-gray-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <?php if (isLoggedIn()): ?>
                        <a href="cart.php" class="text-gray-300 hover:text-white">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="ml-1"><?php echo getCartItemsCount(); ?></span>
                        </a>
                        <a href="profile.php" class="text-gray-300 hover:text-white">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-300 hover:text-white">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php elseif ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <!-- Product Details -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="md:flex">
                    <!-- Product Images -->
                    <div class="md:w-1/2">
                        <div class="relative h-96">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-full object-cover" id="mainImage">
                            <?php else: ?>
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['additional_images']): ?>
                            <div class="flex p-4 space-x-2 overflow-x-auto">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     class="w-20 h-20 object-cover cursor-pointer hover:opacity-75"
                                     onclick="updateMainImage(this.src)">
                                <?php foreach (explode(',', $product['additional_images']) as $image): ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                         class="w-20 h-20 object-cover cursor-pointer hover:opacity-75"
                                         onclick="updateMainImage(this.src)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div class="md:w-1/2 p-8">
                        <div class="mb-2">
                            <a href="search.php?category=<?php echo $product['category_id']; ?>" 
                               class="text-sm text-gray-500 hover:text-black">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </a>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h1>
                        <div class="text-2xl font-bold text-gray-900 mb-6">
                            $<?php echo number_format($product['price'], 2); ?>
                        </div>
                        <div class="prose max-w-none mb-6">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>

                        <?php if ($product['stock'] > 0): ?>
                            <form method="POST" class="mb-6">
                                <input type="hidden" name="action" value="add_to_cart">
                                <div class="flex items-center space-x-4 mb-4">
                                    <label for="quantity" class="text-gray-700">Quantity:</label>
                                    <select name="quantity" id="quantity" 
                                            class="rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                                        <?php for ($i = 1; $i <= min($product['stock'], 10); $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="text-sm text-gray-500">
                                        <?php echo $product['stock']; ?> in stock
                                    </span>
                                </div>
                                <button type="submit"
                                        class="w-full bg-black text-white px-6 py-3 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                                    <?php echo $translations['add_to_cart']; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
                                <?php echo $translations['out_of_stock']; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Additional Info -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center mb-4">
                                <h2 class="text-lg font-semibold"><?php echo $translations['related_products']; ?></h2>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($related_products as $related): ?>
                                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                        <a href="product.php?id=<?php echo $related['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($related['name']); ?>"
                                                 class="w-full h-48 object-cover">
                                        </a>
                                        <div class="p-4">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <a href="product.php?id=<?php echo $related['id']; ?>" class="hover:text-gray-600">
                                                    <?php echo htmlspecialchars($related['name']); ?>
                                                </a>
                                            </h3>
                                            <div class="text-lg font-bold text-gray-900">
                                                $<?php echo number_format($related['price'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $translations['product_not_found']; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>
</body>
</html>
