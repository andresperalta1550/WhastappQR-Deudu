<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channel\PutChannelRequest;
use App\Models\Channel;

class PutChannelController extends Controller
{
    public function __invoke(Channel $channel, PutChannelRequest $request)
    {
        $channel->update([
            'coordination_id' => $request->getCoordinationId(),
            'priority' => $request->getPriority()
        ]);

        return response()->json([
            'success' => true,
            'data' => $channel
        ]);
    }
}
