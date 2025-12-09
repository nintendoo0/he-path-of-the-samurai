<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AstronomyApiService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appSecret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = 'https://api.astronomyapi.com/api/v2';
        $this->appId = env('ASTRO_APP_ID', '');
        $this->appSecret = env('ASTRO_APP_SECRET', '');
        $this->timeout = (int) env('ASTRO_TIMEOUT', 25);
    }

    public function getEvents(float $lat, float $lon, int $days): array
    {
        if (empty($this->appId) || empty($this->appSecret)) {
            return ['error' => 'Missing ASTRO_APP_ID/ASTRO_APP_SECRET'];
        }

        $from = now('UTC')->toDateString();
        $to = now('UTC')->addDays($days)->toDateString();

        $query = [
            'latitude' => $lat,
            'longitude' => $lon,
            'from' => $from,
            'to' => $to,
        ];

        try {
            $auth = base64_encode($this->appId . ':' . $this->appSecret);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'monolith-iss/1.0'
                ])
                ->get($this->baseUrl . '/bodies/events', $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("Astronomy API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'error' => 'astronomy_api_error',
                'code' => $response->status(),
                'message' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Astronomy API exception", [
                'message' => $e->getMessage()
            ]);

            return ['error' => 'connection_error', 'message' => $e->getMessage()];
        }
    }
}
