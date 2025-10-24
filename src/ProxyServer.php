<?php

namespace Phoxy;

use InvalidArgumentException;
use RuntimeException;

class ProxyServer
{
    public function __construct(
        private FileCache $cache = new FileCache()
    ) {
    }

    public function handleRequest(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(Response::HTTP_OK);
            exit;
        }

        $url = $this->getTargetUrl();

        if ($url === null) {
            $this->sendError('Target URL parameter is required', Response::HTTP_BAD_REQUEST);

            return;
        }

        // Check filter URL
        if (config('cache', 'filter_url_enable')) {
            if ($this->checkFilterUrl($url) === false) {
                $this->sendError('Domain not allowed', Response::HTTP_FORBIDDEN);

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
            $this->sendError($response['error'], $response['status_code'] ?? Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getTargetUrl(): ?string
    {
        if ($_GET['url'] !== null) {
            return filter_var(urldecode($_GET['url']), FILTER_VALIDATE_URL);
        }

        $requestBody = file_get_contents('php://input');

        if ($requestBody === false) {
            throw new RuntimeException("Failed to read `POST` request body");
        }

        $input = json_decode($requestBody, true);

        if ($input['url'] !== null) {
            return filter_var($input['url'], FILTER_VALIDATE_URL);
        }

        return null;
    }

    private function checkFilterUrl(string $url): bool
    {
        $url = $this->normalizeUrl($url);
        $host = parse_url($url, PHP_URL_HOST);

        // Allow localhost for development
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }

        if (in_array($host, config('cache', 'filter_url_list'))) {
            return false;
        }

        return true;
    }

    private function normalizeUrl(string $url): string
    {
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        return 'https://' . $url;
    }

    /**
     * @return array<mixed>
     */
    private function makeRequest(string $url): array
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

            $requestBody = file_get_contents('php://input');

            if ($requestBody === false) {
                throw new RuntimeException("Failed to read `POST` request body");
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
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
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }

        $header_size = $info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Check content length
        if (strlen($body) > config('cache', 'file.max_content_length')) {
            return [
                'success' => false,
                'error' => Response::$statusTexts[Response::HTTP_REQUEST_ENTITY_TOO_LARGE],
                'status_code' => Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
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

    /**
     * @param array<mixed> $response
     */
    private function cacheResponse(string $url, array $response): void
    {
        if ($response['status_code'] === Response::HTTP_OK) {
            header('X-Proxy-Cache: MISS');

            $this->cache->set($url, $response);
        }
    }

    /**
     * @param array<mixed> $cachedResponse
     */
    private function sendCachedResponse(array $cachedResponse): void
    {
        header('X-Proxy-Cache: HIT');
        $this->sendResponse($cachedResponse);
    }

    /**
     * @param array<mixed> $response
     */
    private function sendResponse(array $response): void
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

    private function sendError(string $message, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $data = json_encode([
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ], JSON_PRETTY_PRINT);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        echo $data;
    }
}
