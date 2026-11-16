# PHP SDK example

A complete server + browser sample:

- `server.php` — creates a stream with `seedess/sdk`, then serves the page and `/api/seedess`.
- `public/index.html` — loads hls.js and `https://seedess.com/p2p.js`, then plays the stream
  with the P2P adapter.

## Run

```bash
cd sdks/php-sdk
SEEDESS_API_KEY=key_… php -S 127.0.0.1:8787 examples/server.php
open http://localhost:8787
```

Optional: `SEEDESS_STREAM_URL`, `SEEDESS_STREAM_ID`, `SEEDESS_API_BASE`.

The first request to `/api/seedess` creates the stream. The API key stays in the server
process; the browser only sees the public token and playback URLs.
