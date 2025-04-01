<?php
if (!defined("MY_ACCESS_GRANTED")) {
    http_response_code(403);
    exit('<div style="display:flex;justify-content:center;align-items:center;height:100vh;margin:0;font-family:Arial,sans-serif;background:#f8d7da;"> <div style="text-align:center;padding:40px;width:90%;max-width:500px;background:#fff;border-left:5px solid #dc3545;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,.15);"><svg viewBox="0 0 24 24" style="width:48px;height:48px;margin-bottom:15px;"><path fill="#dc3545" d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M13,17h-2v-2h2V17z M13,13h-2V7h2V13z"/></svg><h1 style="font-size:28px;font-weight:bold;color:#721c24;margin:0 0 15px;">Access Denied</h1><p style="color:#721c24;margin:0;line-height:1.5;">You do not have permission to access this secure payment resource.</p></div></div>');
}

// S-PayWay API Credentials
define('MERCHANT_ID', 'your_merchant_id');
define('API_KEY', 'your_api_key');
define('PRIVATE_KEY', 'your_private_key');

//Save the debug logs as callback_log.txt
define('DEBUG', false); // Don't set to true when running the service as public