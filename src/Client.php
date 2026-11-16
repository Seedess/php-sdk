<?php

declare(strict_types=1);

namespace Seedess;

final class Client
{
    private const DEFAULT_BASE_URL = 'https://seedess.com';

    /** @var callable(string, string, array<string,string>, ?string): array{0:int,1:string} */
    private $transport;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        ?callable $transport = null,
    ) {
        if (!str_starts_with($apiKey, 'key_')) {
            throw new \InvalidArgumentException('Seedess: apiKey must be a server API key (key_…)');
        }
        $this->transport = $transport ?? $this->curl(...);
    }

    /**
     * @return array{0:int,1:string}
     */
    private function curl(string $method, string $url, array $headers, ?string $body): array
    {
        $handle = curl_init($url);
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($raw === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new \RuntimeException('Seedess transport error: ' . $message);
        }
        curl_close($handle);
        return [$status, (string) $raw];
    }

    public function request(string $method, string $path, ?array $body = null, bool $auth = true): mixed
    {
        $url = rtrim($this->baseUrl, '/') . (str_starts_with($path, '/') ? $path : '/' . $path);
        $headers = ['accept' => 'application/json'];
        $payload = null;
        if ($body !== null) {
            $headers['content-type'] = 'application/json';
            $payload = json_encode($body, JSON_THROW_ON_ERROR);
        }
        if ($auth) {
            $headers['authorization'] = 'Bearer ' . $this->apiKey;
        }

        [$status, $raw] = ($this->transport)($method, $url, $headers, $payload);
        $data = null;
        if ($raw !== '') {
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                $data = ['error' => ['code' => 'invalid_json', 'message' => substr($raw, 0, 200)]];
            }
        }

        if ($status < 200 || $status >= 300) {
            $error = is_array($data) && isset($data['error']) && is_array($data['error']) ? $data['error'] : [];
            throw new SeedessException(
                $status,
                (string) ($error['code'] ?? 'http_error'),
                (string) ($error['message'] ?? 'HTTP ' . $status),
                $data,
            );
        }
        return $data;
    }

    public function createStream(array $input): array
    {
        return $this->request('POST', '/api/v1/streams', $input);
    }

    public function getStream(string $id): array
    {
        return $this->request('GET', '/api/v1/streams/' . rawurlencode($id));
    }

    public function listStreams(): array
    {
        return $this->request('GET', '/api/v1/streams');
    }

    public function refreshStream(string $id): array
    {
        return $this->request('POST', '/api/v1/streams/' . rawurlencode($id) . '/refresh');
    }

    public function disablePeers(string $id): array
    {
        return $this->request('POST', '/api/v1/streams/' . rawurlencode($id) . '/kill');
    }

    public function enablePeers(string $id): array
    {
        return $this->request('POST', '/api/v1/streams/' . rawurlencode($id) . '/revive');
    }

    public function getMetrics(string $id): array
    {
        return $this->request('GET', '/api/v1/streams/' . rawurlencode($id) . '/metrics');
    }

    public function sendTelemetry(array $input): mixed
    {
        return $this->request('POST', '/api/v1/telemetry', $input, false);
    }
}
