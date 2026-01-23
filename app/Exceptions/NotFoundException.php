<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends Exception
{
    protected $message;
    protected $code;

    public function __construct($message, $code = Response::HTTP_NOT_FOUND)
    {
        $this->message = $message;
        $this->code = $code;
        parent::__construct($message, $code);
    }

    /**
     * Render the exception as a JSON response.

     * @param mixed $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => $this->message
        ], $this->code);
    }

    public function report(): void
    {

    }
}
