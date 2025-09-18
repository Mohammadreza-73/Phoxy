# PHP Proxy Server with Caching

A lightweight, flexible PHP proxy server with intelligent caching capabilities for web content. Designed to improve website performance by caching HTML and JSON resources.

> [!IMPORTANT]  
> This proxy server is designed for development and moderate production use. For high-traffic applications, consider additional scaling strategies and monitoring.

## Features

- 🚀 High Performance: Caches responses to reduce server load and improve response times
- 📦 Content-Type Aware: Intelligently caches HTML and JSON
- ⚡ Configurable TTL: Different cache durations for different content types
- 🔒 Security: Domain blacklisting and request validation
- 📊 Cache Statistics: Built-in monitoring and statistics generation
- 🛠️ Easy Configuration: Simple setup and customization

## Supported Content Types

| Content Type | Default TTL |        Description         |
| ------------ | ----------- | -------------------------- |
| HTML         |   1 Hour    | Web pages and HTML content |
| JSON         |   1 Hour    | API responses and data     |

## Installation and Running

### 1. Clone the repository
``` bash
git clone https://github.com/Mohammadreza-73/Caching-Proxy-Server.git
cd Caching-Proxy-Server
```

### 2. Ensure PHP requirements
``` bash
# Check if cURL is enabled
php -m | grep curl
```

### 3. Ensure cache directory permissions
``` bash
chmod 755 cache/
chmod 755 cache/*/
```

### 4. Serve server
``` bash
php -S localhost:8000
```

## Configuration

Edit `src/config/cache.php` to customize the proxy behavior:
``` php
return [
    'ttl' => 3600, // Default cache Time to live
    'timeout' => 30, // Request to cache content timeout
    'max_content_length' => 10485760, // Max response size (10MB)

    'filter_url_status' => true,  // Enable/Disable domain filter
    'blacklist' => [  // Blacklisted domains
        'blocked-domain1.com',
        'blocked-domain2.com',
    ],
];
```

## Usage

### Basic GET Request
``` js
// Frontend JavaScript usage
fetch('/proxy.php?url=' + encodeURIComponent('https://example.com/api/data'))
    .then(response => response.json())
    .then(data => console.log(data));
```

### POST Request
``` js
// Sending POST data through proxy
fetch('/proxy.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        url: 'https://api.example.com/submit',
        data: { /* your payload */ }
    })
});
```

### API Reference

#### Query Parameters
| Parameter | Required |               Description             |
| --------- | -------- | ------------------------------------- |
| `url`     | Yes      | The target URL to proxy (URL encoded) |

#### Response Headers
|          Header               |                      Description                    |
| ----------------------------- | --------------------------------------------------- |
| `X-Proxy-Cache`               | `HIT` if served from cache, `MISS` if fetched fresh |
| `Access-Control-Allow-Origin` | `*` (CORS enabled)                                  |

## Security Considerations

1. Domain Blacklisting: Block specific domains
2. Content Length Limits: Prevents large file attacks
3. Timeout Protection: Limits request duration
4. CORS Headers: Proper cross-origin handling
5. Header Filtering: Removes sensitive headers from responses

## Example Use Cases

 - API Aggregation: Combine multiple APIs with caching
 - Content Mirroring: Cache external resources for faster access
 - Cross-Origin Requests: bypass CORS limitations for development
 - Performance Optimization: Reduce load times for external resources
 - Offline Development: Cache responses for development without internet

## File Structure

``` text
caching-proxy-server/
├── cache/               # Cache storage directory
├── logs/                # Store logs in `app.log`
├── src/                 # Source directory
│   ├── config/          # Cache configuration settings
│   ├── helpers/         # Helper functions
│   ├── FileCache.php    # Class to handle cache files
│   └── ProxyServer.php  # Class to manage Proxy Server
├── .editorconfig        # config for editors
└── index.php            # Serve project
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

1. Check the issues page
2. Create a new issue with detailed description

## License

This project is licensed under the MIT License - see the [License File](LICENSE) file for details.