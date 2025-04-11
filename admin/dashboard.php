<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

// Get statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch()['total'];

    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetch()['total'];

    // Revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
    $totalRevenue = $stmt->fetch()['total'] ?? 0;

    // Recent orders
    $stmt = $pdo->query("
        SELECT o.*, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Error fetching statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Adidas Clone</title>
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 bg-gray-900">
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
                    <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
                </div>
            </header>

            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                                <i class="fas fa-users text-blue-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Total Users</h3>
                                <p class="text-2xl font-semibold"><?php echo number_format($totalUsers); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                                <i class="fas fa-box text-green-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Total Products</h3>
                                <p class="text-2xl font-semibold"><?php echo number_format($totalProducts); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10">
                                <i class="fas fa-shopping-cart text-yellow-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Total Orders</h3>
                                <p class="text-2xl font-semibold"><?php echo number_format($totalOrders); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                                <i class="fas fa-dollar-sign text-purple-500 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Total Revenue</h3>
                                <p class="text-2xl font-semibold">$<?php echo number_format($totalRevenue, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold">Recent Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">#<?php echo $order['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            switch($order['status']) {
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                                                case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                            }
                                            ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
