<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TelemetryController extends Controller
{
    private const CSV_PATH = '/data/csv';
      /**
     * Display telemetry CSV data with DataTables
     */
    public function index(Request $request)
    {
        $data = $this->parseLatestCSV();
        
        // Get max display limit from query parameter (default: 100, max: 10000)
        $maxDisplay = max(1, min(10000, (int) $request->query('max', 100)));
        $totalRows = count($data['rows'] ?? []);
        
        // Limit displayed rows
        $displayedRows = array_slice($data['rows'] ?? [], 0, $maxDisplay);
        
        return view('telemetry', [
            'telemetry' => $displayedRows,
            'headers' => $data['headers'] ?? [],
            'filename' => $data['filename'] ?? 'No CSV files found',
            'timestamp' => $data['timestamp'] ?? null,
            'total_count' => $totalRows,
            'displayed_count' => count($displayedRows),
        ]);
    }
      /**
     * Export CSV to XLSX - simplified CSV export
     */
    public function export(Request $request)
    {
        $data = $this->parseLatestCSV();
        
        if (empty($data['rows'])) {
            return response()->json(['error' => 'No data to export'], 404);
        }
        
        // Generate CSV file for download
        $filename = 'telemetry_export_' . date('Ymd_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $data['headers']);
            
            // Write data rows
            foreach ($data['rows'] as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
      /**
     * Parse the main CSV file from /data/csv
     */
    private function parseLatestCSV(): array
    {
        $csvPath = self::CSV_PATH;
        
        if (!is_dir($csvPath)) {
            return ['rows' => [], 'headers' => [], 'filename' => 'CSV directory not found'];
        }
        
        // Use main CSV file instead of looking for multiple files
        $mainFile = $csvPath . '/telemetry_main.csv';
        
        if (!file_exists($mainFile)) {
            return ['rows' => [], 'headers' => [], 'filename' => 'telemetry_main.csv not found'];
        }
        
        $handle = fopen($mainFile, 'r');
        if (!$handle) {
            return ['rows' => [], 'headers' => [], 'filename' => 'Cannot open CSV'];
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        
        // Read data rows
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        
        return [
            'rows' => $rows,
            'headers' => $headers,
            'filename' => basename($mainFile),
            'timestamp' => filemtime($mainFile),
        ];
    }
    
    /**
     * API endpoint for DataTables server-side processing
     */
    public function api(Request $request)
    {
        $data = $this->parseLatestCSV();
        
        if (empty($data['rows'])) {
            return response()->json([
                'draw' => (int)$request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
        
        $rows = $data['rows'];
        $headers = $data['headers'];
        
        // Search filter
        $searchValue = $request->input('search.value', '');
        if (!empty($searchValue)) {
            $rows = array_filter($rows, function($row) use ($searchValue) {
                return stripos(implode(' ', $row), $searchValue) !== false;
            });
        }
        
        $totalRecords = count($data['rows']);
        $filteredRecords = count($rows);
        
        // Sorting
        $orderColumn = (int)$request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        
        usort($rows, function($a, $b) use ($orderColumn, $orderDir) {
            $valA = $a[$orderColumn] ?? '';
            $valB = $b[$orderColumn] ?? '';
            
            // Numeric comparison for numbers
            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = (float)$valA <=> (float)$valB;
            } else {
                $cmp = strcasecmp($valA, $valB);
            }
            
            return $orderDir === 'desc' ? -$cmp : $cmp;
        });
        
        // Pagination
        $start = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);
        $rows = array_slice($rows, $start, $length);
        
        // Convert to DataTables format
        $formattedRows = array_map(function($row) use ($headers) {
            $formatted = [];
            foreach ($headers as $idx => $header) {
                $formatted[$header] = $row[$idx] ?? '';
            }
            return $formatted;
        }, $rows);
        
        return response()->json([
            'draw' => (int)$request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $formattedRows,
        ]);
    }
}
