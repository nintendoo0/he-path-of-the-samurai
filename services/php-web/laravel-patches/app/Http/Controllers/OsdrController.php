<?php

namespace App\Http\Controllers;

use App\Services\RustApiService;
use Illuminate\Http\Request;

class OsdrController extends Controller
{
    protected RustApiService $rustApi;

    public function __construct(RustApiService $rustApi)
    {
        $this->rustApi = $rustApi;
    }    public function index(Request $request)
    {
        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $maxDisplay = max(1, min(500, (int) $request->query('max', 20))); // Лимит на отображение
        
        $data = $this->rustApi->getOsdrList($limit);
        $items = $data['items'] ?? [];

        $items = $this->flattenOsdr($items);
        
        // Ограничиваем количество отображаемых записей
        $totalCount = count($items);
        $items = array_slice($items, 0, $maxDisplay);

        return view('osdr', [
            'items' => $items,
            'total_count' => $totalCount,
            'displayed_count' => count($items),
            'src' => "Rust API /osdr/list (limit={$limit}, showing {$maxDisplay} of {$totalCount})",
        ]);
    }private function flattenOsdr(array $items): array
    {
        $out = [];
        $uniqueId = 1;
        
        foreach ($items as $row) {
            $raw = $row['raw'] ?? [];
            if (is_array($raw) && $this->looksOsdrDict($raw)) {
                foreach ($raw as $k => $v) {
                    if (!is_array($v)) continue;
                    $rest = $v['REST_URL'] ?? $v['rest_url'] ?? $v['rest'] ?? null;
                    $title = $v['title'] ?? $v['name'] ?? null;
                    if (!$title && is_string($rest)) {
                        $title = basename(rtrim($rest, '/'));
                    }
                    $out[] = [
                        'id' => $uniqueId++,  // Уникальный ID для каждой записи
                        'parent_id' => $row['id'],  // ID родительской записи из БД
                        'dataset_id' => $k,
                        'title' => $title,
                        'status' => $row['status'] ?? null,
                        'updated_at' => $row['updated_at'] ?? null,
                        'inserted_at' => $row['inserted_at'] ?? null,
                        'rest_url' => $rest,
                        'raw' => $v,
                    ];
                }
            } else {
                $row['rest_url'] = is_array($raw) ? ($raw['REST_URL'] ?? $raw['rest_url'] ?? null) : null;
                $row['parent_id'] = null;
                $out[] = $row;
            }
        }
        return $out;
    }

    private function looksOsdrDict(array $raw): bool
    {
        foreach ($raw as $k => $v) {
            if (is_string($k) && str_starts_with($k, 'OSD-')) return true;
            if (is_array($v) && (isset($v['REST_URL']) || isset($v['rest_url']))) return true;
        }
        return false;
    }
}
