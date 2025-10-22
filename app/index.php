<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueAsync PHP-FPM Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .links a {
            display: inline-block;
            margin: 10px 10px 0 0;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .links a:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🚀 TrueAsync PHP-FPM + Nginx</h1>

    <div class="card">
        <h2>PHP Info</h2>
        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
        <p><strong>Server API:</strong> <?= php_sapi_name() ?></p>
        <p><strong>Zend Thread Safety:</strong> <?= ZEND_THREAD_SAFE ? 'Enabled' : 'Disabled' ?></p>
    </div>

    <div class="card">
        <h2>TrueAsync Extension</h2>
        <?php if (extension_loaded('true_async')): ?>
            <p class="success">✓ TrueAsync extension is loaded!</p>
            <pre><?php
                $reflection = new ReflectionExtension('true_async');
                echo "Version: " . $reflection->getVersion() . "\n";
                echo "\nFunctions:\n";
                $functions = $reflection->getFunctions();
                foreach (array_slice($functions, 0, 10) as $func) {
                    echo "  - " . $func->name . "\n";
                }
                if (count($functions) > 10) {
                    echo "  ... and " . (count($functions) - 10) . " more\n";
                }
            ?></pre>
        <?php else: ?>
            <p class="error">✗ TrueAsync extension is NOT loaded</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Examples</h2>
        <div class="links">
            <a href="phpinfo.php">Full PHPInfo</a>
            <a href="async-test.php">Async Test</a>
            <a href="async-parallel.php">Parallel Requests</a>
            <a href="async-sleep.php">Async Sleep Demo</a>
            <a href="async-scraper.php">Web Scraper Demo</a>
        </div>
    </div>

    <div class="card">
        <h2>System Info</h2>
        <pre><?php
            echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
            echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
            echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
            echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
        ?></pre>
    </div>
</body>
</html>
