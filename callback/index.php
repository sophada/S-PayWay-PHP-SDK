<?php
/**
 * S-PayWay callback Handler
 * 
 * This file should be placed on your server to receive and verify payment notifications
 * Never make this URL of this path public.
 * from S_PayWay. Set this URL in your merchant dashboard -> API Management as your callback URL.
 * 
 * URL Example: https://yourwebsite.com/callbacks/s_payway_callback.php
 */

// Replace with your S_PayWay merchant credentials
define('MERCHANT_ID', 'your_merchant_id');
define('PRIVATE_KEY', 'your_private_key'); // Keep this secure and never expose it

$rawPostData = file_get_contents('php://input');

// Log incoming callback (optional)
file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Received callback\n", FILE_APPEND);

$receivedSignature = $_SERVER['HTTP_S_PAYWAY_SIGNATURE'] ?? '';
$receivedTimestamp = $_SERVER['HTTP_S_PAYWAY_TIMESTAMP'] ?? '';
$expectedSignature = hash_hmac('sha256', $receivedTimestamp . ':' . $rawPostData, PRIVATE_KEY);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Invalid signature\n", FILE_APPEND);
    exit;
}

$callbackData = json_decode($rawPostData, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($callbackData['event']) || !isset($callbackData['data'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid callback data']);
    file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Invalid callback data\n", FILE_APPEND);
    exit;
}

switch ($callbackData['event']) {
    case 'payment.completed':
        // Payment was successful
        $paymentData = $callbackData['data'];
        $invoiceId = $paymentData['invoice_id'] ?? '';
        $referenceId = $paymentData['reference_id'] ?? '';
        $amount = $paymentData['payment']['value'] ?? 0;
        $currency = $paymentData['payment']['currency'] ?? '';
        // TODO: Update your database to mark the order as paid
        // updateOrderStatus($referenceId, 'paid');
        
        // Log successful payment
        file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Payment completed for order: $referenceId, Amount: $amount $currency\n", FILE_APPEND);
        break;
    default:
        // Unknown event type
        file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Unknown event type: {$callbackData['event']}\n", FILE_APPEND);
        break;
}

// Respond with success to acknowledge receipt of the callback
http_response_code(200);
echo json_encode(['status' => 'success']);

/**
 * Example function to update order status in your database
 * Customize this to work with your database structure
 */
function updateOrderStatus($referenceId, $status, $reason = '') {
    // Replace with your database connection code
    // $db = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
    
    // Example query to update order status
    // $stmt = $db->prepare("UPDATE orders SET status = ?, status_reason = ?, updated_at = NOW() WHERE reference_id = ?");
    // $stmt->execute([$status, $reason, $referenceId]);
}