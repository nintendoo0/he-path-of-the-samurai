<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TelemetryController extends Controller
{
    private const CSV_PATH = '/data/csv';
    
    /**
     * Display telemetry CSV data with DataTables
     */
    public function index(Request $request)
    {
        $data = $this->parseLatestCSV();
        
        return view('telemetry', [
            'telemetry' => $data['rows'] ?? [],
            'headers' => $data['headers'] ?? [],
            'filename' => $data['filename'] ?? 'No CSV files found',
            'timestamp' => $data['timestamp'] ?? null,
        ]);
    }
    
    /**
     * Export CSV to XLSX with proper formatting
     */
    public function export(Request $request)
    {
        $data = $this->parseLatestCSV();
        
        if (empty($data['rows'])) {
            return response()->json(['error' => 'No data to export'], 404);
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Telemetry Data');
        
        // Headers
        $headers = $data['headers'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }
        
        // Data rows with type formatting
        $row = 2;
        foreach ($data['rows'] as $dataRow) {
            $col = 'A';
            foreach ($headers as $idx => $header) {
                $value = $dataRow[$idx] ?? '';
                $cellCoord = $col . $row;
                
                // Type-specific formatting
                if ($header === 'recorded_at') {
                    // Timestamp ISO8601 → Excel DateTime
                    $sheet->setCellValue($cellCoord, $value);
                    $sheet->getStyle($cellCoord)
                          ->getNumberFormat()
                          ->setFormatCode('yyyy-mm-dd hh:mm:ss');
                } elseif ($header === 'sensor_active') {
                    // Boolean as TRUE/FALSE
                    $sheet->setCellValue($cellCoord, $value === 'TRUE' ? 'TRUE' : 'FALSE');
                } elseif (in_array($header, ['voltage', 'temp'])) {
                    // Float numbers with 2 decimals
                    $sheet->setCellValue($cellCoord, (float)$value);
                    $sheet->getStyle($cellCoord)
                          ->getNumberFormat()
                          ->setFormatCode('0.00');
                } elseif ($header === 'cycle_count') {
                    // Integer
                    $sheet->setCellValue($cellCoord, (int)$value);
                    $sheet->getStyle($cellCoord)
                          ->getNumberFormat()
                          ->setFormatCode('0');
                } else {
                    // String
                    $sheet->setCellValue($cellCoord, $value);
                }
                
                $col++;
            }
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', $col) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // Generate file
        $filename = 'telemetry_export_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        // Stream to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Parse the latest CSV file from /data/csv
     */
    private function parseLatestCSV(): array
    {
        $csvPath = self::CSV_PATH;
        
        if (!is_dir($csvPath)) {
            return ['rows' => [], 'headers' => [], 'filename' => 'CSV directory not found'];
        }
        
        $files = glob($csvPath . '/telemetry_*.csv');
        
        if (empty($files)) {
            return ['rows' => [], 'headers' => [], 'filename' => 'No CSV files found'];
        }
        
        // Get latest file by modification time
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $latestFile = $files[0];
        
        $handle = fopen($latestFile, 'r');
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
            'filename' => basename($latestFile),
            'timestamp' => filemtime($latestFile),
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
