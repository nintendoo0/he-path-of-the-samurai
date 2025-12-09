<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JwstService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $email;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = env('JWST_HOST', 'https://api.jwstapi.com');
        $this->apiKey = env('JWST_API_KEY', '');
        $this->email = env('JWST_EMAIL', '');
        $this->timeout = (int) env('JWST_TIMEOUT', 30);
    }

    public function fetchGallery(array $params): array
    {
        $source = $params['source'] ?? 'jpg';
        $suffix = $params['suffix'] ?? '';
        $program = $params['program'] ?? '';
        $instrument = strtoupper($params['instrument'] ?? '');
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(60, (int) ($params['perPage'] ?? 24)));

        $path = 'all/type/jpg';
        if ($source === 'suffix' && $suffix !== '') {
            $path = 'all/suffix/' . ltrim($suffix, '/');
        }
        if ($source === 'program' && $program !== '') {
            $path = 'program/id/' . rawurlencode($program);
        }

        $response = $this->get($path, ['page' => $page, 'perPage' => $perPage]);
        $list = $response['body'] ?? ($response['data'] ?? (is_array($response) ? $response : []));

        return $this->processGalleryItems($list, $instrument, $perPage);
    }

    protected function processGalleryItems(array $list, string $instrumentFilter, int $limit): array
    {
        $items = [];

        foreach ($list as $it) {
            if (!is_array($it)) continue;

            $url = $this->pickImageUrl($it);
            if (!$url) continue;

            $instruments = $this->extractInstruments($it);
            if ($instrumentFilter && $instruments && !in_array($instrumentFilter, $instruments, true)) {
                continue;
            }

            $items[] = [
                'url' => $url,
                'obs' => (string) ($it['observation_id'] ?? $it['observationId'] ?? ''),
                'program' => (string) ($it['program'] ?? ''),
                'suffix' => (string) ($it['details']['suffix'] ?? $it['suffix'] ?? ''),
                'inst' => $instruments,
                'caption' => $this->buildCaption($it, $instruments),
                'link' => $it['location'] ?? $it['url'] ?? $url,
            ];

            if (count($items) >= $limit) break;
        }

        return $items;
    }

    protected function pickImageUrl(array $item): ?string
    {
        $location = $item['location'] ?? $item['url'] ?? null;
        $thumbnail = $item['thumbnail'] ?? null;

        foreach ([$location, $thumbnail] as $u) {
            if (is_string($u) && preg_match('~\.(jpg|jpeg|png)(\?.*)?$~i', $u)) {
                return $u;
            }
        }

        return null;
    }

    protected function extractInstruments(array $item): array
    {
        $instruments = [];
        $details = $item['details']['instruments'] ?? [];

        foreach ($details as $inst) {
            if (is_array($inst) && !empty($inst['instrument'])) {
                $instruments[] = strtoupper($inst['instrument']);
            }
        }

        return $instruments;
    }

    protected function buildCaption(array $item, array $instruments): string
    {
        return trim(
            (($item['observation_id'] ?? '') ?: ($item['id'] ?? '')) .
            ' · P' . ($item['program'] ?? '-') .
            (($item['details']['suffix'] ?? '') ? ' · ' . $item['details']['suffix'] : '') .
            ($instruments ? ' · ' . implode('/', $instruments) : '')
        );
    }

    protected function get(string $path, array $query = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'X-API-Email' => $this->email,
                    'User-Agent' => 'monolith-iss/1.0',
                ])
                ->get($this->baseUrl . '/' . ltrim($path, '/'), $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error("JWST API error: {$path}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['error' => 'jwst_error', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error("JWST API exception: {$path}", [
                'message' => $e->getMessage()
            ]);

            return ['error' => 'connection_error', 'message' => $e->getMessage()];
        }
    }
}
