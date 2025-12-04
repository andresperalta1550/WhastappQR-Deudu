<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Actions\StoreMessageAction;
use App\Exceptions\BadRequestException;
use App\Exceptions\ExternalServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PostMessageController extends Controller
{
    /**
     * Handle the incoming request to send a message.
     *
     * @throws BadRequestException
     * @throws ExternalServiceException
     * @throws ConnectionException
     */
    public function __invoke(SendMessageRequest $request, StoreMessageAction $storeMessageAction): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('message.data.file')) {
            $file = $request->file('message.data.file');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $filename, 'public');
            $url = Storage::url($path);

            // Ensure the URL is absolute if needed, or rely on Storage::url returning a relative path that works for the frontend/client
            // Ideally, for WhatsApp, we might need a full URL.
            // Storage::url usually returns /storage/path. Let's prepend app url if it's relative.
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $url = config('app.url') . $url;
            }

            $data['message']['data']['url'] = $url;
        }

        $message = $storeMessageAction->handle($data);

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ], Response::HTTP_CREATED);
    }
}
