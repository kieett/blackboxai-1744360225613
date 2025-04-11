<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'profile.php';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get user's orders
    $stmt = $pdo->prepare("
        SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = 'Error fetching user data: ' . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            if (!empty($current_password)) {
                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception('Current password is incorrect');
                }

                // Validate new password
                if (empty($new_password)) {
                    throw new Exception('New password is required');
                }
                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match');
                }
                if (strlen($new_password) < 8) {
                    throw new Exception('New password must be at least 8 characters long');
                }

                // Update user with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, password = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $hashed_password, $user_id]);
            } else {
                // Update user without changing password
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $user_id]);
            }

            $message = 'Profile updated successfully';
            
            // Update session name
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Adidas Clone</title>
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
                    <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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

        <div class="md:flex md:space-x-8">
            <!-- Profile Section -->
            <div class="md:w-1/2">
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold mb-6">Profile Information</h2>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h3 class="text-lg font-medium mb-4">Change Password</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="md:w-1/2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-6">Order History</h2>
                    
                    <?php if (empty($orders)): ?>
                        <p class="text-gray-500">No orders found.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($orders as $order): ?>
                                <div class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-medium">Order #<?php echo $order['id']; ?></h3>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
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
                                    <div class="text-sm">
                                        <p><?php echo $order['items_count']; ?> items</p>
                                        <p class="font-medium">Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                    </div>
                                    <div class="mt-2">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>"
                                           class="text-black hover:text-gray-800 text-sm font-medium">
                                            View Details <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
