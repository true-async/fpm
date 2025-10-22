<?php
header('Content-Type: text/plain; charset=utf-8');

use function Async\spawn;
use function Async\await;
use function Async\awaitAll;
use function Async\delay;

echo "=== TrueAsync Basic Test ===\n\n";

if (!extension_loaded('true_async')) {
    echo "ERROR: async extension is not loaded!\n";
    exit(1);
}

echo "✓ async extension is loaded\n\n";

// Test 1: Simple spawn and await
echo "Test 1: Simple spawn and await\n";

$start = microtime(true);
$coroutine = spawn(function() {
    delay(500); // 0.5 seconds (milliseconds)
    return "Hello, TrueAsync!";
});

$result = await($coroutine);
$duration = round((microtime(true) - $start) * 1000);

echo "Result: $result\n";
echo "Duration: {$duration}ms\n\n";

// Test 2: Parallel execution
echo "Test 2: Parallel execution\n";

$start = microtime(true);

$coroutines = [
    spawn(function() {
        $start = microtime(true);
        delay(1000);
        $duration = round((microtime(true) - $start) * 1000);
        return "Task 1 completed in {$duration}ms";
    }),
    spawn(function() {
        $start = microtime(true);
        delay(1000);
        $duration = round((microtime(true) - $start) * 1000);
        return "Task 2 completed in {$duration}ms";
    }),
    spawn(function() {
        $start = microtime(true);
        delay(1000);
        $duration = round((microtime(true) - $start) * 1000);
        return "Task 3 completed in {$duration}ms";
    }),
];

[$results, $exceptions] = awaitAll($coroutines);
$totalDuration = round((microtime(true) - $start) * 1000);

echo "Results:\n";
foreach ($results as $result) {
    echo "  - $result\n";
}
echo "Total duration: {$totalDuration}ms (should be ~1000ms, not 3000ms)\n\n";

// Test 3: Error handling
echo "Test 3: Error handling\n";

try {
    $coroutine = spawn(function() {
        delay(100);
        return "Success!";
    });
    await($coroutine);
    echo "✓ No error case works\n";
} catch (Exception $e) {
    echo "✗ Unexpected error: " . $e->getMessage() . "\n";
}

try {
    $coroutine = spawn(function() {
        delay(100);
        throw new Exception("Intentional error");
    });
    await($coroutine);
    echo "✗ Error case didn't throw\n";
} catch (Exception $e) {
    echo "✓ Error handling works: " . $e->getMessage() . "\n";
}

// Test 4: Stream operations
echo "\nTest 4: Stream operations (file_get_contents)\n";

$coroutine = spawn(function() {
    // In TrueAsync context, file_get_contents and other stream operations are non-blocking
    $content = file_get_contents('https://google.com');
    return "Read " . strlen($content) . " bytes";
});

$result = await($coroutine);
echo "Result: $result\n";

echo "\n=== All tests completed ===\n";
