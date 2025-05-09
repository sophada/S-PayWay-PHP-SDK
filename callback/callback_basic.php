<?php
/**
 * S-PayWay callback Handler
 * 
 * This file should be placed on your server to receive and verify payment notifications
 * Never make this URL of this path public.
 * Set this URL in your merchant dashboard -> API Management as your callback URL.
 * 
 * URL Example: https://yourwebsite.com/callbacks/s_payway_callback.php
 */

 define('MY_ACCESS_GRANTED', true) or defined('MY_ACCESS_GRANTED');
 require_once('../config.php'); // Please change this 'config.php' path to a different one

$rawPostData = file_get_contents('php://input');

// Log incoming callback
if (defined('DEBUG') && DEBUG){
    file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Received callback\n", FILE_APPEND);
}
$receivedSignature = $_SERVER['HTTP_S_PAYWAY_SIGNATURE'] ?? '';
$receivedTimestamp = $_SERVER['HTTP_S_PAYWAY_TIMESTAMP'] ?? '';
$expectedSignature = hash_hmac('sha256', $receivedTimestamp . ':' . $rawPostData, PRIVATE_KEY);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    $errorMessage = "Invalid signature";
    if (defined('DEBUG') && DEBUG){
        file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - $errorMessage\n", FILE_APPEND);
    }
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    exit();
}

$callbackData = json_decode($rawPostData, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($callbackData['event']) || !isset($callbackData['data'])) {
    $errorMessage = "Invalid callback data";
    if (defined('DEBUG') && DEBUG){
        file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - $errorMessage\n", FILE_APPEND);
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    exit();
}

switch ($callbackData['event']) {
    case 'test.connection':
        // Don't change this test connection's response. If verification fails, you won't be able to enable the API !!!
        http_response_code(200);
        echo json_encode([
            'data' => [
                'ip' => getServerIP(),
                'ipv6' => getServerIPv6()
            ],
            'message' => 'Credentials verified successfully. Please add your Hosting IP address to the API Security Guard page.',
            'status' => 'success'
        ]);
        exit();
        break;
    case 'payment.completed':
        // Payment was successful
        $paymentData = $callbackData['data'];
        $invoiceId = $paymentData['invoice_id'] ?? ' ';
        $invoiceHash = $paymentData['invoice_hash'] ?? ' ';
        $totalUSD = $paymentData['total_usd'] ?? 0;
        $referenceId = $paymentData['goods']['reference_id'] ?? ' ';
        $txnId = $paymentData['payment']['id'] ?? ' ';
        $txnHash = $paymentData['payment']['hash'] ?? ' ';
        $amount = $paymentData['payment']['value'] ?? 0;
        $currency = $paymentData['payment']['currency'] ?? ' ';
        $paidMethod = $paymentData['payment']['method'] ?? ' ';
        $paidCountry = $paymentData['payment']['country'] ?? ' ';
        $paidIP = $paymentData['payment']['ip'] ?? ' ';
        $paidTimestamp = $paymentData['payment']['timestamp'] ?? '883612800';
        // TODO: Update your database to mark the order as paid
        # updateOrderStatus($referenceId, 'paid'); // || You can also use the Invoice ID or Invoice hash to verify if they were saved when the request for the checkout link was made.
        
        // Log successful payment
        if (defined('DEBUG') && DEBUG){
            file_put_contents('callback_log.txt', date('Y-m-d H:i:s') . " - Payment completed for order: $referenceId, Amount: $amount $currency\n", FILE_APPEND);
        }
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
    # $db = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
    
    // Example query to update order status
    # $stmt = $db->prepare("UPDATE orders SET status = ?, status_reason = ?, updated_at = NOW() WHERE reference_id = ?");
    # $stmt->execute([$status, $reason, $referenceId]);
}
function getServerIP() { $ch = curl_init('https://api.ipify.org?format=json'); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); $json = json_decode(curl_exec($ch)); curl_close($ch); return isset($json->ip) ? $json->ip : '0'; }
function getServerIPv6() { $ch = curl_init('https://api64.ipify.org?format=json'); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); $json = json_decode(curl_exec($ch)); curl_close($ch); return isset($json->ip) ? $json->ip : '0'; }