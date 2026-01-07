<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatorBatch\ApproveValidatorBatchRequest;
use App\Models\ValidatorBatch;
use Symfony\Component\HttpFoundation\Response;

class RejectValidatorBatchController extends Controller
{
    public function __invoke(ValidatorBatch $batch, ApproveValidatorBatchRequest $request)
    {
        $batch->update([
            'status' => ValidatorBatch::STATUS_REJECTED,
            'approved_at' => now(),
            'approved_by' => $request->getApprovedBy()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lote de validación rechazado exitosamente'
        ], Response::HTTP_OK);
    }
}
