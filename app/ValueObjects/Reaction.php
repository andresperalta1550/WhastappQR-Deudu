<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject specific for reaction.
 */
class Reaction implements \JsonSerializable
{
    /**
     * @var string|null $emoji The emoji used in the reaction.
     */
    private ?string $emoji;

    /**
     * @var string|null $reactorPhoneNumber The phone number of the user who reacted.
     */
    private ?string $reactorPhoneNumber;

    /**
     * @var Carbon|null $createdAt The timestamp when the reaction was created.
     */
    private ?Carbon $createdAt;

    public function __construct(
        ?string $emoji = null,
        ?string $reactorPhoneNumber = null,
        ?Carbon $createdAt = null,
    ) {
        $this->emoji = $emoji;
        $this->reactorPhoneNumber = $reactorPhoneNumber;
        $this->createdAt = $createdAt;
    }

    /**
     * Create a Reaction instance from an associative array.
     *
     * @param array|null $data The associative array containing reaction data.
     * @return self|null A Reaction instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data)
            return null;
        return new self(
            $data['emoji'] ?? null,
            $data['reaction_phone_number'] ?? null,
            isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
        );
    }

    /**
     * Convert the Reaction instance to an associative array.
     *
     * @return array The associative array representation of the Reaction instance.
     */
    public function toArray(): array
    {
        return [
            'emoji' => $this->emoji,
            'reaction_phone_number' => $this->reactorPhoneNumber,
            'created_at' => $this->createdAt?->toDateTime(),
        ];
    }

    /**
     * Get the emoji.
     *
     * @return string|null
     */
    public function emoji(): ?string
    {
        return $this->emoji;
    }

    /**
     * Set the emoji.
     *
     * @param string|null $emoji
     */
    public function setEmoji(?string $emoji): void
    {
        $this->emoji = $emoji;
    }

    /**
     * Get the reactorPhoneNumber.
     *
     * @return string|null
     */
    public function reactorPhoneNumber(): ?string
    {
        return $this->reactorPhoneNumber;
    }

    /**
     * Set the reactorPhoneNumber.
     *
     * @param string|null $reactorPhoneNumber
     */
    public function setReactorPhoneNumber(?string $reactorPhoneNumber): void
    {
        $this->reactorPhoneNumber = $reactorPhoneNumber;
    }

    /**
     * Get the createdAt timestamp.
     *
     * @return Carbon|null
     */
    public function createdAt(): ?Carbon
    {
        return $this->createdAt;
    }

    /**
     * Set the createdAt timestamp.
     *
     * @param Carbon|null $createdAt
     */
    public function setCreatedAt(?Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
