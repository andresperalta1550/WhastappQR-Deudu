<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * That class represents a Bad Request Exception (HTTP 400).
 *
 * @package App\Exceptions
 */
class BadRequestException extends Exception
{
    protected $message;
    protected $code;

    public function __construct(string $message = "Bad Request", int $code = 400)
    {
        $this->message = $message;
        $this->code = $code;
        parent::__construct($this->message, $this->code);
    }

    /**
     * Render the exception as a JSON response.
     *
     * @param $request
     * @return JsonResponse
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
        ], $this->code);
    }

    public function report(): void
    {
        // You can log the exception here if needed
    }
}
