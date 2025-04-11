<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
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
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    // Send order confirmation email
    $subject = "Order Confirmation - Order #$order_id";
    $message = "
        <h2>Thank you for your order!</h2>
        <p>Dear {$order['first_name']},</p>
        <p>Your order has been received and is being processed.</p>
        <p><strong>Order Number:</strong> #{$order_id}</p>
        <p><strong>Order Total:</strong> $" . number_format($order['total_amount'], 2) . "</p>
        <p>We'll send you another email when your order ships.</p>
    ";
    sendEmail($order['email'], $subject, $message);

} catch(Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Adidas Clone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .checkmark {
            animation: checkmark 0.5s ease-in-out forwards;
        }
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
            <div class="bg-white rounded-lg shadow-lg p-6">
                <!-- Success Message -->
                <div class="text-center mb-8">
                    <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
                        <svg class="checkmark w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">Thank You for Your Order!</h1>
                    <p class="mt-2 text-gray-600">Order #<?php echo $order_id; ?> has been placed successfully</p>
                </div>

                <!-- Order Details -->
                <div class="border-t border-gray-200 pt-6">
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
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
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

                    <!-- Next Steps -->
                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <h2 class="text-lg font-semibold mb-4">What's Next?</h2>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-blue-700">
                                <i class="fas fa-info-circle mr-2"></i>
                                We'll send you an email confirmation with your order details and tracking information once your order ships.
                            </p>
                        </div>
                        <div class="mt-6 flex justify-center space-x-4">
                            <a href="profile.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800">
                                <i class="fas fa-user mr-2"></i>
                                View Order History
                            </a>
                            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-shopping-bag mr-2"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
