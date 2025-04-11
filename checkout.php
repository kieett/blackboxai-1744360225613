<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$message = '';
$error = '';

// Get cart items and total
$cart_items = array();
$total = 0;

try {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock 
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
    $error = 'Error processing cart: ' . $e->getMessage();
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['address']) . "\n" . 
                       sanitize($_POST['city']) . "\n" . 
                       sanitize($_POST['state']) . "\n" . 
                       sanitize($_POST['zip']) . "\n" . 
                       sanitize($_POST['country']);
    
    // Validate input
    if (empty($_POST['address']) || empty($_POST['city']) || empty($_POST['state']) || 
        empty($_POST['zip']) || empty($_POST['country'])) {
        $error = 'All fields are required';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, status, payment_status) 
                VALUES (?, ?, ?, ?, 'pending', 'paid')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $total,
                $shipping_address,
                $shipping_address // Using same address for billing
            ]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Update product stock
                $new_stock = $item['stock'] - $item['quantity'];
                $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")
                    ->execute([$new_stock, $item['id']]);
            }
            
            // Clear cart
            clearCart();
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to order confirmation
            header("Location: order-confirmation.php?id=$order_id");
            exit();
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = 'Error processing order: ' . $e->getMessage();
        }
    }
}

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $error = 'Error fetching user data: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Adidas Clone</title>
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
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="mt-2 text-gray-600">Complete your order</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="md:flex md:space-x-8">
            <!-- Checkout Form -->
            <div class="md:w-2/3">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Shipping Information</h2>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                            <input type="text" id="address" name="address" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" id="city" name="city" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State</label>
                                <input type="text" id="state" name="state" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="zip" class="block text-sm font-medium text-gray-700">ZIP Code</label>
                                <input type="text" id="zip" name="zip" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" id="country" name="country" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                        </div>

                        <div class="mt-8">
                            <h2 class="text-xl font-semibold mb-4">Payment Information</h2>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <p class="text-gray-600">
                                    This is a demo site. No actual payment will be processed.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit"
                                    class="w-full bg-black text-white px-6 py-3 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="md:w-1/3">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="flex justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Qty: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                $<?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t border-gray-200 mt-4 pt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="text-gray-900">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-gray-600">Shipping</span>
                            <span class="text-gray-900">Free</span>
                        </div>
                        <div class="flex justify-between mt-2 text-lg font-semibold">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="cart.php" class="block text-center text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Return to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
