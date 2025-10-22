<?php
header('Content-Type: text/plain; charset=utf-8');

use function Async\spawn;
use function Async\awaitAll;

echo "=== TrueAsync Web Scraper Demo ===\n\n";

if (!extension_loaded('true_async')) {
    echo "ERROR: async extension is not loaded!\n";
    exit(1);
}

// List of websites to scrape
$websites = [
    'https://www.php.net',
    'https://github.com',
    'https://stackoverflow.com',
    'https://www.wikipedia.org',
    'https://www.reddit.com',
];

echo "Fetching and parsing " . count($websites) . " websites in parallel...\n\n";

$overallStart = microtime(true);

// Create coroutines for each website
$coroutines = [];
foreach ($websites as $url) {
    $coroutines[] = spawn(function() use ($url) {
        $start = microtime(true);

        // Use cURL for async HTTP request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TrueAsync-PHP-Scraper/1.0)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        // curl_close() not needed in PHP 8.0+, resources are freed automatically

        $duration = round((microtime(true) - $start) * 1000);

        if ($error || !$html) {
            return [
                'url' => $url,
                'success' => false,
                'error' => $error ?: 'Failed to fetch',
                'duration' => $duration,
                'http_code' => $httpCode,
            ];
        }

        // Parse page title
        $title = 'No title found';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            // Limit title length
            if (strlen($title) > 100) {
                $title = substr($title, 0, 97) . '...';
            }
        }

        // Extract meta description
        $description = '';
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches)) {
            $description = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            if (strlen($description) > 150) {
                $description = substr($description, 0, 147) . '...';
            }
        }

        // Count links
        $linkCount = preg_match_all('/<a[^>]*href=["\'][^"\']*["\'][^>]*>/is', $html, $linkMatches);

        return [
            'url' => $url,
            'success' => true,
            'title' => $title,
            'description' => $description,
            'link_count' => $linkCount,
            'size' => strlen($html),
            'duration' => $duration,
            'http_code' => $httpCode,
        ];
    });
}

// Wait for all coroutines to complete
[$results, $exceptions] = awaitAll($coroutines);
$overallDuration = round((microtime(true) - $overallStart) * 1000);

// Display results
echo str_repeat('=', 80) . "\n";
echo "RESULTS:\n";
echo str_repeat('=', 80) . "\n\n";

foreach ($results as $index => $result) {
    $num = $index + 1;
    echo "[$num] {$result['url']}\n";
    echo str_repeat('-', 80) . "\n";

    if ($result['success']) {
        echo "✓ Status: HTTP {$result['http_code']}\n";
        echo "✓ Title: {$result['title']}\n";
        if ($result['description']) {
            echo "✓ Description: {$result['description']}\n";
        }
        echo "✓ Links found: {$result['link_count']}\n";
        echo "✓ Page size: " . number_format($result['size']) . " bytes\n";
        echo "✓ Fetch time: {$result['duration']}ms\n";
    } else {
        echo "✗ Error: {$result['error']}\n";
        echo "✗ HTTP Code: {$result['http_code']}\n";
        echo "✗ Attempt duration: {$result['duration']}ms\n";
    }

    echo "\n";
}

// Statistics
echo str_repeat('=', 80) . "\n";
echo "STATISTICS:\n";
echo str_repeat('=', 80) . "\n";

$successCount = count(array_filter($results, fn($r) => $r['success']));
$totalBytes = array_sum(array_column($results, 'size'));
$avgDuration = round(array_sum(array_column($results, 'duration')) / count($results));

echo "Total websites: " . count($websites) . "\n";
echo "Successful: $successCount\n";
echo "Failed: " . (count($websites) - $successCount) . "\n";
echo "Total data downloaded: " . number_format($totalBytes) . " bytes\n";
echo "Average fetch time: {$avgDuration}ms\n";
echo "\n";
echo "Total parallel execution time: {$overallDuration}ms\n";
echo "Sequential would take: ~" . array_sum(array_column($results, 'duration')) . "ms\n";
echo "Speedup: " . round(array_sum(array_column($results, 'duration')) / $overallDuration, 2) . "x\n";

if (count($exceptions) > 0) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "EXCEPTIONS:\n";
    echo str_repeat('=', 80) . "\n";
    foreach ($exceptions as $i => $e) {
        echo "[$i] " . $e->getMessage() . "\n";
    }
}

echo "\n=== Scraping completed ===\n";
