<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Async Sleep Demo</title>
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
        .timer {
            font-size: 48px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            margin: 20px 0;
        }
        .log {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
        }
        .log-entry {
            padding: 5px;
            border-left: 3px solid #4CAF50;
            margin: 5px 0;
            padding-left: 10px;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>⏱️ Async Sleep Demo</h1>

    <div class="card">
        <?php
        use function Async\spawn;
        use function Async\awaitAll;
        use function Async\delay;

        if (!extension_loaded('true_async')) {
            echo '<p style="color: red;">ERROR: async extension is not loaded!</p>';
            exit;
        }

        $steps = 5;
        $delayMs = 1000;

        echo "<p>Executing {$steps} sleep operations ({$delayMs}ms each) in parallel...</p>";

        $overallStart = microtime(true);

        $coroutines = [];
        for ($i = 1; $i <= $steps; $i++) {
            $coroutines[] = spawn(function() use ($i, $delayMs) {
                $start = microtime(true);
                delay($delayMs);
                $duration = round((microtime(true) - $start) * 1000);
                return [
                    'id' => $i,
                    'expected' => $delayMs,
                    'actual' => $duration,
                    'timestamp' => date('H:i:s.') . substr(microtime(), 2, 3),
                ];
            });
        }

        [$results, $exceptions] = awaitAll($coroutines);
        $overallDuration = round((microtime(true) - $overallStart) * 1000);

        echo '<div class="timer">' . $overallDuration . 'ms</div>';

        echo '<div class="log">';
        echo '<strong>Execution log:</strong>';
        foreach ($results as $result) {
            echo sprintf(
                '<div class="log-entry">[%s] Task %d completed: %dms (expected %dms)</div>',
                $result['timestamp'],
                $result['id'],
                $result['actual'],
                $result['expected']
            );
        }
        echo '</div>';

        $sequentialTime = $steps * $delayMs;
        echo "<p><strong>Comparison:</strong></p>";
        echo "<ul>";
        echo "<li>Parallel execution: <strong>{$overallDuration}ms</strong></li>";
        echo "<li>Sequential would be: <strong>{$sequentialTime}ms</strong></li>";
        echo "<li>Speedup: <strong>" . round($sequentialTime / $overallDuration, 2) . "x</strong></li>";
        echo "</ul>";

        if ($overallDuration < ($delayMs * 1.5)) {
            echo '<p style="color: green; font-weight: bold;">✓ Parallel execution confirmed!</p>';
        } else {
            echo '<p style="color: orange;">⚠ Tasks may not be running in parallel</p>';
        }
        ?>
    </div>

    <a href="index.php">← Back to Home</a>
</body>
</html>
