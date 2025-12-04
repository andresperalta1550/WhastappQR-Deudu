<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetChannelController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Channel::all()
        ]);
    }
}
