<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatorBatch\ApproveValidatorBatchRequest;
use App\Jobs\ProcessValidatorBatchJob;
use App\Models\ValidatorBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApproveValidatorBatchController extends Controller
{
    public function __invoke(ValidatorBatch $batch, ApproveValidatorBatchRequest $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        // Validate if the batch is in pending status
        if ($batch->getStatus() !== ValidatorBatch::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'El lote de validación no se encuentra en estado "pendiente"',
                'error' => 'El lote de validación no se encuentra en estado "pendiente"'
            ], Response::HTTP_BAD_REQUEST);
        }

        $batch->update([
            'status' => ValidatorBatch::STATUS_IN_PROCESS,
            'approved_by' => $request->getApprovedBy(),
            'approved_at' => now()
        ]);

        // Obtain records of the batch for processing by chunks
        $totalRecords = $batch->getTotalNumbers();
        $chunkSize = 100;
        $totalChunks = ceil($totalRecords / $chunkSize);

        // Dispatch the jobs for processing the batch
        for ($i = 0; $i < $totalChunks; $i++) {
            // Process the chunk
            ProcessValidatorBatchJob::dispatch(
                batchId: $batch->getId(),
                offset: $i * $chunkSize,
                limit: $chunkSize
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Lote de validación aprobado exitosamente',
            'data' => [
                'batch_id' => $batch->getId(),
                'consecutive' => $batch->getConsecutive(),
                'total_records' => $batch->getTotalNumbers()
            ]
        ], Response::HTTP_OK);
    }
}
