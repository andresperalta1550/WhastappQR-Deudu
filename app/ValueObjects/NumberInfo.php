<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for phone number information.
 */
class NumberInfo implements \JsonSerializable
{
    /**
     * @var string|null The ISO country code associated with the phone number.
     */
    private ?string $isoCountryCode;

    /**
     * @var string|null The carrier name associated with the phone number.
     */
    private ?string $carrier;

    /**
     * @var string|null The timezone associated with the phone number.
     */
    private ?string $timezone;

    public function __construct(
        ?string $isoCountryCode = null,
        ?string $carrier = null,
        ?string $timezone = null,
    ) {
        $this->isoCountryCode = $isoCountryCode;
        $this->carrier = $carrier;
        $this->timezone = $timezone;
    }

    /**
     * Create a NumberInfo instance from an associative array.
     *
     * @param array|null $data
     * @return self|null
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['number_info'] ?? $data;

        return new self(
            $data['iso_country_code'] ?? '',
            $data['carrier'] ?? null,
            $data['timezone'] ?? null,
        );
    }

    /**
     * Convert the NumberInfo instance to an associative array.
     *
     * @return array[] The associative array representation of the NumberInfo instance.
     */
    public function toArray(): array
    {
        return [
            'number_info' => [
                'iso_country_code' => $this->isoCountryCode,
                'carrier' => $this->carrier,
                'timezone' => $this->timezone,
            ],
        ];
    }

    /**
     * @return string|null
     */
    public function getIsoCountryCode(): ?string
    {
        return $this->isoCountryCode;
    }

    /**
     * @param string|null $isoCountryCode
     */
    public function setIsoCountryCode(?string $isoCountryCode): void
    {
        $this->isoCountryCode = $isoCountryCode;
    }

    /**
     * @return string|null
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @param string|null $carrier
     */
    public function setCarrier(?string $carrier): void
    {
        $this->carrier = $carrier;
    }

    /**
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @param string|null $timezone
     */
    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['number_info'];
    }
}
