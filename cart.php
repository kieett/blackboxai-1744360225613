<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$error = '';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    switch ($_POST['action']) {
        case 'update':
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
            updateCartQuantity($product_id, $quantity);
            $message = 'Cart updated successfully';
            break;
            
        case 'remove':
            removeFromCart($product_id);
            $message = 'Item removed from cart';
            break;
            
        case 'clear':
            clearCart();
            $message = 'Cart cleared';
            break;
    }
}

// Get cart items with product details
$cart_items = array();
$total = 0;

if (!empty($_SESSION['cart'])) {
    try {
        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT id, name, price, image, stock 
            FROM products 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;
            
            $cart_items[] = array_merge($product, [
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ]);
        }
    } catch(PDOException $e) {
        $error = 'Error fetching cart items: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Adidas Clone</title>
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
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            My Profile
                        </a>
                        <a href="logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Login
                        </a>
                        <a href="register.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

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

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                <i class="fas fa-shopping-cart text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 mb-4">Your cart is empty</p>
                <a href="index.php" class="inline-block bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="h-16 w-16 object-cover rounded">
                                    <?php else: ?>
                                        <div class="h-16 w-16 bg-gray-200 rounded"></div>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    $<?php echo number_format($item['price'], 2); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>"
                                           class="w-20 rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                                    <button type="submit" class="text-gray-600 hover:text-gray-900">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    $<?php echo number_format($item['subtotal'], 2); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Cart Summary -->
                <div class="bg-gray-50 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Clear Cart
                            </button>
                        </form>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">Subtotal</div>
                            <div class="text-2xl font-bold text-gray-900">$<?php echo number_format($total, 2); ?></div>
                            <div class="text-sm text-gray-600 mt-1">Shipping calculated at checkout</div>
                            <a href="checkout.php" class="inline-block mt-4 px-6 py-3 bg-black text-white rounded-md hover:bg-gray-800">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chatbot -->
    <div id="chatbot" class="fixed bottom-4 right-4 z-50">
        <button id="chatbot-toggle" class="bg-black text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center hover:bg-gray-800">
            <i class="fas fa-comments text-2xl"></i>
        </button>
        <div id="chatbot-container" class="hidden absolute bottom-16 right-0 w-80 bg-white rounded-lg shadow-xl">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Chat with us</h3>
            </div>
            <div id="chat-messages" class="h-80 overflow-y-auto p-4">
                <!-- Messages will be added here -->
            </div>
            <div class="p-4 border-t">
                <div class="flex">
                    <input type="text" id="chat-input" placeholder="Type a message..." 
                           class="flex-1 px-3 py-2 border rounded-l-md focus:outline-none focus:ring-2 focus:ring-black">
                    <button id="send-message" class="bg-black text-white px-4 py-2 rounded-r-md hover:bg-gray-800">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="chatbot/script.js"></script>
</body>
</html>
