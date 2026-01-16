<?php

require_once __DIR__ . '/../includes/init.php';

requireLogin();

$file = LOG_DIR . 'perf_events.jsonl';
$limit = $_GET['limit'] ?? 500;
$limit = is_numeric($limit) ? (int) $limit : 500;
$limit = max(1, min($limit, 5000));

$actionFilter = $_GET['action'] ?? null;
$actionFilter = is_string($actionFilter) && $actionFilter !== '' ? $actionFilter : null;

if (!file_exists($file)) {
    jsonResponse([
        'status' => 'success',
        'file' => basename($file),
        'count' => 0,
        'events' => [],
        'summary' => []
    ]);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    jsonResponse(['status' => 'error', 'message' => 'Failed to read perf log'], 500);
}

$slice = array_slice($lines, -$limit);
$events = [];

foreach ($slice as $line) {
    $row = json_decode($line, true);
    if (!is_array($row)) {
        continue;
    }
    if ($actionFilter !== null && (($row['action'] ?? null) !== $actionFilter)) {
        continue;
    }
    $events[] = $row;
}

function percentile(array $values, float $p): ?float
{
    if (empty($values)) {
        return null;
    }
    sort($values);
    $n = count($values);
    $rank = (int) ceil($p * $n);
    $idx = max(0, min($n - 1, $rank - 1));
    return (float) $values[$idx];
}

function summarizeMetric(array $events, string $key): array
{
    $values = [];
    foreach ($events as $e) {
        if (isset($e[$key]) && is_numeric($e[$key])) {
            $values[] = (float) $e[$key];
        }
    }

    if (empty($values)) {
        return [
            'count' => 0,
            'min' => null,
            'max' => null,
            'avg' => null,
            'p50' => null,
            'p95' => null,
            'p99' => null
        ];
    }

    $count = count($values);
    $sum = array_sum($values);
    $min = min($values);
    $max = max($values);

    return [
        'count' => $count,
        'min' => $min,
        'max' => $max,
        'avg' => $sum / $count,
        'p50' => percentile($values, 0.50),
        'p95' => percentile($values, 0.95),
        'p99' => percentile($values, 0.99)
    ];
}

$byAction = [];
foreach ($events as $e) {
    $a = $e['action'] ?? 'unknown';
    if (!isset($byAction[$a])) {
        $byAction[$a] = [];
    }
    $byAction[$a][] = $e;
}

$summary = [];
foreach ($byAction as $a => $rows) {
    $summary[$a] = [
        'server_total_ms' => summarizeMetric($rows, 'server_total_ms'),
        'line_api_ms' => summarizeMetric($rows, 'line_api_ms')
    ];
}

jsonResponse([
    'status' => 'success',
    'file' => basename($file),
    'count' => count($events),
    'limit' => $limit,
    'action' => $actionFilter,
    'summary' => $summary,
    'events' => $events
]);

