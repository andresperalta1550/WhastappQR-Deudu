<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for normalized phone numbers.
 */
class NormalizedNumber implements \JsonSerializable
{
    /**
     * In this case if we have iso_country_code in number_info,
     * use it to fill this field. And, we use this normalized number
     * for search in aquila or oracle systems.
     * @var string|null $countryCode The country code of the phone number.
     */
    public ?string $countryCode;

    /**
     * Same thing as above, use the city/area code from number_info.
     *
     * @var string|null $cityCode The city/area code of the phone number.
     */
    public ?string $cityCode;

    /**
     * @var string|null $number The normalized phone number without country and city codes.
     */
    public ?string $number;

    public function __construct(
        ?string $countryCode,
        ?string $cityCode,
        ?string $number,
    ) {
        $this->countryCode = $countryCode;
        $this->cityCode = $cityCode;
        $this->number = $number;
    }

    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['normalized_number'] ?? $data;

        return new self(
            $data['country_code'] ?? '',
            $data['city_code'] ?? '',
            $data['number'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'normalized_number' => [
                'country_code' => $this->countryCode,
                'city_code' => $this->cityCode,
                'number' => $this->number,
            ]
        ];
    }

    /**
     * Get the value of countryCode.
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Set the value of countryCode.
     *
     * @param string $countryCode
     */
    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    /**
     * Get the value of cityCode.
     *
     * @return string
     */
    public function getCityCode(): string
    {
        return $this->cityCode;
    }

    /**
     * Set the value of cityCode.
     *
     * @param string $cityCode
     */
    public function setCityCode(string $cityCode): void
    {
        $this->cityCode = $cityCode;
    }

    /**
     * Get the value of number.
     *
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the value of number.
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['normalized_number'];
    }
}
