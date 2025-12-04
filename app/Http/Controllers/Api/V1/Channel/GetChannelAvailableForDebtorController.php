<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;
use App\Models\Channel;

class GetChannelAvailableForDebtorController extends Controller
{
    public function __invoke(int $coordinationId)
    {
        $channel = (new Channel())
            ->where('coordination_id', $coordinationId)
            ->where('connection_status', 'C')
            ->orderBy('priority', 'asc')
            ->first();

        if (!$channel) {
            throw new BadRequestException(
                "No se encontro un canal disponible para la coordinacion $coordinationId"
            );
        }

        return response()->json([
            'success' => true,
            'data' => $channel,
        ]);
    }
}
