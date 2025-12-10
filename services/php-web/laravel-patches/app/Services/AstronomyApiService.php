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
        // AstronomyAPI is optional feature
        // Return empty data structure if keys not configured
        if (empty($this->appId) || empty($this->appSecret) || 
            $this->appId === 'your_app_id_here' || 
            $this->appSecret === 'your_app_secret_here') {
            Log::info("AstronomyAPI: Keys not configured");
            return $this->emptyResponse();
        }

        // Format date and time according to documentation
        $fromDate = now('UTC')->format('Y-m-d');
        $toDate = now('UTC')->addDays($days)->format('Y-m-d');
        $time = '12:00:00'; // Noon for position calculations

        try {
            // According to official documentation: https://docs.astronomyapi.com/
            // Use /bodies/positions/:body endpoint for daily astronomical data
            // This endpoint returns positions which include rise/set times
            
            $results = [];
            
            // Get sun positions (includes sunrise/sunset data)
            $sunData = $this->fetchBodyPositions('sun', $lat, $lon, $fromDate, $toDate, $time);
            if (!empty($sunData)) {
                $results['sun'] = $sunData;
            }
            
            // Get moon positions (includes moonrise/moonset data)
            $moonData = $this->fetchBodyPositions('moon', $lat, $lon, $fromDate, $toDate, $time);
            if (!empty($moonData)) {
                $results['moon'] = $moonData;
            }

            // Format results as table for frontend
            if (!empty($results)) {
                return $this->formatPositionsAsTable($results, $lat, $lon, $fromDate, $toDate);
            }

            return $this->emptyResponse();
        } catch (\Exception $e) {
            Log::error("Astronomy API exception: " . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return $this->emptyResponse();
        }
    }

    private function fetchBodyPositions(string $body, float $lat, float $lon, string $fromDate, string $toDate, string $time): array
    {
        // Use /bodies/positions/:body endpoint
        $url = "{$this->baseUrl}/bodies/positions/{$body}";
        
        // Query parameters in snake_case per documentation
        $params = [
            'latitude' => $lat,
            'longitude' => $lon,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'time' => $time,
            'elevation' => 0,
        ];

        Log::info("AstronomyAPI Positions Request for {$body}", [
            'url' => $url,
            'params' => $params,
            'app_id' => $this->appId
        ]);

        try {
            // Use Basic Authentication (base64 of appId:appSecret)
            $authString = base64_encode("{$this->appId}:{$this->appSecret}");
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Basic {$authString}",
                    'Accept' => 'application/json'
                ])
                ->get($url, $params);

            Log::info("AstronomyAPI Positions Response for {$body}", [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_preview' => substr($response->body(), 0, 1000)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Extract table data
                if (isset($data['data']['table'])) {
                    return [
                        'header' => $data['data']['table']['header'] ?? [],
                        'rows' => $data['data']['table']['rows'] ?? []
                    ];
                }
                
                return [];
            }

            // Log error but don't throw exception
            Log::warning("Astronomy API positions error for {$body}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Astronomy API positions exception for {$body}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format positions data as table for frontend display
     */
    private function formatPositionsAsTable(array $results, float $lat, float $lon, string $fromDate, string $toDate): array
    {
        $tableHeader = ['Дата', 'Небесное тело', 'Азимут', 'Высота', 'Расстояние (AU)', 'Созвездие'];
        $tableRows = [];

        foreach ($results as $bodyType => $bodyData) {
            if (empty($bodyData['rows'])) {
                continue;
            }

            foreach ($bodyData['rows'] as $row) {
                $bodyName = $row['entry']['name'] ?? $bodyType;
                $cells = $row['cells'] ?? [];

                foreach ($cells as $cell) {
                    $date = $cell['date'] ?? '-';
                    $position = $cell['position'] ?? [];
                    $horizontal = $position['horizontal'] ?? [];
                    $distance = $cell['distance']['fromEarth']['au'] ?? '-';
                    $constellation = $position['constellation']['name'] ?? '-';

                    $tableRows[] = [
                        'cells' => [
                            ['value' => date('d.m.Y H:i', strtotime($date))],
                            ['value' => $bodyName],
                            ['value' => ($horizontal['azimuth']['degrees'] ?? '-') . '°'],
                            ['value' => ($horizontal['altitude']['degrees'] ?? '-') . '°'],
                            ['value' => $distance],
                            ['value' => $constellation]
                        ]
                    ];
                }
            }
        }

        return [
            'data' => $tableRows,
            'table' => [
                'header' => $tableHeader,
                'rows' => $tableRows
            ],
            'observer' => [
                'location' => [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'elevation' => 0
                ]
            ],
            'dates' => [
                'from' => $fromDate,
                'to' => $toDate
            ],
            'source' => 'AstronomyAPI (Official)'
        ];
    }

    private function fetchBodyEvents(string $body, float $lat, float $lon, string $fromDate, string $toDate, string $time): array
    {
        // Correct endpoint per documentation
        $url = "{$this->baseUrl}/bodies/events/{$body}";
        
        // Query parameters in snake_case per documentation
        $params = [
            'latitude' => $lat,
            'longitude' => $lon,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'time' => $time,
            'elevation' => 0,
        ];

        Log::info("AstronomyAPI Request for {$body}", [
            'url' => $url,
            'params' => $params,
            'app_id' => $this->appId
        ]);

        try {
            // Method 1: Basic Authentication (as per documentation)
            $authString = base64_encode("{$this->appId}:{$this->appSecret}");
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Basic {$authString}",
                    'Accept' => 'application/json'
                ])
                ->get($url, $params);

            Log::info("AstronomyAPI Response for {$body}", [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 1000)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['rows'] ?? [];
            }

            // Log error but don't throw exception
            Log::warning("Astronomy API error for {$body}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'auth_header_length' => strlen($authString)
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error("Astronomy API exception for {$body}: " . $e->getMessage());
            return [];
        }
    }

    private function emptyResponse(): array
    {
        return [
            'data' => [],
            'observer' => [
                'location' => [
                    'latitude' => 0,
                    'longitude' => 0,
                    'elevation' => 0
                ]
            ],
            'dates' => [
                'from' => '',
                'to' => ''
            ]
        ];
    }
}
