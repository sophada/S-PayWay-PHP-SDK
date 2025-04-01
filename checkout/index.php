<?php
/**
 * @package S-PayWay PHP SDK
 * @author S-SERVER Pvt Ltd.
 * @version 2.0.0
 */

define('MY_ACCESS_GRANTED', true) or defined('MY_ACCESS_GRANTED');
require_once('../config.php'); // Please change this 'config.php' path to a different one

require_once('src/S_PayWay/Gateway.php');
use S_PayWay\Gateway;
function processPayment(){
    $gateway = new Gateway(
        MERCHANT_ID, 
        API_KEY,
        PRIVATE_KEY,
        [
            'timeout' => 45,    // API request timeout in seconds
            'debug' => DEBUG,
            'timestampOffsets' => [0, 500, -500]    // Custom timestamp offsets
        ]
    );
    
    // Alternative configuration using setter methods
    // $gateway->setTimeout(45)->setDebug(true);
    
    $params = [
        'goods'             => [
            'reference_id'  => 'ORD_' . bin2hex(random_bytes(3)),
            'name'          => 'Mobile Legends Bang Bang Diamonds',
            'description'   => 'Instant Recharge',
            'quantity'      => (float) 25,
            'unit_price'    => (double) 1.00,
        ],
        'customer'      => [
            'id'        => 'Premium_user', // Username identity, e.g., Johnkh or User_1688
            'fullname'  => 'John Kh',
            'email'     => 'john.kh@example.com'
        ],
        'paid_url'      => 'https://example.com/payment/success',
        'cancel_url'    => 'https://example.com/payment/cancel',
        'fees'          => [
            'name'      => 'Shipping Fee', 
            'type'      => 'fixed', // or 'percentage'
            'value'     => 0.00     // 'fixed' = set amount, 'percentage' = rate (e.g., 5 = 5% of total)
        ],
        'taxes'         => [
            'name'      => 'VAT',
            'type'      => 'percentage',
            'value'     => 0        // 10% VAT
        ],
        'config'        => [
            "timeout"   => 2,       // Invoice timeout: between 1 and 24 hours. Set to [X] hours (Default: 2 hours).
            'version'   => '2.0.0', // API version
        ],
    ];
    
    try {
        $result = $gateway->createINV($params);
        
        if ($result['success']) {
            // Here you might want to:
            // 1. Log the successful payment initiation
            // 2. Store transaction details in your database
            // 3. Update order status
            
            // Then redirect the customer to the payment page
            header('Location: ' . $result['payment_url']);
            exit;
        } else {
            // Handle payment initiation failure
            // 1. Log the error
            // 2. Notify admin if it's a critical issue
            // 3. Display appropriate message to the customer
            
            echo "Payment could not be processed. Please try again later.";
            
            // Detailed error information (only in debug mode)
            if ($gateway->getDebug()) {
                echo "<pre>";
                print_r($result);
                echo "</pre>";
            }
        }
    } catch (\Exception $e) {
        echo "Error processing payment: " . $e->getMessage();
    }
}

// Run!!!!!!!!!!
processPayment();