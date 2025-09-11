<?php
// config.php - Configuration settings
class ProxyConfig {
    const CACHE_DIR = __DIR__ . '/cache/';
    const CACHE_TTL = 3600; // 1 hour in seconds
    const ALLOWED_DOMAINS = [
        'api.example.com',
        'jsonplaceholder.typicode.com',
        'httpbin.org'
    ];
    const MAX_CONTENT_LENGTH = 10485760; // 10MB
    const TIMEOUT = 30; // seconds
}

// cache.php - Cache management
class ProxyCache {
    private $cacheDir;
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?: ProxyConfig::CACHE_DIR;
        $this->ensureCacheDir();
    }
    
    private function ensureCacheDir() {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function getCacheKey($url) {
        return md5($url);
    }
    
    public function getCachePath($key) {
        return $this->cacheDir . $key . '.cache';
    }
    
    public function get($url) {
        $key = $this->getCacheKey($url);
        $cacheFile = $this->getCachePath($key);
        
        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            
            // Check if cache is still valid
            if (time() - $data['timestamp'] < ProxyConfig::CACHE_TTL) {
                return $data['content'];
            }
            
            // Remove expired cache
            unlink($cacheFile);
        }
        
        return null;
    }
    
    public function set($url, $content) {
        $key = $this->getCacheKey($url);
        $cacheFile = $this->getCachePath($key);
        
        $data = [
            'timestamp' => time(),
            'content' => $content,
            'url' => $url
        ];
        
        file_put_contents($cacheFile, serialize($data));
        return true;
    }
    
    public function clearExpired() {
        $files = glob($this->cacheDir . '*.cache');
        $now = time();
        $cleared = 0;
        
        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));
            if ($now - $data['timestamp'] >= ProxyConfig::CACHE_TTL) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
}

// proxy.php - Main proxy functionality
class ProxyServer {
    private $cache;
    private $allowedDomains;
    
    public function __construct() {
        $this->cache = new ProxyCache();
        $this->allowedDomains = ProxyConfig::ALLOWED_DOMAINS;
    }
    
    public function handleRequest() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        $url = $this->getTargetUrl();
        
        if (!$url) {
            $this->sendError('Target URL parameter is required', 400);
            return;
        }
        
        if (!$this->isUrlAllowed($url)) {
            $this->sendError('Domain not allowed', 403);
            return;
        }
        
        // Check cache first
        $cachedResponse = $this->cache->get($url);
        if ($cachedResponse) {
            $this->sendCachedResponse($cachedResponse);
            return;
        }
        
        // Make the request
        $response = $this->makeRequest($url);
        
        if ($response['success']) {
            $this->cacheResponse($url, $response);
            $this->sendResponse($response);
        } else {
            $this->sendError($response['error'], $response['status_code'] ?? 500);
        }
    }
    
    private function getTargetUrl() {
        if (isset($_GET['url'])) {
            return urldecode($_GET['url']);
        }
        
        // Also check POST data for URL
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['url'])) {
            return $input['url'];
        }
        
        return null;
    }
    
    private function isUrlAllowed($url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        // Allow localhost for development
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }
        
        foreach ($this->allowedDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => ProxyConfig::TIMEOUT,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'PHP Proxy/1.0',
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
        }
        
        // Forward headers (excluding some sensitive headers)
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0 && !in_array($key, ['HTTP_HOST', 'HTTP_COOKIE'])) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($key, 5))));
                $headers[] = "$header: $value";
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => $error,
                'status_code' => 500
            ];
        }
        
        $header_size = $info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        // Check content length
        if (strlen($body) > ProxyConfig::MAX_CONTENT_LENGTH) {
            return [
                'success' => false,
                'error' => 'Response too large',
                'status_code' => 413
            ];
        }
        
        return [
            'success' => true,
            'status_code' => $info['http_code'],
            'headers' => $headers,
            'body' => $body,
            'content_type' => $info['content_type']
        ];
    }
    
    private function cacheResponse($url, $response) {
        if ($response['status_code'] === 200 && 
            strpos($response['content_type'], 'application/json') !== false) {
            $this->cache->set($url, $response);
        }
    }
    
    private function sendCachedResponse($cachedResponse) {
        header('X-Proxy-Cache: HIT');
        $this->sendResponse($cachedResponse);
    }
    
    private function sendResponse($response) {
        http_response_code($response['status_code']);
        
        // Forward headers (filtering out some)
        $headers = explode("\r\n", $response['headers']);
        foreach ($headers as $header) {
            if (!empty($header) && 
                !preg_match('/^(Transfer-Encoding|Content-Length|Connection|Set-Cookie)/i', $header)) {
                header($header);
            }
        }
        
        header('X-Proxy-Cache: MISS');
        echo $response['body'];
    }
    
    private function sendError($message, $statusCode = 500) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        echo json_encode([
            'error' => true,
            'message' => $message,
            'status' => $statusCode
        ]);
    }
}

// index.php - Main entry point
require_once 'config.php';
require_once 'cache.php';
require_once 'proxy.php';

// Handle request
$proxy = new ProxyServer();
$proxy->handleRequest();