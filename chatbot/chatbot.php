<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get the incoming message
$data = json_decode(file_get_contents('php://input'), true);
$message = strtolower(sanitize($data['message'] ?? ''));
$user_id = $data['user_id'] ?? null;

// Initialize response
$response = [
    'message' => '',
    'suggestions' => [],
    'error' => false
];

// Function to log chat interaction
function logChatInteraction($user_id, $message, $response) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chatbot_messages (user_id, message, response) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $message, $response]);
    } catch(PDOException $e) {
        // Log error but don't stop execution
        error_log("Error logging chat: " . $e->getMessage());
    }
}

// Function to get product suggestions
function getProductSuggestions($keyword) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, price 
            FROM products 
            WHERE name LIKE ? OR description LIKE ?
            LIMIT 3
        ");
        $param = "%$keyword%";
        $stmt->execute([$param, $param]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Process the message and generate response
if (!empty($message)) {
    // Common greetings
    if (preg_match('/(hello|hi|hey|good morning|good afternoon|good evening)/i', $message)) {
        $response['message'] = "Hello! Welcome to Adidas. How can I help you today?";
        $response['suggestions'] = [
            "Show me new arrivals",
            "Track my order",
            "Size guide",
            "Contact support"
        ];
    }
    // Product inquiries
    elseif (preg_match('/(shoe|sneaker|running|football|boot)/i', $message)) {
        $products = getProductSuggestions($message);
        if (!empty($products)) {
            $response['message'] = "Here are some products you might like:";
            foreach ($products as $product) {
                $response['message'] .= "\n- {$product['name']} (\$" . number_format($product['price'], 2) . ")";
            }
            $response['suggestions'] = [
                "Show me more products",
                "Filter by price",
                "View size guide"
            ];
        } else {
            $response['message'] = "I can help you find the perfect shoes. What type are you looking for?";
            $response['suggestions'] = [
                "Running shoes",
                "Football boots",
                "Casual sneakers",
                "Training shoes"
            ];
        }
    }
    // Order tracking
    elseif (preg_match('/(track|order|delivery|shipping)/i', $message)) {
        $response['message'] = "To track your order, please enter your order number or visit your account page.";
        $response['suggestions'] = [
            "View my orders",
            "Contact support",
            "Shipping policy"
        ];
    }
    // Size guide
    elseif (preg_match('/(size|fit|measurement)/i', $message)) {
        $response['message'] = "Our size guide can help you find the perfect fit. Would you like to see the size chart for:";
        $response['suggestions'] = [
            "Men's shoes",
            "Women's shoes",
            "Kids' shoes",
            "Clothing"
        ];
    }
    // Returns and refunds
    elseif (preg_match('/(return|refund|exchange)/i', $message)) {
        $response['message'] = "Our return policy allows returns within 30 days of purchase. Would you like to:";
        $response['suggestions'] = [
            "Start a return",
            "Return policy",
            "Track return",
            "Contact support"
        ];
    }
    // Contact support
    elseif (preg_match('/(help|support|contact|speak|talk|agent)/i', $message)) {
        $response['message'] = "Our customer support team is here to help! You can:";
        $response['suggestions'] = [
            "Call us: 1-800-123-4567",
            "Email: support@example.com",
            "Live chat with agent",
            "FAQ"
        ];
    }
    // Promotions and deals
    elseif (preg_match('/(deal|discount|promotion|sale|offer)/i', $message)) {
        $response['message'] = "Check out our current promotions:";
        $response['suggestions'] = [
            "View sale items",
            "Student discount",
            "Newsletter signup",
            "Member benefits"
        ];
    }
    // Clothing recommendations
    elseif (preg_match('/(clothing|outfit|wear|size|sport)/i', $message)) {
        $response['message'] = "Please provide your weight, height, and preferred sport to get clothing recommendations.";
        $response['suggestions'] = [
            "Weight and height",
            "Sport type",
            "Size preferences"
        ];
    }
    // Clothing recommendations
    elseif (preg_match('/(recommend|suggest|advice)/i', $message)) {
        // Extract weight, height, size, and sport from the message
        preg_match('/weight:\s*(\d+)/i', $message, $weight);
        preg_match('/height:\s*(\d+)/i', $message, $height);
        preg_match('/size:\s*([SMLXL]+)/i', $message, $size);
        preg_match('/sport:\s*([a-zA-Z]+)/i', $message, $sport);

        $response['message'] = "Based on your input:";
        if (!empty($weight[1]) && !empty($height[1]) && !empty($sport[1])) {
            $response['message'] .= " For a weight of {$weight[1]} kg and height of {$height[1]} cm, suitable clothing for {$sport[1]} would be recommended.";
        } else {
            $response['message'] .= " Please provide complete information for better suggestions.";
        }
        $response['suggestions'] = [
            "Provide more details",
            "Ask about specific clothing",
            "General inquiries"
        ];
    }
    // Default response
    else {
        $response['message'] = "I'm not sure I understand. How can I help you with:";
        $response['suggestions'] = [
            "Product information",
            "Order tracking",
            "Returns & exchanges",
            "Contact support"
        ];
    }

    // Log the interaction
    logChatInteraction($user_id, $message, $response['message']);
} else {
    $response['error'] = true;
    $response['message'] = "Please enter a message.";
}

// Send response
echo json_encode($response);
?>
