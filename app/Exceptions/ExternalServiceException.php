<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * That exception is thrown when an external service returns an error.
 * It includes the service name for better debugging.
 *
 * @package App\Exceptions
 */
class ExternalServiceException extends Exception
{
    protected $message;
    protected $code;
    protected ?string $service;

    public function __construct(
        string $message = "Error en el servicio externo",
        int $code = 502,
        ?string $service = null
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->service = $service;
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
        $response = [
            'success' => false,
            'message' => $this->message,
        ];

        if ($this->service) {
            $response['service'] = $this->service;
        }

        return response()->json($response, $this->code);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @return bool
     */
    public function report(): bool
    {
        return true;
    }

    /**
     * Get the context for logging.
     *
     * @return array
     */
    public function context(): array
    {
        return [
            'service' => $this->service,
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
