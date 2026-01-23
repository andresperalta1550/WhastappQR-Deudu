<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch\Limits;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatorBatch\Limits\PutLimitRequest;
use App\Models\LimitsValidatorBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PutLimitController extends Controller
{
    public function __invoke(LimitsValidatorBatch $limit, PutLimitRequest $request): JsonResponse
    {
        $limit->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $limit
        ]);
    }
}
