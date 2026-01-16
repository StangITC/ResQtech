<?php

require_once __DIR__ . '/../includes/init.php';

requireLogin();

$now = microtime(true);
$serverNowMs = (int) round($now * 1000);

$lineHost = 'api.line.me';
$resolved = gethostbyname($lineHost);
$dnsOk = $resolved !== $lineHost;

$timeout = 3.0;
$sslVerify = (bool) env('LINE_SSL_VERIFY', true);
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => $sslVerify,
        'verify_peer_name' => $sslVerify
    ]
]);

$errno = 0;
$errstr = '';
$t0 = microtime(true);
$socket = @stream_socket_client(
    "ssl://{$lineHost}:443",
    $errno,
    $errstr,
    $timeout,
    STREAM_CLIENT_CONNECT,
    $context
);
$t1 = microtime(true);
$connectMs = (int) round(($t1 - $t0) * 1000);
$tlsOk = is_resource($socket);
if ($tlsOk) {
    fclose($socket);
}

$config = [
    'esp32_api_key_set' => defined('ESP32_API_KEY') && ESP32_API_KEY !== '',
    'line_user_id_set' => defined('LINE_USER_ID') && LINE_USER_ID !== '',
    'line_channel_access_token_set' => defined('LINE_CHANNEL_ACCESS_TOKEN') && LINE_CHANNEL_ACCESS_TOKEN !== '',
    'line_channel_access_token_len' => defined('LINE_CHANNEL_ACCESS_TOKEN') ? strlen((string) LINE_CHANNEL_ACCESS_TOKEN) : 0,
    'line_http_timeout' => (int) env('LINE_HTTP_TIMEOUT', 5),
    'line_ssl_verify' => $sslVerify
];

$fs = [
    'log_dir' => LOG_DIR,
    'log_dir_exists' => is_dir(LOG_DIR),
    'log_dir_writable' => is_writable(LOG_DIR)
];

$php = [
    'php_version' => PHP_VERSION,
    'openssl' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : null
];

$network = [
    'line_host' => $lineHost,
    'dns_ok' => $dnsOk,
    'resolved_ip' => $resolved,
    'tls_ok' => $tlsOk,
    'connect_ms' => $connectMs,
    'errno' => $errno,
    'error' => $errstr
];

jsonResponse([
    'status' => 'success',
    'server_now_ms' => $serverNowMs,
    'config' => $config,
    'filesystem' => $fs,
    'php' => $php,
    'network' => $network
]);

