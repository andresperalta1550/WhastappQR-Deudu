<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Libraries\Whatsapp\Webhook\WebhookParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $parser = new WebhookParser();
            $event = $parser->parse($request);
            $event->process();

            return response()->json([
                'success' => true,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
