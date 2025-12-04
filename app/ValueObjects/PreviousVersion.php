<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject specific for previous version information
 * when a message has been edited.
 */
class PreviousVersion implements \JsonSerializable
{
    private ?string $text;

    private ?Carbon $editedAt;

    public function __construct(
        ?string $text = null,
        ?Carbon $editedAt = null,
    ) {
        $this->text = $text;
        $this->editedAt = $editedAt;
    }

    /**
     * Create a PreviousVersion instance from an associative array.
     *
     * @param array|null $data The associative array containing previous version data.
     * @return self|null A PreviousVersion instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        return new self(
            $data['text'] ?? null,
            isset($data['editedAt']) ? Carbon::parse($data['editedAt']) : null,
        );
    }

    /**
     * Convert the PreviousVersion instance to an associative array.
     *
     * @return array The associative array representation of the PreviousVersion instance.
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'editedAt' => $this->editedAt?->toIso8601String(),
        ];
    }

    /**
     * Get the text of the previous version.
     *
     * @return string|null
     */
    public function text(): ?string
    {
        return $this->text;
    }

    /**
     * Set the text of the previous version.
     *
     * @param string|null $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Get the editedAt timestamp.
     *
     * @return Carbon|null
     */
    public function editedAt(): ?Carbon
    {
        return $this->editedAt;
    }

    /**
     * Set the editedAt timestamp.
     *
     * @param Carbon|null $editedAt
     */
    public function setEditedAt(?Carbon $editedAt): void
    {
        $this->editedAt = $editedAt;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
