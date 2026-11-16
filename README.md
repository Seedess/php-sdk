# Seedess PHP SDK

Official Seedess SDK for PHP.

**Server-side only.** The SDK uses a secret API key (`key_…`) to create and control streams.
Never ship it in a browser or a mobile app. Playback uses the separate `@seedess/*-p2p`
packages and a per-stream public token (`pk_…`).

## Install

```bash
composer require seedess/sdk
```

PHP 8.1+ with `ext-curl` and `ext-json`.

## Use

```php
use Seedess\Client;

$seedess = new Client(getenv('SEEDESS_API_KEY'));

$stream = $seedess->createStream([
    'url'  => 'https://origin.example/master.m3u8',
    'id'   => 'demo',
    'live' => true,
]);

echo $stream['publicToken'];
echo $stream['playback']['master'];
```

Pass only the public token and stream id to the page.

## Full working example

See [`examples/`](./examples) for a server that creates a stream and a browser page
that plays it with hls.js + the Seedess P2P adapter.

## Methods

| Method | HTTP | Purpose |
| --- | --- | --- |
| `createStream(array $input)` | `POST /api/v1/streams` | Ingest a manifest, mint the public token. |
| `getStream(string $id)` | `GET /api/v1/streams/:id` | Status, variants, segment count. |
| `listStreams()` | `GET /api/v1/streams` | All streams in the tenant. |
| `refreshStream(string $id)` | `POST /api/v1/streams/:id/refresh` | Pull new live segments. |
| `disablePeers(string $id)` | `POST /api/v1/streams/:id/kill` | CDN only; playback continues. |
| `enablePeers(string $id)` | `POST /api/v1/streams/:id/revive` | Re-enable peer delivery. |
| `getMetrics(string $id)` | `GET /api/v1/streams/:id/metrics` | Offload and delivery counters. |
| `sendTelemetry(array $input)` | `POST /api/v1/telemetry` | One session batch (public, rate limited). |

`request($method, $path, $body = null, $auth = true)` is available for routes without a helper.

## Errors

```php
use Seedess\SeedessException;

try {
    $seedess->createStream(['url' => $url]);
} catch (SeedessException $error) {
    echo $error->status, $error->errorCode, $error->getMessage();
}
```

## Links

- Docs: https://seedess.com/docs/
- API: https://seedess.com/docs/api/
- Libraries: https://seedess.com/docs/libraries/

## License

MIT.
