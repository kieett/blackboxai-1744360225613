<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

$error = '';
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

try {
    // Get order details with user information
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND (o.user_id = ? OR ? = true)
    ");
    $stmt->execute([$order_id, $user_id, isAdmin()]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items with product details
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Adidas Clone</title>
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
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        My Profile
                    </a>
                    <a href="logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Order Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Order #<?php echo $order['id']; ?>
                        </h1>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            <?php
                            switch($order['status']) {
                                case 'pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'processing':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'shipped':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'delivered':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'cancelled':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                            }
                            ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        Ordered on <?php echo date('F d, Y', strtotime($order['created_at'])); ?>
                    </p>
                </div>

                <!-- Order Details -->
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Customer Information -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                            <p class="text-gray-700">
                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                                <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                        </div>

                        <!-- Shipping Address -->
                        <div>
                            <h2 class="text-lg font-semibold mb-4">Shipping Address</h2>
                            <p class="text-gray-700">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if ($item['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                         class="h-12 w-12 object-cover rounded">
                                                <?php else: ?>
                                                    <div class="h-12 w-12 bg-gray-200 rounded"></div>
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $item['quantity']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">$<?php echo number_format($item['price'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="mt-8 flex justify-end">
                        <div class="bg-gray-50 rounded-lg p-6 w-full md:w-1/3">
                            <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                            <div class="space-y-2">
                                <div class="flex justify-between text-gray-700">
                                    <span>Subtotal</span>
                                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <div class="flex justify-between text-gray-700">
                                    <span>Shipping</span>
                                    <span>Free</span>
                                </div>
                                <div class="border-t border-gray-200 pt-2 mt-2">
                                    <div class="flex justify-between text-lg font-semibold">
                                        <span>Total</span>
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                    <!-- Tracking Information -->
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold mb-4">Order Timeline</h2>
                        <div class="relative">
                            <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-px bg-gray-200"></div>
                            <div class="space-y-8 relative">
                                <div class="flex items-center">
                                    <div class="absolute left-1/2 transform -translate-x-1/2">
                                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                                    </div>
                                    <div class="w-1/2 pr-8 text-right">
                                        <p class="font-medium">Order Placed</p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="absolute left-1/2 transform -translate-x-1/2">
                                        <div class="w-4 h-4 <?php echo $order['status'] !== 'pending' ? 'bg-green-500' : 'bg-gray-200'; ?> rounded-full"></div>
                                    </div>
                                    <div class="w-1/2 pl-8">
                                        <p class="font-medium">Processing</p>
                                        <p class="text-sm text-gray-500">Order is being processed</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="absolute left-1/2 transform -translate-x-1/2">
                                        <div class="w-4 h-4 <?php echo $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'bg-green-500' : 'bg-gray-200'; ?> rounded-full"></div>
                                    </div>
                                    <div class="w-1/2 pr-8 text-right">
                                        <p class="font-medium">Shipped</p>
                                        <p class="text-sm text-gray-500">Order has been shipped</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="absolute left-1/2 transform -translate-x-1/2">
                                        <div class="w-4 h-4 <?php echo $order['status'] === 'delivered' ? 'bg-green-500' : 'bg-gray-200'; ?> rounded-full"></div>
                                    </div>
                                    <div class="w-1/2 pl-8">
                                        <p class="font-medium">Delivered</p>
                                        <p class="text-sm text-gray-500">Order has been delivered</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
