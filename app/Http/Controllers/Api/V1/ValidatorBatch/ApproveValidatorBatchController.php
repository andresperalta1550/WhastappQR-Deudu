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

        // Dispatch the job for processing the batch
        // Before we put multiple batch por use correctly 
        ProcessValidatorBatchJob::dispatch(
            batchId: $batch->getId()
        );

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
