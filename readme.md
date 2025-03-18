# S_PayWay PHP Gateway

A lightweight, framework-agnostic PHP client for the S_PayWay payment gateway API. This library makes it easy to integrate S_PayWay payments into any PHP application.

## Features

- Simple and intuitive API
- Framework-agnostic (works with any PHP framework)
- Minimal dependencies
- Handles server timestamp synchronization automatically
- Securely generates signatures for API requests
- Built-in debugging capabilities

## Requirements

- PHP 5.4 or higher (compatible with PHP 8.3+)
- cURL extension
- JSON extension
- hash extension

## Installation

### Via Composer (Recommended)

```bash
composer require your-vendor/s-payway-php
```

### Manual Installation

1. Download the latest release from GitHub
2. Include the Gateway class in your project:

```php
require_once 'path/to/src/S_PayWay/Gateway.php';
```

## Quick Start

```php
// Import the Gateway class
use S_PayWay\Gateway;

// Initialize the gateway with your credentials
$gateway = new Gateway(
    'your_merchant_id',
    'your_private_key',
    'your_api_key'
);

// Set up your payment parameters
$params = [
    'goods' => [
        'reference_id'  => 'ORD-' . uniqid(),
        'name'          => 'Mobile Legends Bang Bang Diamonds',
        'description'   => 'Instant Recharge',
        'quantity'      => (float) 25,
        'unit_price'    => (double) 1.00,
    ],
    'customer' => [
        'id'        => 'Premium_user',
        'fullname'  => 'John Kh',
        'email'     => 'john.kh@example.com'
    ],
    'paid_url' => 'https://yourwebsite.com/success.php',
    'cancel_url' => 'https://yourwebsite.com/cancel.php',
    'config'        => [
        "timeout"   => 2,
        'version'   => '2.0.0',
    ],
];

// Process the payment
$result = $gateway->processPayment($params);

// Check the result
if ($result['success']) {
    // Redirect to payment page
    header('Location: ' . $result['payment_url']);
    exit;
} else {
    // Handle error
    echo 'Payment failed: ' . $result['message'];
}
```

## Framework Compatibility

This library works with all popular PHP frameworks including:

| Framework | Compatible Versions |
|-----------|---------------------|
| Laravel   | 5.x - 12.x (latest) |
| Symfony   | 3.x - 6.x (latest)  |
| CodeIgniter | 3.x - 4.x (latest) |
| CakePHP   | 3.x - 5.x (latest)  |
| Yii       | 1.x - 2.x (latest)  |
| Laminas/Zend | All versions     |
| Slim      | 3.x - 4.x (latest)  |
| WordPress | 5.x and above       |
| Magento   | 1.x - 2.x (latest)  |
| Drupal    | 7.x - 10.x (latest) |
| PrestaShop | 1.6.x - 8.x (latest) |
| OpenCart  | 2.x - 4.x (latest)  |
| Custom PHP Projects | All       |

## Framework Integration Examples

### Laravel

```php
// In a controller
public function checkout(Request $request)
{
    $gateway = new \S_PayWay\Gateway(
        config('services.s_payway.merchant_id'),
        config('services.s_payway.private_key'),
        config('services.s_payway.api_key')
    );
    
    $params = [
        'goods' => [
            'reference_id' => 'ORD-' . $request->order_id,
            // Other params...
        ],
        // Rest of the params...
    ];
    
    $result = $gateway->processPayment($params);
    
    if ($result['success']) {
        return redirect($result['payment_url']);
    } else {
        return back()->with('error', $result['message']);
    }
}
```

### WordPress

```php
function process_s_payway_payment() {
    // Include the Gateway class
    require_once plugin_dir_path(__FILE__) . 'includes/class-s-payway-gateway.php';
    
    $gateway = new \S_PayWay\Gateway(
        get_option('s_payway_merchant_id'),
        get_option('s_payway_private_key'),
        get_option('s_payway_api_key')
    );
    
    // Rest of the code...
}
```

## Advanced Usage

### Enabling Debug Mode

```php
$gateway->setDebug(true);
```

### Callback Handling

Implement a callback handler to receive payment notifications:

```php
// callback.php
$rawData = file_get_contents('php://input');
$headers = getallheaders();

$signature = $headers['S-PayWay-Signature'] ?? '';
$timestamp = $headers['S-PayWay-Timestamp'] ?? '';
$merchantId = $headers['S-PayWay-Merchant-ID'] ?? '';

// Verify the signature
$expectedSignature = hash_hmac(
    'sha256', 
    $timestamp . ':' . $rawData, 
    'your_private_key'
);

if (hash_equals($expectedSignature, $signature)) {
    // Process the callback
    $callbackData = json_decode($rawData, true);
    
    // Update order status in your database
    // ...
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
}
```

## Security

- Never expose your private key or API key in client-side code
- Always validate and sanitize user inputs
- Use HTTPS for all API communications
- Store API credentials securely (e.g., in environment variables)
- Implement proper callback signature verification

## License

MIT License

## Credits

Developed and maintained by S-SERVER Pvt LTD

## Support

For support, please open an issue on GitHub or contact [contact@sophada.com](mailto:contact@sophada.com).
