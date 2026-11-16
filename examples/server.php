<?php

declare(strict_types=1);

/**
 * Full working Seedess example for PHP.
 *
 *   SEEDESS_API_KEY=key_... php -S 127.0.0.1:8787 examples/server.php
 *   open http://localhost:8787
 */

require __DIR__ . '/../src/SeedessException.php';
require __DIR__ . '/../src/Client.php';

use Seedess\Client;
use Seedess\SeedessException;

const ORIGIN = 'https://seedess.com';

$apiKey = getenv('SEEDESS_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo 'Set SEEDESS_API_KEY (a server key, key_...) before running this example.';
    return;
}

$streamUrl = getenv('SEEDESS_STREAM_URL') ?: 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';
$streamId = getenv('SEEDESS_STREAM_ID') ?: 'sdk-example';
$baseUrl = getenv('SEEDESS_API_BASE') ?: ORIGIN;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

function handoff(Client $client, string $streamUrl, string $streamId, string $baseUrl): array
{
    $cacheFile = sys_get_temp_dir() . '/seedess-example-' . md5($streamId) . '.json';
    if (is_file($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $stream = $client->createStream([
        'url' => $streamUrl,
        'id' => $streamId,
        'title' => 'Seedess PHP SDK example',
    ]);

    $token = $stream['publicToken'];
    $playback = $stream['playback'];
    $payload = [
        'ok' => true,
        'stream' => $stream['id'],
        'token' => $token,
        'playback' => [
            'master' => ORIGIN . $playback['master'],
            'signaling' => ORIGIN . $playback['signaling'],
            'config' => ORIGIN . '/signaling/config?token=' . rawurlencode($token),
        ],
    ];
    file_put_contents($cacheFile, json_encode($payload));
    return $payload;
}

header('content-type: application/json');

try {
    if ($path === '/api/seedess') {
        $client = new Client($apiKey, $baseUrl);
        echo json_encode(handoff($client, $streamUrl, $streamId, $baseUrl));
        return;
    }

    if ($path === '/' || $path === '/index.html') {
        header('content-type: text/html; charset=utf-8');
        readfile(__DIR__ . '/public/index.html');
        return;
    }

    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Not found']);
} catch (SeedessException $error) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $error->status . ' ' . $error->errorCode . ': ' . $error->getMessage(),
    ]);
}
