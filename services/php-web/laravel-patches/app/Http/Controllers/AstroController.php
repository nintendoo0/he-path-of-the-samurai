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
    }

    public function events(Request $request)
    {
        $lat = (float) $request->query('lat', 55.7558);
        $lon = (float) $request->query('lon', 37.6176);
        $days = max(1, min(30, (int) $request->query('days', 7)));
        $useAlternative = $request->query('alternative', false);
        $forceAstronomy = $request->query('force_astronomy', false);

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
}
