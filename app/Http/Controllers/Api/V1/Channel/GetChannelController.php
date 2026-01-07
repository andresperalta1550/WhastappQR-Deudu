<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\GetChannelRequest;
use App\Models\Channel;
use App\Services\QueryFilterService;
use Illuminate\Http\JsonResponse;

class GetChannelController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param GetChannelRequest $request
     * @param QueryFilterService $filterService
     * @return JsonResponse
     */
    public function __invoke(GetChannelRequest $request, QueryFilterService $filterService): JsonResponse
    {
        $query = Channel::with('coordination');

        // Apply filters if provided
        $filters = $request->getFilters();
        $filterService->apply($query, $filters);

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }
}
