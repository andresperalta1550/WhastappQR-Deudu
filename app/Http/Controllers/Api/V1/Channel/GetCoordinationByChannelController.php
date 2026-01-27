<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;

class GetCoordinationByChannelController extends Controller
{
    public function __invoke(string $channel_phone_number): JsonResponse
    {
        $channel = Channel::where('phone_number', $channel_phone_number)
            ->where('enabled', true)
            ->first();

        if (!$channel) {
            return response()->json([
                'success' => false,
                'data' => 'El canal con el número ' . $channel_phone_number . ' no se ha encontrado',
            ], Response::HTTP_NOT_FOUND);
        }

        $coordination = User::where('id', $channel->getCoordinationId())
            ->first();

        $coordinationResponse = $coordination->toArray();
        $coordinationResponse['fullname'] = $coordination->getFullname();

        return response()->json([
            'success' => true,
            'data' => $coordinationResponse,
        ], Response::HTTP_OK);
    }
}
