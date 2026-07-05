<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class ExportService
{
    /**
     * Export a collection to CSV (Excel compatible)
     */
    public function exportToCsv(Collection $data, array $headers, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Response::streamDownload(function () use ($data, $headers) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($handle, array_values($headers));
            
            // Add data
            foreach ($data as $row) {
                $line = [];
                foreach (array_keys($headers) as $key) {
                    $value = data_get($row, $key, '');
                    
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    } elseif (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);
                    }
                    
                    // Ensure the value is a string and valid UTF-8
                    $value = (string) $value;
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                    
                    $line[] = $value;
                }
                fputcsv($handle, $line);
            }
            
            fclose($handle);
        }, $filename . '.csv');
    }

    /**
     * Export a collection to PDF
     */
    public function exportToPdf(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($view, $data, $paper, $orientation) {
            $pdf = Pdf::loadView($view, $data);
            $pdf->setPaper($paper, $orientation);
            echo $pdf->output();
        }, $filename . '.pdf');
    }
}
