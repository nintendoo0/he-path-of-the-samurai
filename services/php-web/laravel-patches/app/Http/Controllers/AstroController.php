<?php

namespace App\Http\Controllers;

use App\Services\AstronomyApiService;
use Illuminate\Http\Request;

class AstroController extends Controller
{
    protected AstronomyApiService $astronomyApi;

    public function __construct(AstronomyApiService $astronomyApi)
    {
        $this->astronomyApi = $astronomyApi;
    }

    public function events(Request $request)
    {
        $lat = (float) $request->query('lat', 55.7558);
        $lon = (float) $request->query('lon', 37.6176);
        $days = max(1, min(30, (int) $request->query('days', 7)));

        $result = $this->astronomyApi->getEvents($lat, $lon, $days);

        if (isset($result['error'])) {
            return response()->json($result, 403);
        }

        return response()->json($result);
    }
}
