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
                "No existe un canal de comunicación asignado para la coordinación de este deudor. Por favor, configure un canal válido para continuar con el proceso."
            );
        }

        return response()->json([
            'success' => true,
            'data' => $channel,
        ]);
    }
}
