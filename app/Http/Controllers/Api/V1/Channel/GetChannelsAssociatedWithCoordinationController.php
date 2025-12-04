<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;

class GetChannelsAssociatedWithCoordinationController extends Controller
{
    public function __invoke(int $coordinationId)
    {
        $channels = (new Channel())
            ->where('coordination_id', $coordinationId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }
}
