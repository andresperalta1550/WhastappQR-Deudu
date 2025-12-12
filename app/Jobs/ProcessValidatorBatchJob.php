<?php

namespace App\Jobs;

use App\Models\User;
use App\Libraries\Whatsapp\Client;
use App\Models\ValidatorBatchTemp;
use App\Models\ValidatorBatch;
use App\Services\Excel\ExportDTO as ExcelExportDTO;
use App\Services\Excel\ExportService as ExcelExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Always search if the batch exists
        $batch = (new ValidatorBatch())
            ->findOrFail($this->batchId);

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
            $validationResult = $this->validatePhoneNumber($phoneNumber, $batch);
            Log::info('Validation result:', $validationResult);

            // Update the register with the result
            $record->update([
                'status' => ValidatorBatchTemp::STATUS_COMPLETED,
                'validation_result' => $validationResult,
                'processed_at' => now()
            ]);
        }

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
            if (!$validationResult['is_valid']) {
                $data[] = [
                    $record->getData()['celular'] ?? $record->getData()['telefono'] ?? null,
                    'NO',
                    'El número de télefono no es valido.'
                ];
                continue;
            }
            $phoneNumber = $record->getData()['celular'] ?? $record->getData()['telefono'] ?? null;
            $isValid = $validationResult['on_whatsapp'] ?? false;

            $data[] = [
                $phoneNumber,
                $isValid ? 'SI' : 'NO',
                !$isValid ? 'El número no se encuentra en whatsapp' : null
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
    private function validatePhoneNumber(string $phoneNumber, ValidatorBatch $batch): array
    {
        // We create the client with the phone number of the batch
        // the parameters in null is because the client is created with
        // environment variables for default values
        $client = new Client(
            phoneNumber: $batch->getPhoneNumber()
        );
        return $client->checkNumber($phoneNumber);
    }
}
