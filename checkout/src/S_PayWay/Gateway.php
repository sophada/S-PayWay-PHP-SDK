<?php
namespace S_PayWay;

class Gateway
{
    const API_BASE_URL = 'https://api.s-payway.com';
    const TIMESTAMP_ENDPOINT = '/timestamp/';
    const INVOICE_ENDPOINT = '/v2/invoices98/';
    
    private $merchantId;
    private $privateKey;
    private $apiKey;
    private $timeout = 30;
    private $debug = false;
    private $timestampOffsets = [0, 100, 500, 1000, -100, -500, -1000];
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    
    public function __construct(string $merchantId,  string $apiKey, string $privateKey, array $options = [])
    {
        $this->merchantId = $merchantId;
        $this->apiKey = $apiKey;
        $this->privateKey = $privateKey;

        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            $this->timeout = (int)$options['timeout'];
        }
        
        if (isset($options['debug']) && is_bool($options['debug'])) {
            $this->debug = $options['debug'];
        }
        
        if (isset($options['timestampOffsets']) && is_array($options['timestampOffsets'])) {
            $this->timestampOffsets = $options['timestampOffsets'];
        }
    }
    
    public function createINV(array $params): array
    {
        $baseTimestamp = $this->getServerTimestamp();
        
        $this->validateParams($params);
        
        foreach ($this->timestampOffsets as $offset) {
            $timestamp = $baseTimestamp + $offset;
            $requestId = $this->generateRequestId();

            $requestBody = $this->buildRequestBody($params, $timestamp);
            $jsonData = json_encode($requestBody);
            
            $signature = $this->calculateSignature($timestamp, $jsonData);
            
            $headers = $this->buildHeaders($timestamp, $jsonData, $requestId);
            
            $this->debugOutput("Trying with timestamp $timestamp (offset: {$offset}ms)");
            
            $response = $this->sendRequest($jsonData, $headers);
            
            $this->debugOutput("Response with timestamp $timestamp:<br>" . htmlspecialchars($response['body']));
            
            $result = json_decode($response['body'], true);
            
            if (isset($result['success']) && $result['success']) {
                return [
                    'success' => true,
                    'payment_url' => $result["payment_url"],
                    'debug' => $result
                ];
            }
            
            if (isset($result['error']) && strpos($result['error'], 'timestamp') === false) {
                break;
            }
            
            usleep(100000);
        }
        
        return [
            'success' => false,
            'message' => isset($result['error']) ? $result['error'] : 'Unknown error occurred',
            'debug' => $result ?? []
        ];
    }
    
    private function getServerTimestamp(): int
    {
        $ch = curl_init(self::API_BASE_URL . self::TIMESTAMP_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $timestampData = json_decode($response, true);
        
        if (isset($timestampData['timestamp'])) {
            return $timestampData['timestamp'];
        }
        
        return round(microtime(true) * 1000);
    }
    
    private function validateParams(array $params): void
    {
        $requiredParams = [
            'goods',
            'customer', 
            'paid_url',
            'cancel_url'
        ];
        
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: $param");
            }
        }
        
        if (!is_array($params['goods'])) {
            throw new \InvalidArgumentException("goods must be an array");
        }
        $requiredGoodsDetails = [
            'reference_id',
            'name',
            'description',
            'unit_price'
        ];
        foreach ($requiredGoodsDetails as $detail) {
            if (!isset($params['goods'][$detail])) {
                throw new \InvalidArgumentException("Missing required goods detail: $detail");
            }
        }

        if (!is_array($params['customer'])) {
            throw new \InvalidArgumentException("customer must be an array");
        }
        $requiredCustomerDetails = [
            'id',
            'fullname',
            'email'
        ];
        foreach ($requiredCustomerDetails as $detail) {
            if (!isset($params['customer'][$detail])) {
                throw new \InvalidArgumentException("Missing required customer detail: $detail");
            }
        }
        if (!filter_var($params['paid_url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("paid_url must be a valid URL");
        }
        
        if (!filter_var($params['cancel_url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("cancel_url must be a valid URL");
        }
    }
    private function buildRequestBody(array $params, int $timestamp): array
    {
        $requestBody = $params;
        $requestBody['timestamp'] = $timestamp;
        return $requestBody;
    }
    
    private function calculateSignature(int $timestamp, string $jsonData): string
    {
        return hash_hmac(
            'sha256', 
            $this->merchantId . ':' . $timestamp . ':' . $jsonData, 
            $this->privateKey
        );
    }
    
    private function buildHeaders(int $timestamp, string $jsonData, string $requestId): array
    {
        $signature = $this->calculateSignature($timestamp, $jsonData);
        
        return [
            "Content-Type: application/json",
            "Content-Length: " . strlen($jsonData),
            "S-PayWay-Merchant-ID: " . $this->merchantId,
            "S-PayWay-Timestamp: " . $timestamp,
            "S-PayWay-Signature: " . $signature,
            "S-PayWay-API-KEY: " . $this->apiKey,
            "S-PayWay-Request-ID: " . $requestId,
            "User-Agent: " . $this->userAgent,
            "Accept: application/json, text/plain, */*"
        ];
    }
    
    private function sendRequest(string $jsonData, array $headers): array
    {
        $ch = curl_init(self::API_BASE_URL . self::INVOICE_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->debugOutput("cURL Error: $error");
        }
        
        return [
            'body' => $body,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    private function generateRequestId(): string
    {
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } else {
            return md5(uniqid(mt_rand(), true));
        }
    }
    
    private function debugOutput(string $message): void
    {
        if ($this->debug) {
            echo "<pre>$message</pre>";
        }
    }
    
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
    
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
    
    public function setTimestampOffsets(array $offsets): self
    {
        $this->timestampOffsets = $offsets;
        return $this;
    }
    
    public function getDebug(): bool
    {
        return $this->debug;
    }
}