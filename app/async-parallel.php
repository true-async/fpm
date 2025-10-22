<?php
header('Content-Type: text/plain; charset=utf-8');

use function Async\spawn;
use function Async\awaitAll;
use function Async\delay;

echo "=== TrueAsync Parallel Requests Demo ===\n\n";

if (!extension_loaded('true_async')) {
    echo "ERROR: async extension is not loaded!\n";
    exit(1);
}

echo "Simulating 5 parallel API calls...\n\n";

$overallStart = microtime(true);

$coroutines = [
    spawn(function() {
        $start = microtime(true);
        delay(1000); // 1 second
        $duration = round((microtime(true) - $start) * 1000);
        return ['name' => 'API-1', 'delay' => 1000, 'duration' => $duration, 'data' => 'Data from API-1'];
    }),
    spawn(function() {
        $start = microtime(true);
        delay(1500); // 1.5 seconds
        $duration = round((microtime(true) - $start) * 1000);
        return ['name' => 'API-2', 'delay' => 1500, 'duration' => $duration, 'data' => 'Data from API-2'];
    }),
    spawn(function() {
        $start = microtime(true);
        delay(800); // 0.8 seconds
        $duration = round((microtime(true) - $start) * 1000);
        return ['name' => 'API-3', 'delay' => 800, 'duration' => $duration, 'data' => 'Data from API-3'];
    }),
    spawn(function() {
        $start = microtime(true);
        delay(1200); // 1.2 seconds
        $duration = round((microtime(true) - $start) * 1000);
        return ['name' => 'API-4', 'delay' => 1200, 'duration' => $duration, 'data' => 'Data from API-4'];
    }),
    spawn(function() {
        $start = microtime(true);
        delay(500); // 0.5 seconds
        $duration = round((microtime(true) - $start) * 1000);
        return ['name' => 'API-5', 'delay' => 500, 'duration' => $duration, 'data' => 'Data from API-5'];
    }),
];

[$results, $exceptions] = awaitAll($coroutines);
$overallDuration = round((microtime(true) - $overallStart) * 1000);

echo "Results:\n";
foreach ($results as $i => $result) {
    echo sprintf(
        "  %d. %s: %s (took %dms, expected ~%dms)\n",
        $i + 1,
        $result['name'],
        $result['data'],
        $result['duration'],
        $result['delay']
    );
}

$totalSequential = array_sum(array_column($results, 'delay'));
echo "\nTotal time: {$overallDuration}ms\n";
echo "Sequential would take: {$totalSequential}ms\n";
echo "Speedup: " . round($totalSequential / $overallDuration, 2) . "x\n";

if (count($exceptions) > 0) {
    echo "\nExceptions:\n";
    foreach ($exceptions as $e) {
        echo "  - " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Parallel execution works!\n";
