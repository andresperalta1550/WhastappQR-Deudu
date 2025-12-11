<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatorBatch\CreateValidatorBatchRequest;
use App\Models\User;
use App\Models\ValidatorBatchTemp;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ValidatorBatch;
use Illuminate\Http\Request;

class CreateValidatorBatchController extends Controller
{
    public function __invoke(
        CreateValidatorBatchRequest $request
    ): \Illuminate\Http\JsonResponse {
        try {
            DB::beginTransaction();

            // Generate the consecutive for the batch
            $consecutive = (new ValidatorBatchTemp())->generateConsecutive();

            // Verify if the user have coordination
            // $user = (new User())->find($request->getCreatedBy())?->getCoordinationId();
            // if (!$user) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'El usuario no tiene una coordinación asignada',
            //     ], Response::HTTP_BAD_REQUEST);
            // }

            // Create the validator batch
            $batch = (new ValidatorBatch())
                ->create([
                    'consecutive' => $consecutive,
                    'status' => 'pending',
                    'total_records' => 0,
                    'processed_records' => 0,
                    'created_by' => $request->getCreatedBy(),
                    'created_at' => now()
                ]);

            // Process the excel file
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove the first row (headers)
            $headers = array_shift($rows);

            $tempRecords = [];
            $totalRecords = 0;

            // Prepare the data for insertion in the ValidatorBatchTemp
            foreach ($rows as $index => $row) {
                // Skip the rows empty
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map the data
                $tempRecords[] = [
                    'batch_id' => $batch->getId(),
                    'row_number' => $index + 2, // +2 because we initiated with 1 and delete the header
                    'data' => $this->mapExcelRowToData($row, $headers),
                    'status' => 'pending',
                    'errors' => null
                ];

                $totalRecords++;

                // Insert the batch of 1000 records for optimize
                if (count($tempRecords) >= 1000) {
                    (new ValidatorBatchTemp())
                        ->insert($tempRecords);
                    $tempRecords = [];
                }
            }

            // Insert the last data
            if (!empty($tempRecords)) {
                (new ValidatorBatchTemp())
                    ->insert($tempRecords);
            }

            // Update the batch
            $batch->setTotalNumbers($totalRecords);
            $batch->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lote de validación creado exitosamente',
                'data' => [
                    'batch_id' => $batch->getId(),
                    'consecutive' => $consecutive,
                    'total_records' => $totalRecords
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el lote de validación',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Map the row of Excel to data structure.
     * 
     * @param array $row - The actual row.
     * @param array $headers - The headers of the Excel file.
     */
    private function mapExcelRowToData(array $row, array $headers): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim(str_replace(' ', '_', $header)));
            $data[$key] = $row[$index] ?? null;
        }

        return $data;
    }
}
