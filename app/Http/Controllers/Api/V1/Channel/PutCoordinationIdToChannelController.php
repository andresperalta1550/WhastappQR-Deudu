<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\PutCoordinationIdToChannelRequest;
use App\Models\Channel;

class PutCoordinationIdToChannelController extends Controller
{
    public function __invoke(Channel $channel, PutCoordinationIdToChannelRequest $request)
    {
        $channel->update([
            'coordination_id' => $request->getCoordinationId()
        ]);

        return response()->json([
            'success' => true,
            'data' => $channel
        ]);
    }
}
