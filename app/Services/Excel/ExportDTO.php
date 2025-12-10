<?php

namespace App\Services\Excel;

readonly class ExportDTO
{
    /**
     * @param array<string> $headers - Array of column names
     * @param array<array<string|int|float|bool|null>> $data - Array of rows with data
     * @param string $fileName - File name without extension
     * @param string $directory - Directory where the file will be saved (relative to storage/app/public)
     */
    public function __construct(
        public array $headers,
        public array $data,
        public string $fileName,
        public string $directory = 'exports'
    ) {
    }
}