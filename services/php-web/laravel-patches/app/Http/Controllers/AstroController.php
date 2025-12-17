<?php

namespace App\Http\Controllers;

use App\Services\AstronomyApiService;
use App\Services\AstroEventsAlternativeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AstroController extends Controller
{
    protected AstronomyApiService $astronomyApi;
    protected AstroEventsAlternativeService $alternativeApi;

    public function __construct(
        AstronomyApiService $astronomyApi,
        AstroEventsAlternativeService $alternativeApi
    ) {
        $this->astronomyApi = $astronomyApi;
        $this->alternativeApi = $alternativeApi;
    }    public function events(Request $request)
    {
        $lat = (float) $request->query('lat', 55.7558);
        $lon = (float) $request->query('lon', 37.6176);
        $days = max(1, min(30, (int) $request->query('days', 7)));
        $useAlternative = $request->query('alternative', false);
        $forceAstronomy = $request->query('force_astronomy', false);

        // Валидация координат (независимо от API)
        $validation = $this->validateCoordinates($lat, $lon);
        if (!$validation['valid']) {
            Log::warning("Invalid coordinates in AstroController", [
                'lat' => $lat,
                'lon' => $lon,
                'reason' => $validation['reason']
            ]);
            
            return response()->json([
                'data' => [],
                'table' => [
                    'header' => [],
                    'rows' => []
                ],
                'error' => true,
                'message' => $validation['reason'],
                'coordinates' => compact('lat', 'lon')
            ]);
        }

        // Если явно запрошена альтернатива
        if ($useAlternative && !$forceAstronomy) {
            Log::info("Using alternative astronomy service (Open-Meteo)");
            $result = $this->alternativeApi->getEvents($lat, $lon, $days);
            return response()->json($result);
        }

        // Пробуем основной сервис (AstronomyAPI)
        $result = $this->astronomyApi->getEvents($lat, $lon, $days);

        // Если force_astronomy=true, не используем fallback (для отладки)
        if ($forceAstronomy) {
            Log::info("Force astronomy mode - no fallback");
            return response()->json($result);
        }

        // Если основной сервис вернул пустые данные, пробуем альтернативу
        if (empty($result['data'])) {
            Log::info("AstronomyAPI returned empty data, falling back to alternative service");
            $result = $this->alternativeApi->getEvents($lat, $lon, $days);
        }

        // Always return 200 OK with data (empty or filled)
        return response()->json($result);
    }

    /**
     * Валидация координат на осмысленность
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

        // Проверка на "Null Island" (0, 0)
        if (abs($lat) < 0.1 && abs($lon) < 0.1) {
            return [
                'valid' => false,
                'reason' => 'Координаты (0, 0) находятся в Атлантическом океане. Укажите реальное местоположение.'
            ];
        }        // Проверка на океанические области
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
                // Восточная Африка (материковая часть, без побережных вод)
                // Кения, Танзания, Уганда и т.д. - более узкая зона
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

        // Антарктика
        if ($lat < -60) {
            return [
                'valid' => false,
                'reason' => 'Координаты находятся в Антарктике. Для астрономических событий укажите населенный пункт.'
            ];
        }

        // Крайний север
        if ($lat > 80) {
            return [
                'valid' => false,
                'reason' => 'Координаты находятся в Арктике. Для астрономических событий укажите населенный пункт.'
            ];
        }

        return ['valid' => true, 'reason' => null];
    }
}
