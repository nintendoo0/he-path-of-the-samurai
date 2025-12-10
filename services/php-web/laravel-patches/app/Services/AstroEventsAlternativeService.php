<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Альтернативный сервис для получения астрономических событий
 * Использует бесплатные API без требования ключей
 */
class AstroEventsAlternativeService
{
    protected int $timeout;

    public function __construct()
    {
        $this->timeout = 10;
    }

    /**
     * Получить астрономические события (восход/закат солнца и луны)
     * 
     * @param float $lat Широта
     * @param float $lon Долгота
     * @param int $days Количество дней для прогноза
     * @return array
     */
    public function getEvents(float $lat, float $lon, int $days = 7): array
    {
        try {
            // Используем Open-Meteo API (бесплатный, без ключей)
            // Документация: https://open-meteo.com/en/docs
            
            $fromDate = now('UTC')->format('Y-m-d');
            $toDate = now('UTC')->addDays($days)->format('Y-m-d');
            
            $url = 'https://api.open-meteo.com/v1/forecast';
            $params = [
                'latitude' => $lat,
                'longitude' => $lon,
                'start_date' => $fromDate,
                'end_date' => $toDate,
                'daily' => 'sunrise,sunset,daylight_duration,sunshine_duration',
                'timezone' => 'auto',
            ];

            Log::info("Open-Meteo API Request", [
                'url' => $url,
                'params' => $params
            ]);

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info("Open-Meteo API Success", [
                    'status' => $response->status(),
                    'has_data' => isset($data['daily'])
                ]);

                return $this->formatOpenMeteoResponse($data, $lat, $lon, $fromDate, $toDate);
            }

            Log::warning("Open-Meteo API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->emptyResponse($lat, $lon, $fromDate, $toDate);
        } catch (\Exception $e) {
            Log::error("Open-Meteo API exception: " . $e->getMessage());
            return $this->emptyResponse($lat, $lon, $fromDate, $toDate);
        }
    }

    /**
     * Форматировать ответ Open-Meteo в формат таблицы для фронтенда
     */
    private function formatOpenMeteoResponse(array $data, float $lat, float $lon, string $fromDate, string $toDate): array
    {
        if (!isset($data['daily'])) {
            return $this->emptyResponse($lat, $lon, $fromDate, $toDate);
        }

        $daily = $data['daily'];
        $rows = [];
        $header = ['Дата', 'Восход ☀️', 'Закат 🌅', 'Длительность дня', 'Солнечное время'];

        // Форматируем данные для каждого дня
        for ($i = 0; $i < count($daily['time'] ?? []); $i++) {
            $date = $daily['time'][$i];
            $sunrise = $daily['sunrise'][$i] ?? null;
            $sunset = $daily['sunset'][$i] ?? null;
            $daylight = $this->formatDuration($daily['daylight_duration'][$i] ?? 0);
            $sunshine = $this->formatDuration($daily['sunshine_duration'][$i] ?? 0);
            
            // Форматируем время для отображения
            $sunriseTime = $sunrise ? date('H:i', strtotime($sunrise)) : '-';
            $sunsetTime = $sunset ? date('H:i', strtotime($sunset)) : '-';
            
            $rows[] = [
                'cells' => [
                    ['value' => date('d.m.Y', strtotime($date))],
                    ['value' => $sunriseTime],
                    ['value' => $sunsetTime],
                    ['value' => $daylight],
                    ['value' => $sunshine]
                ]
            ];
        }

        return [
            'data' => $rows,
            'table' => [
                'header' => $header,
                'rows' => $rows
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
            'source' => 'Open-Meteo (Free Alternative)'
        ];
    }

    /**
     * Форматировать длительность в секундах в читаемый формат
     */
    private function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }

    /**
     * Возвращает пустой ответ в случае ошибки
     */
    private function emptyResponse(float $lat, float $lon, string $fromDate, string $toDate): array
    {
        return [
            'data' => [],
            'table' => [
                'header' => [],
                'rows' => []
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
            'source' => 'Open-Meteo (Free Alternative)',
            'error' => 'No data available'
        ];
    }
}
