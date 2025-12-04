<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject specific for delivery information.
 */
class Delivery implements \JsonSerializable
{
    /**
     * @var Carbon|null $sentAt The timestamp when the message was sent.
     */
    public ?Carbon $sentAt;

    /**
     * @var Carbon|null $deliveredAt The timestamp when the message was delivered.
     */
    public ?Carbon $deliveredAt;

    /**
     * @var Carbon|null $readAt The timestamp when the message was read.
     */
    public ?Carbon $readAt;

    public function __construct(
        ?Carbon $sentAt = null,
        ?Carbon $deliveredAt = null,
        ?Carbon $readAt = null,
    ) {
        $this->sentAt = $sentAt;
        $this->deliveredAt = $deliveredAt;
        $this->readAt = $readAt;
    }

    /**
     * Create a Delivery instance from an associative array.
     *
     * @param array|null $data The associative array containing delivery data.
     * @return self|null A Delivery instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data)
            return null;

        $data = $data['delivery'] ?? $data;

        return new self(
            isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : null,
            isset($data['delivered_at']) ? Carbon::parse($data['delivered_at']) : null,
            isset($data['read_at']) ? Carbon::parse($data['read_at']) : null,
        );
    }

    /**
     * Convert the Delivery instance to an associative array.
     *
     * @return array The associative array representation of the Delivery instance.
     */
    public function toArray(): array
    {
        return [
            'delivery' => [
                // In this case we have a problem with the external API "2Chat"
                // because they have a different timezone than us, so we need
                // change about that with the toDateTime() method.
                'sent_at' => $this->sentAt?->toDateTime(),
                'delivered_at' => $this->deliveredAt?->toDateTime(),
                'read_at' => $this->readAt?->toDateTime(),
            ]
        ];
    }

    /**
     * Get the sentAt timestamp.
     *
     * @return Carbon|null
     */
    public function sentAt(): ?Carbon
    {
        return $this->sentAt;
    }

    /**
     * Set the sentAt timestamp.
     *
     * @param Carbon|null $sentAt
     */
    public function setSentAt(?Carbon $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    /**
     * Get the deliveredAt timestamp.
     *
     * @return Carbon|null
     */
    public function deliveredAt(): ?Carbon
    {
        return $this->deliveredAt;
    }

    /**
     * Set the deliveredAt timestamp.
     *
     * @param Carbon|null $deliveredAt
     */
    public function setDeliveredAt(?Carbon $deliveredAt): void
    {
        $this->deliveredAt = $deliveredAt;
    }

    /**
     * Get the readAt timestamp.
     *
     * @return Carbon|null
     */
    public function readAt(): ?Carbon
    {
        return $this->readAt;
    }

    /**
     * Set the readAt timestamp.
     * @param Carbon|null $readAt
     */
    public function setReadAt(?Carbon $readAt): void
    {
        $this->readAt = $readAt;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['delivery'];
    }
}
