<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$translations = include 'lang/' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en') . '.php';

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'relevance';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;

try {
    // Get categories for filter
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();

    // Build search query
    $params = [];
    $sql = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1
    ";

    if ($query) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_term = "%$query%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($category) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category;
    }

    // Add sorting
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        default:
            if ($query) {
                $sql .= " ORDER BY 
                    CASE 
                        WHEN p.name LIKE ? THEN 1 
                        WHEN p.name LIKE ? THEN 2 
                        ELSE 3 
                    END";
                $params[] = "$query%";  // Starts with query
                $params[] = "%$query%";  // Contains query
            } else {
                $sql .= " ORDER BY p.created_at DESC";
            }
    }

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as count FROM ($sql) as count_table";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_count = $stmt->fetch()['count'];
    $total_pages = ceil($total_count / $per_page);

    // Add pagination
    $offset = ($page - 1) * $per_page;
    $sql .= " LIMIT $per_page OFFSET $offset";

    // Get products
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = 'Error fetching search results: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Adidas Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; }
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
                        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                               placeholder="Search products..."
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
        <!-- Search Results Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <?php if ($query): ?>
                    Search Results for "<?php echo htmlspecialchars($query); ?>"
                <?php else: ?>
                    All Products
                <?php endif; ?>
                <span class="text-gray-500 text-lg font-normal">(<?php echo $total_count; ?> items)</span>
            </h1>
        </div>

        <!-- Filters and Sort -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" onchange="this.form.submit()"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
                    <select name="sort" id="sort" onchange="this.form.submit()"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No products found matching your criteria</p>
                <a href="index.php" class="mt-4 inline-block bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="p-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="hover:text-gray-600">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </p>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </span>
                                <form action="cart.php" method="POST" class="inline">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="text-black hover:text-gray-600">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page-1; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-black bg-gray-100' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page+1; ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
