<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save reset token
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET reset_token = ?, reset_token_expires = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$token, $expires, $user['id']]);
                
                // Send reset email
                $reset_link = "http://{$_SERVER['HTTP_HOST']}/reset-password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message_body = "
                    <h2>Password Reset Request</h2>
                    <p>Dear {$user['first_name']},</p>
                    <p>You have requested to reset your password. Click the link below to reset your password:</p>
                    <p><a href='$reset_link'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                
                if (sendEmail($email, $subject, $message_body)) {
                    $message = 'Password reset instructions have been sent to your email';
                } else {
                    $error = 'Error sending email. Please try again.';
                }
            } else {
                // Don't reveal if email exists or not
                $message = 'If your email is registered, you will receive password reset instructions';
            }
        } catch(PDOException $e) {
            $error = 'Error processing request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Adidas Clone</title>
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
                <div class="flex items-center">
                    <a href="login.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Forgot Password</h2>
                <p class="mt-2 text-gray-600">Enter your email to reset your password</p>
            </div>

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

            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Send Reset Link
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="font-medium text-black hover:text-gray-800">
                        Login here
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
