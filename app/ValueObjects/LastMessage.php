<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for the last message details.
 * We need to keep this structure as simple as possible to avoid overloading
 * the responses.
 *
 * I don't have the designs yet, but I think this information will be
 * sufficient for the first version. If u want more details, we can always
 * extend this class later.
 */
class LastMessage implements \JsonSerializable
{
    /**
     * @var string|null $direction The direction of the last message (e.g., 'inbound' or 'outbound').
     */
    private ?string $direction;

    /**
     * @var string|null $type The type of the last message (e.g., 'text', 'image', etc.).
     */
    private ?string $type;

    /**
     * @var string|null $text The text content of the last message.
     */
    private ?string $text;

    /**
     * @var string|null $status The status of the last message (e.g., 'sent', 'delivered', 'read').
     */
    private ?string $status;

    public function __construct(
        ?string $direction = null,
        ?string $type = null,
        ?string $text = null,
        ?string $status = null,
    ) {
        $this->direction = $direction;
        $this->type = $type;
        $this->text = $text;
        $this->status = $status;
    }

    /**
     * Create a LastMessage instance from an associative array.
     *
     * @param array|null $data
     * @return self|null
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['last_message'] ?? $data;

        return new self(
            $data['direction'] ?? null,
            $data['type'] ?? null,
            $data['text'] ?? null,
            $data['status'] ?? null,
        );
    }

    /**
     * Convert the LastMessage instance to an associative array.
     *
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            'last_message' => [
                'direction' => $this->direction,
                'type' => $this->type,
                'text' => $this->text,
                'status' => $this->status,
            ]
        ];
    }

    /**
     * @return string|null
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

    /**
     * @param string|null $direction
     */
    public function setDirection(?string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['last_message'];
    }
}
