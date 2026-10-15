<?php

declare(strict_types=1);

/**
 * Fails (exit code 1) when line coverage in a Clover XML report is below the given threshold.
 *
 * Usage: php tests/coverage-check.php <clover.xml> [min-percent=90]
 */

$file = $argv[1] ?? '';
$threshold = (float) ($argv[2] ?? 90);

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Clover report not found: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);
$metrics = $xml !== false ? $xml->xpath('/coverage/project/metrics') : false;
if ($metrics === false || $metrics === null || $metrics === []) {
    fwrite(STDERR, "Invalid Clover report: {$file}\n");
    exit(1);
}

$statements = (int) $metrics[0]['statements'];
$covered = (int) $metrics[0]['coveredstatements'];
$percent = $statements > 0 ? $covered / $statements * 100 : 0.0;

printf("Line coverage: %.2f%% (%d/%d), required: %.2f%%\n", $percent, $covered, $statements, $threshold);

exit($percent >= $threshold ? 0 : 1);
