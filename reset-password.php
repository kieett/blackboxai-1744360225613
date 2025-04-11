<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$error = '';
$valid_token = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Verify token
if ($token) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email 
            FROM users 
            WHERE reset_token = ? AND reset_token_expires > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $valid_token = true;
        } else {
            $error = 'Invalid or expired reset link';
        }
    } catch(PDOException $e) {
        $error = 'Error verifying reset token';
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please enter both passwords';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            // Update password and clear reset token
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, reset_token = NULL, reset_token_expires = NULL 
                WHERE reset_token = ?
            ");
            $stmt->execute([$hashed_password, $token]);
            
            // Send confirmation email
            $subject = "Password Reset Successful";
            $message_body = "
                <h2>Password Reset Successful</h2>
                <p>Your password has been successfully reset.</p>
                <p>If you did not make this change, please contact support immediately.</p>
            ";
            sendEmail($user['email'], $subject, $message_body);
            
            $message = 'Your password has been reset successfully. You can now login with your new password.';
        } catch(PDOException $e) {
            $error = 'Error resetting password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Adidas Clone</title>
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

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Reset Password</h2>
                <p class="mt-2 text-gray-600">Enter your new password</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $message; ?>
                    <div class="mt-4 text-center">
                        <a href="login.php" class="inline-block bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                            Go to Login
                        </a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                    <?php if (!$valid_token): ?>
                        <div class="mt-4 text-center">
                            <a href="forgot-password.php" class="inline-block bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                                Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token && !$message): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="password" name="password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!$valid_token && !$message): ?>
                <div class="text-center">
                    <p class="text-gray-600 mb-4">The reset link is invalid or has expired.</p>
                    <a href="forgot-password.php" class="text-black hover:text-gray-800">
                        Request a new reset link
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Password Strength Indicator -->
    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            
            // Contains number
            if (/\d/.test(password)) strength++;
            
            // Contains letter
            if (/[a-zA-Z]/.test(password)) strength++;
            
            // Contains special character
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        passwordInput?.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            let color = 'red';
            
            if (strength >= 4) color = 'green';
            else if (strength >= 2) color = 'orange';
            
            this.style.borderColor = color;
        });
        
        confirmInput?.addEventListener('input', function() {
            const match = this.value === passwordInput.value;
            this.style.borderColor = match ? 'green' : 'red';
        });
    </script>
</body>
</html>
