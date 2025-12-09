<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RustApiService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = env('RUST_BASE', 'http://rust_iss:3000');
        $this->timeout = (int) env('RUST_TIMEOUT', 5);
    }

    public function getIssLast(): array
    {
        return $this->get('/last');
    }

    public function getIssTrend(): array
    {
        return $this->get('/iss/trend');
    }

    public function getOsdrList(int $limit = 20): array
    {
        return $this->get('/osdr/list', ['limit' => $limit]);
    }

    public function getSpaceLatest(string $source): array
    {
        return $this->get("/space/{$source}/latest");
    }

    public function getSpaceSummary(): array
    {
        return $this->get('/space/summary');
    }

    protected function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Rust API error: {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['error' => 'upstream_error', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error("Rust API exception: {$endpoint}", [
                'message' => $e->getMessage()
            ]);

            return ['error' => 'connection_error', 'message' => $e->getMessage()];
        }
    }
}
