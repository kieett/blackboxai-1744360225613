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

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = sanitize($_POST['description']);
                $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (name, slug, description, parent_id) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $slug, $description, $parent_id]);
                    $message = 'Category added successfully';
                } catch(PDOException $e) {
                    $error = 'Error adding category: ' . $e->getMessage();
                }
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

                try {
                    $stmt = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, description = ?, parent_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $parent_id, $id]);
                    $message = 'Category updated successfully';
                } catch(PDOException $e) {
                    $error = 'Error updating category: ' . $e->getMessage();
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);
                try {
                    // First update any child categories to remove parent reference
                    $stmt = $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?");
                    $stmt->execute([$id]);
                    
                    // Then delete the category
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Category deleted successfully';
                } catch(PDOException $e) {
                    $error = 'Error deleting category: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all categories with their parent category names
try {
    $stmt = $pdo->query("
        SELECT c.*, p.name as parent_name,
        (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();

    // Get parent categories for dropdown
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $parent_categories = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Adidas Clone</title>
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
                <a href="products.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-box mr-3"></i>Products
                </a>
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <i class="fas fa-users mr-3"></i>Users
                </a>
                <a href="categories.php" class="flex items-center px-6 py-3 bg-gray-900">
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
                        <h2 class="text-2xl font-bold text-gray-900">Manage Categories</h2>
                        <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Add New Category
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

                <!-- Categories Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($category['parent_name'] ?? 'None'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $category['product_count']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteCategory(<?php echo $category['id']; ?>)"
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

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="action" value="add">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Category</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                        <select name="parent_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            <option value="">None</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"></textarea>
                    </div>

                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-id">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Category</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="edit-name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                        <select name="parent_id" id="edit-parent"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            <option value="">None</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="edit-description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"></textarea>
                    </div>

                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div id="deleteCategoryModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Category</h3>
                    <p class="text-gray-500 mb-4">Are you sure you want to delete this category? This action cannot be undone.</p>
                    
                    <div class="mt-5 sm:mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('deleteCategoryModal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            Delete Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editCategory(category) {
            document.getElementById('edit-id').value = category.id;
            document.getElementById('edit-name').value = category.name;
            document.getElementById('edit-description').value = category.description || '';
            document.getElementById('edit-parent').value = category.parent_id || '';
            document.getElementById('editCategoryModal').classList.remove('hidden');
        }

        function deleteCategory(categoryId) {
            document.getElementById('delete-id').value = categoryId;
            document.getElementById('deleteCategoryModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
