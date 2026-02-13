<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific to quoted messages.
 */
class Quoted
{
    /**
     * @var string|null $message The message that was quoted.
     */
    public ?string $message;

    public function __construct(
        ?string $message = null
    ) {
        $this->message = $message;
    }

    /**
     * Create a Quoted instance from an associative array.
     *
     * @param array|null $data The associative array containing quoted data.
     * @return self|null A Quoted instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data)
            return null;

        $data = $data['quoted'] ?? $data;

        return new self(
            $data['message'] ?? null,
        );
    }

    /**
     * Convert the Quoted instance to an associative array.
     *
     * @return array The associative array representation of the Quoted instance.
     */
    public function toArray(): array
    {
        return [
            'quoted' => [
                'message' => $this->message,
            ]
        ];
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }
}