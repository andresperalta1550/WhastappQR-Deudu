<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * This class represents a ValueObject specific for edited items.
 */
class Edited implements \JsonSerializable
{
    /**
     * @var bool|null $isEdited Indicates whether the item has been edited.
     */
    private ?bool $isEdited;

    /**
     * @var Carbon|null $editedAt The timestamp when the item was edited.
     */
    private ?Carbon $editedAt;

    /**
     * @var PreviousVersion[] $previousVersions An array of PreviousVersion objects representing the previous
     * versions of the edited item.
     */
    private array $previousVersions;

    public function __construct(
        ?bool $isEdited = null,
        ?Carbon $editedAt = null,
        array $previousVersions = [],
    ) {
        $this->isEdited = $isEdited;
        $this->editedAt = $editedAt;
        $this->previousVersions = $previousVersions;
    }

    /**
     * Create a Edited instance from an associative array.
     *
     * @param array|null $data The associative array containing edited data.
     * @return self|null A Edited instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data)
            return null;

        $previousVersions = [];
        if (isset($data['previous_versions']) && is_array($data['previous_versions'])) {
            foreach ($data['previous_versions'] as $versionData) {
                $prev = PreviousVersion::fromArray($versionData);
                if ($prev)
                    $previousVersions[] = $prev;
            }
        }

        return new self(
            $data['is_edited'] ?? null,
            isset($data['edited_at']) ? Carbon::parse($data['edited_at']) : null,
            $previousVersions,
        );
    }

    /**
     * Convert the Edited instance to an associative array.
     *
     * @return array The associative array representation of the Edited instance.
     */
    public function toArray(): array
    {
        return [
            'edited' => [
                'is_edited' => $this->isEdited,
                'edited_at' => $this->editedAt?->toISOString(),
                'previous_versions' => array_map(fn($v) => $v->toArray(), $this->previousVersions),
            ]
        ];
    }

    /**
     * Get the previousVersions.
     *
     * @return PreviousVersion[]
     */
    public function getPreviousVersions(): array
    {
        return $this->previousVersions;
    }

    /**
     * Set the previousVersions.
     *
     * @param PreviousVersion[] $previousVersions
     */
    public function setPreviousVersions(array $previousVersions): void
    {
        $this->previousVersions = $previousVersions;
    }

    /**
     * Get the editedAt timestamp.
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

    /**
     * Get the isEdited flag.
     * @return bool|null
     */
    public function isEdited(): ?bool
    {
        return $this->isEdited;
    }

    /**
     * Set the isEdited flag.
     *
     * @param bool|null $isEdited
     */
    public function setIsEdited(?bool $isEdited): void
    {
        $this->isEdited = $isEdited;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
