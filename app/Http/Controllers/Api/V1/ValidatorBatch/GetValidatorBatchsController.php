<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Models\ValidatorBatch;

class GetValidatorBatchsController extends Controller
{
    public function __invoke(PaginationRequest $request): \Illuminate\Http\JsonResponse
    {
        $result = ValidatorBatch::getPaginatedWithUsers(
            perPage: $request->getPerPage(),
            page: $request->getPage(),
            sortBy: $request->getSortBy(),
            sortOrder: $request->getSortOrder(),
            search: $request->getSearch()
        );

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
