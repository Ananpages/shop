<?php

// Place this file in your Laravel root: C:\xampp\htdocs\shoopbeibe\backend\read-errors.php
// Run with: php read-errors.php

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found at: $logFile\n";
    exit(1);
}

$content = file_get_contents($logFile);

// Extract all ERROR lines with their message
preg_match_all('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.ERROR: (.+?) \{"/', $content, $matches, PREG_SET_ORDER);

if (empty($matches)) {
    echo "✅ No errors found in log!\n";
    exit(0);
}

echo "\n========== LARAVEL ERRORS ==========\n\n";

// Show last 10 errors
$errors = array_slice($matches, -10);

foreach ($errors as $i => $match) {
    $num = $i + 1;
    $time = $match[1];
    $message = $match[2];
    echo "[$num] $time\n";
    echo "    ❌ $message\n\n";
}

echo "====================================\n";
echo "Total errors shown: " . count($errors) . "\n";
