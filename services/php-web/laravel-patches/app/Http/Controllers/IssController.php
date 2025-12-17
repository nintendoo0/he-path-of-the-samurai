<?php

namespace App\Http\Controllers;

class IssController extends Controller
{    public function index(\Illuminate\Http\Request $request)
    {
        $base = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        $last  = @file_get_contents($base.'/last');
        $trend = @file_get_contents($base.'/iss/trend');

        $lastJson  = $last  ? json_decode($last,  true) : [];
        $trendJson = $trend ? json_decode($trend, true) : [];
        
        // Get max display limit for trend data (default: 50, max: 1000)
        $maxDisplay = max(1, min(1000, (int) $request->query('max', 50)));
        $totalTrend = count($trendJson);
        
        // Limit trend data
        $displayedTrend = array_slice($trendJson, 0, $maxDisplay);

        return view('iss', [
            'last' => $lastJson, 
            'trend' => $displayedTrend, 
            'base' => $base,
            'total_trend_count' => $totalTrend,
            'displayed_trend_count' => count($displayedTrend),
        ]);
    }
}
