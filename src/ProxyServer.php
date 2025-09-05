<?php

namespace CachingProxy;

class ProxyServer
{
    public function __construct(
        private FileCache $cache = new FileCache()
    )
    { }

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
            // send error
            return;
        }

        // check filter URL
        if (config('cache', 'filter_url_status')) {
            if ($this->checkFilterUrl($url) === false) {
                // url now allowed erorr 403
                return;
            }
        }

        return $this->cache->get($url);
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

        if (! in_array($host, config('cache', 'whitelist'))) {
            return false;
        }

        return true;
    }
}