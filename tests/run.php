<?php

declare(strict_types=1);

require __DIR__ . '/../src/SeedessException.php';
require __DIR__ . '/../src/Client.php';

use Seedess\Client;
use Seedess\SeedessException;

$failures = 0;
function check(bool $condition, string $label): void
{
    global $failures;
    if ($condition) {
        echo "ok - {$label}\n";
    } else {
        $failures++;
        echo "not ok - {$label}\n";
    }
}

function fakeTransport(int $status, ?array $body, array &$calls): callable
{
    return function (string $method, string $url, array $headers, ?string $payload) use ($status, $body, &$calls): array {
        $calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $payload];
        return [$status, $body === null ? '' : json_encode($body)];
    };
}

$calls = [];
$client = new Client('key_test', 'https://seedess.com', fakeTransport(201, ['ok' => true, 'id' => 'demo', 'publicToken' => 'pk_1'], $calls));
$stream = $client->createStream(['url' => 'https://origin/master.m3u8', 'id' => 'demo']);
check($stream['id'] === 'demo', 'createStream returns the body');
check($calls[0]['url'] === 'https://seedess.com/api/v1/streams', 'createStream path');
check($calls[0]['headers']['authorization'] === 'Bearer key_test', 'createStream auth');
check(json_decode($calls[0]['body'], true)['id'] === 'demo', 'createStream body');

$calls = [];
$client = new Client('key_test', 'https://seedess.com', fakeTransport(200, ['ok' => true], $calls));
$client->getStream('a/b');
check($calls[0]['url'] === 'https://seedess.com/api/v1/streams/a%2Fb', 'path encoding');

$calls = [];
$client = new Client('key_test', 'https://seedess.com', fakeTransport(200, ['ok' => true], $calls));
$client->disablePeers('demo');
$client->enablePeers('demo');
check($calls[0]['url'] === 'https://seedess.com/api/v1/streams/demo/kill', 'disablePeers maps to kill');
check($calls[1]['url'] === 'https://seedess.com/api/v1/streams/demo/revive', 'enablePeers maps to revive');

$calls = [];
$client = new Client('key_test', 'https://seedess.com', fakeTransport(204, null, $calls));
$client->sendTelemetry(['sessionId' => 's', 'streamId' => 'demo']);
check(!isset($calls[0]['headers']['authorization']), 'telemetry has no API key');

$calls = [];
$client = new Client('key_test', 'https://seedess.com', fakeTransport(429, ['ok' => false, 'error' => ['code' => 'QUOTA_EXCEEDED', 'message' => 'Limit reached']], $calls));
try {
    $client->createStream(['url' => 'https://origin']);
    check(false, 'error is thrown');
} catch (SeedessException $error) {
    check($error->status === 429, 'error status');
    check($error->errorCode === 'QUOTA_EXCEEDED', 'error code');
    check($error->getMessage() === 'Limit reached', 'error message');
}

try {
    new Client('pk_stream');
    check(false, 'public token rejected');
} catch (InvalidArgumentException) {
    check(true, 'public token rejected');
}

exit($failures === 0 ? 0 : 1);
