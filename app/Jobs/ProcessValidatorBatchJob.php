<?php

namespace App\Jobs;

use App\Models\ValidatorBatchTemp;
use App\Models\ValidatorBatch;
use App\Services\Excel\ExportDTO as ExcelExportDTO;
use App\Services\Excel\ExportService as ExcelExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessValidatorBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public ExcelExportService $excelService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $batchId,
        public int $offset,
        public int $limit
    ) {
        $this->excelService = app(ExcelExportService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtain the records of the batch for processing
        $records = (new ValidatorBatchTemp())
            ->where('batch_id', $this->batchId)
            ->skip($this->offset)
            ->take($this->limit)
            ->get();

        foreach ($records as $record) {
            // Mark with processing status
            $record->update([
                'status' => ValidatorBatchTemp::STATUS_PROCESSING
            ]);

            $phoneNumber = $record->getData()['celular'] ?? $record->getData()['telefono'] ?? null;


            if (!$phoneNumber) {
                $record->update([
                    'status' => ValidatorBatchTemp::STATUS_FAILED,
                    'error' => ['El número de teléfono es obligatorio']
                ]);
                continue;
            }

            // Validate if the phone number is valid
            $validationResult = $this->validatePhoneNumber($phoneNumber);

            // Update the register with the result
            $record->update([
                'status' => ValidatorBatchTemp::STATUS_COMPLETED,
                'validation_result' => $validationResult,
                'processed_at' => now()
            ]);
        }

        $batch = (new ValidatorBatch())
            ->findOrFail($this->batchId);

        // Verify if all records have been processed
        if ($this->checkBatchCompletion($batch)) {
            // Generate the excel
            $filePath = $this->generateValidationReport($this->batchId);

            // Update the batch
            $batch->update([
                'status' => ValidatorBatch::STATUS_VALIDATED,
                'file_path' => $filePath
            ]);
        }
    }

    private function generateValidationReport(string $batchId): string
    {
        // Obtain the records of the batch
        $records = (new ValidatorBatchTemp())
            ->where('batch_id', $batchId)
            ->orderBy('row_number')
            ->get();

        // Prepare headers
        $headers = ['Celular', 'Validado', 'Motivo'];

        // Prepare data
        $data = [];
        foreach ($records as $record) {
            $validationResult = $record->getValidationResult() ?? [];
            $phoneNumber = $validationResult['celular'] ?? null;
            $isValid = $validationResult['isValid'] ?? false;
            $reason = $validationResult['reason'] ?? null;

            $data[] = [
                $phoneNumber,
                $isValid ? 'SI' : 'NO',
                $reason
            ];
        }

        // Created DTO and generate the excel
        $dto = new ExcelExportDTO(
            headers: $headers,
            data: $data,
            fileName: "validacion-batch-{$batchId}.xlsx",
            directory: "validator-batches"
        );

        // Generate the excel
        return $this->excelService->generate($dto);
    }

    private function checkBatchCompletion(ValidatorBatch $batch): bool
    {
        // Count the number of records with completed status
        $completedRecords = (new ValidatorBatchTemp())
            ->where('batch_id', $this->batchId)
            ->where('status', ValidatorBatchTemp::STATUS_COMPLETED)
            ->count();

        return $completedRecords === $batch->getTotalNumbers();
    }
    private function validatePhoneNumber(string $phoneNumber): array
    {
        // Simulación de respuesta
        $isValid = $this->mockValidation($phoneNumber);
        $reason = $isValid ? null : $this->getMockReason();

        return [
            'phoneNumber' => $phoneNumber,
            'isValid' => $isValid,
            'reason' => $reason
        ];
    }

    private function mockValidation(string $phoneNumber): bool
    {
        // Mock: 80% de números válidos
        return rand(1, 100) <= 80;
    }

    private function getMockReason(): string
    {
        $reasons = [
            'Número fuera de servicio',
            'Formato inválido',
            'Número no existe',
            'Operador no disponible'
        ];

        return $reasons[array_rand($reasons)];
    }
}
