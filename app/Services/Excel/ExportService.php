<?php

namespace App\Services\Excel;

use App\Services\Excel\ExportDTO as ExcelExportDTO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Generate a Excel file generic.
     * 
     * @param ExcelExportDTO $exportData
     * @return string Public path of the generated file
     */
    public function generate(ExcelExportDTO $exportData): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        $this->writeHeaders($sheet, $exportData->headers);

        // Write data
        $this->writeData($sheet, $exportData->data);

        // Auto-size de columnas
        $this->autoSizeColumns($sheet, count($exportData->headers));

        // Save file
        return $this->saveFile($spreadsheet, $exportData->fileName, $exportData->directory);
    }

    /**
     * Write headers in the first row
     * 
     * @param Worksheet $sheet
     * @param array<string> $headers
     * @return void
     */
    private function writeHeaders(Worksheet $sheet, array $headers): void
    {
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Apply styles
        $lastColumn = chr(ord('A') + count($headers) - 1);
        $headerRange = 'A1:' . $lastColumn . '1';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8E8E8']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    /**
     * Write data starting from the second row
     * 
     * @param Worksheet $sheet
     * @param array<mixed> $data
     * @return void
     */
    private function writeData(Worksheet $sheet, array $data): void
    {
        $row = 2;
        foreach ($data as $rowData) {
            $column = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }
    }

    /**
     * Adjusts automatically the column width
     * 
     * @param Worksheet $sheet
     * @param int $columnCount
     * @return void
     */
    private function autoSizeColumns(Worksheet $sheet, int $columnCount): void
    {
        for ($i = 0; $i < $columnCount; $i++) {
            $column = chr(ord('A') + $i);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Save the file and return the public path
     * 
     * @param Spreadsheet $spreadsheet
     * @param string $fileName
     * @param string $directory
     * @return string
     */
    private function saveFile(Spreadsheet $spreadsheet, string $fileName, string $directory): string
    {
        // Generate unique file name with timestamp
        $fileName = $fileName . '_' . time() . '.xlsx';
        $relativePath = $directory . '/' . $fileName;
        $fullPath = storage_path('app/public/' . $relativePath);

        // Create directory if it doesn't exist
        $directoryPath = dirname($fullPath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Save file
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // Return public path
        return Storage::url($relativePath);
    }
}