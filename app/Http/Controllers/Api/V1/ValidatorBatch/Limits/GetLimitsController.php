<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch\Limits;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatorBatch\Limits\GetLimitsRequest;
use App\Models\LimitsValidatorBatch;
use App\Services\QueryFilterService;
use Illuminate\Http\JsonResponse;

/**
 * Get the limits configuration of the validator batch.
 */
class GetLimitsController extends Controller
{
    public function __invoke(
        GetLimitsRequest $request,
        QueryFilterService $filterService
    ): JsonResponse {
        $query = LimitsValidatorBatch::query();

        $filterService->apply($query, $request->getFilters());

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }
}
