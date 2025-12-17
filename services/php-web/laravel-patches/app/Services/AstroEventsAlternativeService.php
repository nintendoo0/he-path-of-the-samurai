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
    }    /**
     * Получить астрономические события (восход/закат солнца и луны)
     * 
     * @param float $lat Широта
     * @param float $lon Долгота
     * @param int $days Количество дней для прогноза
     * @return array
     */    public function getEvents(float $lat, float $lon, int $days = 7): array
    {
        try {
            // Логируем все запросы
            Log::info("AstroEventsAlternativeService::getEvents called", [
                'lat' => $lat,
                'lon' => $lon,
                'days' => $days
            ]);
            
            // Валидация координат
            $validation = $this->validateCoordinates($lat, $lon);
            if (!$validation['valid']) {
                Log::warning("Invalid coordinates", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'reason' => $validation['reason']
                ]);
                
                return [
                    'data' => [],
                    'table' => [
                        'header' => [],
                        'rows' => []
                    ],
                    'error' => true,
                    'message' => $validation['reason'],
                    'coordinates' => compact('lat', 'lon')
                ];
            }
            
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
    }    /**
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

    /**
     * Валидация координат на осмысленность
     * Проверяет базовые диапазоны и "странные" локации
     * 
     * @param float $lat Широта
     * @param float $lon Долгота
     * @return array ['valid' => bool, 'reason' => string|null]
     */
    private function validateCoordinates(float $lat, float $lon): array
    {
        // Базовая валидация диапазонов
        if ($lat < -90 || $lat > 90) {
            return [
                'valid' => false,
                'reason' => 'Широта должна быть в диапазоне от -90° до 90°'
            ];
        }

        if ($lon < -180 || $lon > 180) {
            return [
                'valid' => false,
                'reason' => 'Долгота должна быть в диапазоне от -180° до 180°'
            ];
        }

        // Проверка на подозрительные координаты (0, 0) - "Null Island"
        if (abs($lat) < 0.1 && abs($lon) < 0.1) {
            return [
                'valid' => false,
                'reason' => 'Координаты (0, 0) находятся в Атлантическом океане. Укажите реальное местоположение.'
            ];
        }        // Проверка на океанические области без населения
        // Это упрощенная проверка - в реальности нужна база данных
        $oceanicZones = [
            // Экваториальная Атлантика и Индийский океан у Африки
            ['lat_min' => -10, 'lat_max' => 10, 'lon_min' => -20, 'lon_max' => 60],
            // Тихий океан (западная часть)
            ['lat_min' => -60, 'lat_max' => 60, 'lon_min' => -180, 'lon_max' => -120],
            // Тихий океан (восточная часть)
            ['lat_min' => -60, 'lat_max' => 60, 'lon_min' => 150, 'lon_max' => 180],
            // Индийский океан (центральная часть)
            ['lat_min' => -50, 'lat_max' => 20, 'lon_min' => 60, 'lon_max' => 95],
        ];

        foreach ($oceanicZones as $zone) {
            if ($lat >= $zone['lat_min'] && $lat <= $zone['lat_max'] &&
                $lon >= $zone['lon_min'] && $lon <= $zone['lon_max']) {
                
                // Исключения: населенные области
                // Австралия/Океания
                if ($lat >= -45 && $lat <= -10 && $lon >= 110 && $lon <= 155) {
                    continue;
                }
                // Юго-Восточная Азия
                if ($lat >= -10 && $lat <= 20 && $lon >= 95 && $lon <= 120) {
                    continue;
                }
                // Восточная Африка (материковая часть)
                if ($lat >= -12 && $lat <= 5 && $lon >= 28 && $lon <= 42) {
                    continue;
                }
                // Южная Африка
                if ($lat >= -35 && $lat <= -15 && $lon >= 15 && $lon <= 33) {
                    continue;
                }
                // Западная Африка
                if ($lat >= 0 && $lat <= 20 && $lon >= -18 && $lon <= 15) {
                    continue;
                }
                
                return [
                    'valid' => false,
                    'reason' => 'Указанные координаты находятся в океане. Пожалуйста, укажите координаты населенного пункта.'
                ];
            }
        }

        // Антарктика (южнее -60°)
        if ($lat < -60) {
            return [
                'valid' => false,
                'reason' => 'Координаты находятся в Антарктике. Для астрономических событий укажите населенный пункт.'
            ];
        }

        // Крайний север (севернее 80°) - малонаселенная область
        if ($lat > 80) {
            return [
                'valid' => false,
                'reason' => 'Координаты находятся в Арктике. Для астрономических событий укажите населенный пункт.'
            ];
        }

        return ['valid' => true, 'reason' => null];
    }
}
