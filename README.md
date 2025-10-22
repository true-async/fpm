# TrueAsync PHP with PHP-FPM and Nginx

This configuration allows you to run TrueAsync PHP in FPM mode with Nginx web server.

## Files

- `Dockerfile` - main Dockerfile for building PHP-FPM
- `nginx.conf` - Nginx configuration
- `php-fpm.conf` - main PHP-FPM configuration
- `www.conf` - PHP-FPM pool configuration
- `supervisord.conf` - supervisor configuration for process management
- `docker-compose.yml` - Docker Compose file for easy deployment

## Quick Start

### Option 1: Docker Compose (recommended)

```bash
# Create directory for your application
mkdir -p app
echo '<?php phpinfo(); ?>' > app/index.php

# Start the container
docker-compose up --build

# Open in browser: http://localhost:8080
```

### Option 2: Plain Docker

```bash
# Build the image
docker build -t trueasync-fpm .

# Run the container
docker run -d -p 8080:80 --name trueasync-fpm trueasync-fpm

# Open in browser: http://localhost:8080
```

## Testing

The container includes several test files:

1. **http://localhost:8080/** - Landing page with examples
2. **http://localhost:8080/phpinfo.php** - Full PHPInfo
3. **http://localhost:8080/async-test.php** - Basic TrueAsync tests
4. **http://localhost:8080/async-parallel.php** - Parallel execution demo
5. **http://localhost:8080/async-sleep.php** - Async sleep demo
6. **http://localhost:8080/async-scraper.php** - Web scraping demo

Or using curl:

```bash
# Check phpinfo
curl http://localhost:8080/phpinfo.php

# Test TrueAsync
curl http://localhost:8080/async-test.php
```

## TrueAsync Code Examples

Create a file `app/my-async-test.php`:

```php
<?php

use function Async\spawn;
use function Async\await;
use function Async\awaitAll;
use function Async\delay;

echo "Starting async operations...\n";

// Example 1: Simple spawn and await
$coroutine = spawn(function() {
    delay(1000); // 1 second
    return "Task completed!";
});

$result = await($coroutine);
echo "$result\n";

// Example 2: Parallel execution
$coroutines = [
    spawn(fn() => delay(1000) ?? "Task 1"),
    spawn(fn() => delay(1000) ?? "Task 2"),
    spawn(fn() => delay(1000) ?? "Task 3"),
];

[$results, $exceptions] = awaitAll($coroutines);
print_r($results);

echo "All done!\n";
```

Test it: http://localhost:8080/my-async-test.php

## Configuration

### PHP-FPM Pool (www.conf)

Main process parameters:

```ini
pm = dynamic
pm.max_children = 50        # Maximum processes
pm.start_servers = 5        # Starting number
pm.min_spare_servers = 5    # Minimum idle processes
pm.max_spare_servers = 35   # Maximum idle processes
```

### Nginx

Configuration is located in `nginx.conf`. Main settings:

- Document root: `/var/www/html`
- Socket: `/run/php-fpm/php-fpm.sock`
- Timeouts increased to 300 seconds for async operations

### PHP Configuration

Add your custom settings in `php.ini`:

```bash
# In docker-compose.yml, uncomment:
volumes:
  - ./custom-php.ini:/etc/php.d/custom.ini
```

Example `custom-php.ini`:

```ini
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
```

## Logs

View logs:

```bash
# All logs
docker-compose logs -f

# PHP-FPM only
docker exec -it trueasync-fpm tail -f /var/log/php-fpm/error.log

# Nginx only
docker exec -it trueasync-fpm tail -f /var/log/nginx/error.log
```

## Container Access

```bash
# Bash in container
docker exec -it trueasync-fpm bash

# Check PHP version
docker exec -it trueasync-fpm php -v

# Check extensions
docker exec -it trueasync-fpm php -m | grep async
```

## Production Settings

For production environments, it's recommended to:

1. **Disable error display** (custom-php.ini):
```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
```

2. **Increase OPcache**:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
```

3. **Configure process limits** in `www.conf`:
```ini
pm.max_children = 100
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 50
```

4. **SSL/TLS** - add reverse proxy or modify nginx.conf

## Troubleshooting

### PHP-FPM not starting

```bash
# Check logs
docker logs trueasync-fpm

# Check configuration
docker exec -it trueasync-fpm php-fpm -t
```

### Nginx 502 Bad Gateway

```bash
# Check if PHP-FPM is running
docker exec -it trueasync-fpm ps aux | grep php-fpm

# Check socket
docker exec -it trueasync-fpm ls -la /run/php-fpm/
```

### Permission issues

```bash
# Check file ownership
docker exec -it trueasync-fpm ls -la /var/www/html

# Fix if needed
docker exec -it trueasync-fpm chown -R www-data:www-data /var/www/html
```

## Stop and Cleanup

```bash
# Stop
docker-compose down

# Stop and remove volumes
docker-compose down -v

# Full cleanup
docker-compose down -v --rmi all
```

## Links

- [TrueAsync GitHub](https://github.com/true-async/php-src)
- [TrueAsync Extension](https://github.com/true-async/php-async)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP-FPM Documentation](https://www.php.net/manual/en/install.fpm.php)
