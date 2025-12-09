<?php

namespace App\Http\Controllers;

use App\Services\RustApiService;
use App\Services\JwstService;

class DashboardController extends Controller
{
    protected RustApiService $rustApi;
    protected JwstService $jwstService;

    public function __construct(RustApiService $rustApi, JwstService $jwstService)
    {
        $this->rustApi = $rustApi;
        $this->jwstService = $jwstService;
    }

    public function index()
    {
        $iss = $this->rustApi->getIssLast();

        return view('dashboard', [
            'iss' => $iss,
            'trend' => [],
            'jw_gallery' => [],
            'jw_observation_raw' => [],
            'jw_observation_summary' => [],
            'jw_observation_images' => [],
            'jw_observation_files' => [],
            'metrics' => [
                'iss_speed' => $iss['payload']['velocity'] ?? null,
                'iss_alt' => $iss['payload']['altitude'] ?? null,
                'neo_total' => 0,
            ],
        ]);
    }

    public function jwstFeed(\Illuminate\Http\Request $request)
    {
        $params = [
            'source' => $request->query('source', 'jpg'),
            'suffix' => trim((string) $request->query('suffix', '')),
            'program' => trim((string) $request->query('program', '')),
            'instrument' => trim((string) $request->query('instrument', '')),
            'page' => max(1, (int) $request->query('page', 1)),
            'perPage' => max(1, min(60, (int) $request->query('perPage', 24))),
        ];

        $items = $this->jwstService->fetchGallery($params);

        return response()->json([
            'source' => $params['source'],
            'count' => count($items),
            'items' => $items,
        ]);
    }
}
