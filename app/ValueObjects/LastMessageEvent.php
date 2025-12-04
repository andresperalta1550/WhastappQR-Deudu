<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject specific for last message events.
 */
class LastMessageEvent implements \JsonSerializable
{
    /**
     * @var Carbon|null $lastInboundAt The timestamp of the last inbound message.
     */
    private ?Carbon $lastInboundAt;

    /**
     * @var Carbon|null $lastOutboundAt The timestamp of the last outbound message.
     */
    private ?Carbon $lastOutboundAt;

    /**
     * @var Carbon|null $lastCheckNumberAt The timestamp of the last check number event.
     */
    private ?Carbon $lastCheckNumberAt;

    public function __construct(
        ?Carbon $lastInboundAt = null,
        ?Carbon $lastOutboundAt = null,
        ?Carbon $lastCheckNumberAt = null,
    ) {
        $this->lastInboundAt = $lastInboundAt;
        $this->lastOutboundAt = $lastOutboundAt;
        $this->lastCheckNumberAt = $lastCheckNumberAt;
    }

    /**
     * Create a LastMessageEvent instance from an associative array.
     *
     * @param array|null $data
     * @return self|null
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['last_messages_events'] ?? $data;

        return new self(
            isset($data['last_inbound_at']) ? Carbon::parse($data['last_inbound_at']) : null,
            isset($data['last_outbound_at']) ? Carbon::parse($data['last_outbound_at']) : null,
            isset($data['last_check_number_at']) ? Carbon::parse($data['last_check_number_at']) : null,
        );
    }

    /**
     * Convert the LastMessageEvent instance to an associative array.
     *
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            'last_messages_events' => [
                'last_inbound_at' => $this->lastInboundAt?->toDateTime(),
                'last_outbound_at' => $this->lastOutboundAt?->toDateTime(),
                'last_check_number_at' => $this->lastCheckNumberAt?->toDateTime(),
            ],
        ];
    }

    /**
     * @return Carbon|null
     */
    public function getLastInboundAt(): ?Carbon
    {
        return $this->lastInboundAt;
    }

    /**
     * @param Carbon|null $lastInboundAt
     */
    public function setLastInboundAt(?Carbon $lastInboundAt): void
    {
        $this->lastInboundAt = $lastInboundAt;
    }

    /**
     * @return Carbon|null
     */
    public function getLastOutboundAt(): ?Carbon
    {
        return $this->lastOutboundAt;
    }

    /**
     * @param Carbon|null $lastOutboundAt
     */
    public function setLastOutboundAt(?Carbon $lastOutboundAt): void
    {
        $this->lastOutboundAt = $lastOutboundAt;
    }

    /**
     * @return Carbon|null
     */
    public function getLastCheckNumberAt(): ?Carbon
    {
        return $this->lastCheckNumberAt;
    }

    /**
     * @param Carbon|null $lastCheckNumberAt
     */
    public function setLastCheckNumberAt(?Carbon $lastCheckNumberAt): void
    {
        $this->lastCheckNumberAt = $lastCheckNumberAt;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['last_messages_events'];
    }
}
