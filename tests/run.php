<?php

/**
 * Test runner.
 *
 * Usage:  php tests/run.php  [filter]
 *
 * Loads every *Test.php in this directory and reports the result. An optional
 * argument limits the run to files whose name contains that substring.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Harness.php';

$filter = $argv[1] ?? '';
$files  = glob(__DIR__ . DIRECTORY_SEPARATOR . '*Test.php') ?: [];

sort($files);

foreach ($files as $file) {
    if ($filter !== '' && !str_contains(basename($file), $filter)) {
        continue;
    }

    echo basename($file, '.php') . PHP_EOL;

    require_once $file;
}

exit(Harness::summary());
