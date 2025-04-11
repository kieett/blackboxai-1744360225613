<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);

                try {
                    // Handle image upload
                    $image = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $image = uploadImage($_FILES['image'], '../uploads/products/');
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, slug, description, price, stock, category_id, image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $slug, $description, $price, $stock, $category_id, $image]);
                    $message = 'Product added successfully';
                } catch(PDOException $e) {
                    $error = 'Error adding product: ' . $e->getMessage();
                }
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);

                try {
                    $image_sql = '';
                    $params = [$name, $description, $price, $stock, $category_id];

                    // Handle image upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $image = uploadImage($_FILES['image'], '../uploads/products/');
                        if ($image) {
                            $image_sql = ', image = ?';
                            $params[] = $image;
                        }
                    }

                    $params[] = $id;
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, description = ?, price = ?, stock = ?, category_id = ? $image_sql
                        WHERE id = ?
                    ");
                    $stmt->execute($params);
                    $message = 'Product updated successfully';
                } catch(PDOException $e) {
                    $error = 'Error updating product: ' . $e->getMessage();
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Product deleted successfully';
                } catch(PDOException $e) {
                    $error = 'Error deleting product: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all products
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();

    // Get categories for form
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Adidas Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-black text-white">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            <nav class="mt-6">
                <a href="dashboard.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="products.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <i class="fas fa-box mr-3"></i>Products
                </a>
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-users mr-3"></i>Users
                </a>
                <a href="categories.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-tags mr-3"></i>Categories
                </a>
                <a href="logout.php" class="flex items-center px-6 py-3 hover:bg-gray-900 mt-auto">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Manage Products</h2>
                        <button onclick="document.getElementById('addProductModal').classList.remove('hidden')"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Add New Product
                        </button>
                    </div>
                </div>
            </header>

            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Products Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($product['image']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="h-12 w-12 object-cover rounded">
                                    <?php else: ?>
                                        <div class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo $product['stock']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="add">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Product</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" step="0.01" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" name="stock" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Image</label>
                        <input type="file" name="image" accept="image/*"
                               class="mt-1 block w-full">
                    </div>

                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <!-- Similar structure to Add Product Modal -->
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Product</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="edit-name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="edit-description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category_id" id="edit-category" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" id="edit-price" step="0.01" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" name="stock" id="edit-stock" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Image</label>
                        <input type="file" name="image" accept="image/*"
                               class="mt-1 block w-full">
                    </div>

                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editProductModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteProductModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Product</h3>
                    <p class="text-gray-500 mb-4">Are you sure you want to delete this product? This action cannot be undone.</p>
                    
                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('deleteProductModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            Delete Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('edit-id').value = product.id;
            document.getElementById('edit-name').value = product.name;
            document.getElementById('edit-description').value = product.description;
            document.getElementById('edit-category').value = product.category_id;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-stock').value = product.stock;
            document.getElementById('editProductModal').classList.remove('hidden');
        }

        function deleteProduct(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('deleteProductModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
