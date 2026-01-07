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

            $file = $request->file('file');

            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo inválido o no se pudo cargar correctamente'
                ], Response::HTTP_BAD_REQUEST);
            }

            $consecutive = (new ValidatorBatch())->generateConsecutive();

            $batch = (new ValidatorBatch())
                ->create([
                    'consecutive' => $consecutive,
                    'status' => 'pending',
                    'total_records' => 0,
                    'processed_records' => 0,
                    'created_by' => $request->getCreatedBy(),
                    'created_at' => now(),
                    'phone_number' => $request->getPhoneNumber()
                ]);

            try {
                $reader = IOFactory::createReaderForFile($file->getRealPath());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getRealPath());
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                throw new \Exception('Error al leer el archivo Excel: El archivo puede estar corrupto o tener un formato inválido');
            }

            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                throw new \Exception('El archivo no contiene datos válidos');
            }

            // Remove and validate headers
            $rawHeaders = array_shift($rows);

            // Limpiar headers y mantener el índice original
            $headers = [];
            $hasValidHeaders = false;

            foreach ($rawHeaders as $index => $header) {
                $cleanHeader = trim($header);
                if (!empty($cleanHeader)) {
                    $headers[$index] = $cleanHeader;
                    $hasValidHeaders = true;
                }
            }

            if (!$hasValidHeaders) {
                throw new \Exception('El archivo no contiene encabezados válidos. Asegúrate de que la primera fila tenga nombres de columna.');
            }

            $tempRecords = [];
            $totalRecords = 0;
            $skippedRows = 0;

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) {
                    $skippedRows++;
                    continue;
                }

                $mappedData = $this->mapExcelRowToData($row, $headers);

                // Validar que el mapeo generó datos válidos
                if (empty($mappedData)) {
                    $skippedRows++;
                    continue;
                }

                $tempRecords[] = [
                    'batch_id' => $batch->getId(),
                    'row_number' => $index + 2,
                    'data' => $mappedData,
                    'status' => 'pending',
                    'errors' => null
                ];

                $totalRecords++;

                if (count($tempRecords) >= 1000) {
                    (new ValidatorBatchTemp())->insert($tempRecords);
                    $tempRecords = [];
                }
            }

            if (!empty($tempRecords)) {
                (new ValidatorBatchTemp())->insert($tempRecords);
            }

            if ($totalRecords === 0) {
                throw new \Exception('No se encontraron registros válidos para procesar en el archivo');
            }

            $batch->setTotalNumbers($totalRecords);
            $batch->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lote de validación creado exitosamente',
                'data' => [
                    'batch_id' => $batch->getId(),
                    'consecutive' => $consecutive,
                    'total_records' => $totalRecords,
                    'skipped_rows' => $skippedRows
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating validator batch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $file ? $file->getClientOriginalName() : null
            ]);

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
     * @param array $headers - The headers of the Excel file (sparse array with valid headers).
     */
    private function mapExcelRowToData(array $row, array $headers): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            // Convertir a snake_case y limpiar
            $key = strtolower(trim(str_replace(' ', '_', $header)));

            // Remover caracteres especiales excepto guión bajo
            $key = preg_replace('/[^a-z0-9_]/', '', $key);

            // Solo agregar si la clave no está vacía
            if (!empty($key)) {
                $data[$key] = $row[$index] ?? null;
            }
        }

        return $data;
    }
}
