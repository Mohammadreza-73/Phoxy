<?php

namespace CachingProxy;

class ProxyServer
{
    public function __construct(
        private FileCache $cache = new FileCache()
    ) {
    }

    public function handleRequest()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $url = $this->getTargetUrl();

        if ($url === null) {
            $this->sendError('Target URL parameter is required', 400);

            return;
        }

        // Check filter URL
        if (config('cache', 'filter_url_status')) {
            if ($this->checkFilterUrl($url) === false) {
                $this->sendError('Domain not allowed', 403);

                return;
            }
        }

        // Check cache first
        $cachedResponse = $this->cache->get($url);

        if ($cachedResponse) {
            $this->sendCachedResponse($cachedResponse);

            return;
        }

        $response = $this->makeRequest($url);

        if ($response['success']) {
            $this->cacheResponse($url, $response);
            $this->sendResponse($response);
        } else {
            $this->sendError($response['error'], $response['status_code'] ?? 500);
        }
    }

    private function getTargetUrl(): ?string
    {
        if ($_GET['url'] !== null) {
            return urldecode($_GET['url']);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if ($input['url'] !== null) {
            return $input['url'];
        }

        return null;
    }

    private function checkFilterUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Allow localhost for development
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }

        if (in_array($host, config('cache', 'blacklist'))) {
            return false;
        }

        return true;
    }

    private function makeRequest(string $url)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => config('cache', 'timeout'),
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
        }

        // Forward headers (excluding some sensitive headers)
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0 && ! in_array($key, ['HTTP_HOST', 'HTTP_COOKIE'])) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($key, 5))));
                $headers[] = "$header: $value";
            }
        }

        if (! empty($headers)) {
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
                'status_code' => 500,
            ];
        }

        $header_size = $info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Check content length
        if (strlen($body) > config('cache', 'max_content_length')) {
            return [
                'success' => false,
                'error' => 'Response too large',
                'status_code' => 413,
            ];
        }

        return [
            'success' => true,
            'status_code' => $info['http_code'],
            'headers' => $headers,
            'body' => $body,
            'content_type' => $info['content_type'],
        ];
    }

    private function cacheResponse($url, $response)
    {
        if ($response['status_code'] === 200) {
            header('X-Proxy-Cache: MISS');

            $this->cache->set($url, $response);
        }
    }

    private function sendCachedResponse($cachedResponse)
    {
        header('X-Proxy-Cache: HIT');
        $this->sendResponse($cachedResponse);
    }

    private function sendResponse(array $response)
    {
        http_response_code($response['status_code']);

        // Forward headers (filtering out some)
        $headers = explode("\r\n", $response['headers']);
        foreach ($headers as $header) {
            if (! empty($header) &&
                ! preg_match('/^(Transfer-Encoding|Content-Length|Connection|Set-Cookie)/i', $header)) {
                header($header);
            }
        }

        echo $response['body'];
    }

    private function sendError($message, $statusCode = 500)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode([
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ]);
    }
}
