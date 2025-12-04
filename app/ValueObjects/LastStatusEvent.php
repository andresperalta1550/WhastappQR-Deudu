<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject for the last status event.
 */
class LastStatusEvent implements \JsonSerializable
{
    /**
     * @var string|null $event The event type.
     */
    private ?string $event;

    /**
     * @var string|null $qrCode The QR code associated with the event.
     */
    private ?string $qrCode;

    /**
     * @var Carbon|null $timestamp The timestamp of the event.
     */
    private ?Carbon $timestamp;

    public function __construct(
        ?string $event = null,
        ?string $qrCode = null,
        ?Carbon $timestamp = null,
    ) {
        $this->event = $event;
        $this->qrCode = $qrCode;
        $this->timestamp = $timestamp;
    }

    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['last_status_event'] ?? $data;

        return new self(
            $data['event'] ?? null,
            $data['qr_code'] ?? null,
            isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'last_status_event' => [
                'event' => $this->event,
                'qr_code' => $this->qrCode,
                'timestamp' => $this->timestamp?->toISOString(),
            ]
        ];
    }

    /**
     * Get the value of event.
     *
     * @return string|null
     */
    public function event(): ?string
    {
        return $this->event;
    }

    /**
     * Set the value of event.
     *
     * @param string|null $event
     */
    public function setEvent(?string $event): void
    {
        $this->event = $event;
    }

    /**
     * Get the value of timestamp.
     *
     * @return Carbon|null
     */
    public function timestamp(): ?Carbon
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp.
     *
     * @param Carbon|null $timestamp
     */
    public function setTimestamp(?Carbon $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get the value of qr code.
     *
     * @return string|null
     */
    public function qrCode(): ?string
    {
        return $this->qrCode;
    }

    /**
     * Set the value of qr code.
     *
     * @param string|null $qrCode
     */
    public function setQrCode(?string $qrCode): void
    {
        $this->qrCode = $qrCode;
    }

    /**
     * Serialize the object to a value that can be
     * serialized natively by json_encode().
     *
     * @return array|null
     */
    public function jsonSerialize(): ?array
    {
        return [
            'event' => $this->event,
            'qr_code' => $this->qrCode,
            'timestamp' => $this->timestamp?->toISOString(),
        ];
    }
}
