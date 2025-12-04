<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for WhatsApp information.
 */
class WhatsappInfo implements \JsonSerializable
{
    /**
     * @var string|null $numberId The WhatsApp number ID.
     */
    private ?string $numberId;

    /**
     * @var bool|null $isBusiness Indicates if the number is a business account.
     */
    private ?bool $isBusiness;

    /**
     * @var bool|null $isEnterprise Indicates if the number is an enterprise account.
     */
    private ?bool $isEnterprise;

    /**
     * @var int|null $verifiedLevel The verification level of the WhatsApp account.
     */
    private ?int $verifiedLevel;

    /**
     * @var string|null $verifiedName The verified name of the WhatsApp account.
     */
    private ?string $verifiedName;

    /**
     * @var string|null $statusText The status text of the WhatsApp account.
     */
    private ?string $statusText;

    public function __construct(
        ?string $numberId = null,
        ?bool $isBusiness = null,
        ?bool $isEnterprise = null,
        ?int $verifiedLevel = null,
        ?string $verifiedName = null,
        ?string $statusText = null,
    ) {
        $this->numberId = $numberId;
        $this->isBusiness = $isBusiness;
        $this->isEnterprise = $isEnterprise;
        $this->verifiedLevel = $verifiedLevel;
        $this->verifiedName = $verifiedName;
        $this->statusText = $statusText;
    }

    /**
     * Create a WhatsappInfo instance from an associative array.
     *
     * @param array|null $data
     * @return self|null
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['whatsapp_info'] ?? $data;

        return new self(
            $data['number_id'] ?? null,
            $data['is_business'] ?? null,
            $data['is_enterprise'] ?? null,
            $data['verified_level'] ?? null,
            $data['verified_name'] ?? null,
            $data['status_text'] ?? null,
        );
    }

    /**
     * Convert the WhatsappInfo instance to an associative array.
     *
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            'whatsapp_info' => [
                'number_id' => $this->numberId,
                'is_business' => $this->isBusiness,
                'is_enterprise' => $this->isEnterprise,
                'verified_level' => $this->verifiedLevel,
                'verified_name' => $this->verifiedName,
                'status_text' => $this->statusText,
            ]
        ];
    }

    /**
     * @return string|null
     */
    public function getNumberId(): ?string
    {
        return $this->numberId;
    }

    /**
     * @param string|null $numberId
     */
    public function setNumberId(?string $numberId): void
    {
        $this->numberId = $numberId;
    }

    /**
     * @return bool|null
     */
    public function getIsBusiness(): ?bool
    {
        return $this->isBusiness;
    }

    /**
     * @param bool|null $isBusiness
     */
    public function setIsBusiness(?bool $isBusiness): void
    {
        $this->isBusiness = $isBusiness;
    }

    /**
     * @return bool|null
     */
    public function getIsEnterprise(): ?bool
    {
        return $this->isEnterprise;
    }

    /**
     * @param bool|null $isEnterprise
     */
    public function setIsEnterprise(?bool $isEnterprise): void
    {
        $this->isEnterprise = $isEnterprise;
    }

    /**
     * @return int|null
     */
    public function getVerifiedLevel(): ?int
    {
        return $this->verifiedLevel;
    }

    /**
     * @param int|null $verifiedLevel
     */
    public function setVerifiedLevel(?int $verifiedLevel): void
    {
        $this->verifiedLevel = $verifiedLevel;
    }

    /**
     * @return string|null
     */
    public function getVerifiedName(): ?string
    {
        return $this->verifiedName;
    }

    /**
     * @param string|null $verifiedName
     */
    public function setVerifiedName(?string $verifiedName): void
    {
        $this->verifiedName = $verifiedName;
    }

    /**
     * @return string|null
     */
    public function getStatusText(): ?string
    {
        return $this->statusText;
    }

    /**
     * @param string|null $statusText
     */
    public function setStatusText(?string $statusText): void
    {
        $this->statusText = $statusText;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['whatsapp_info'];
    }
}
