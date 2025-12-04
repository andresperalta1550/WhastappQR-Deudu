<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for media.
 */
class Media implements \JsonSerializable
{
    /**
     * @var string|null The URL of the media.
     */
    public ?string $url;

    /**
     * @var string|null The type of the media.
     */
    public ?string $type;

    /**
     * @var string|null The MIME type of the media.
     */
    public ?string $mimeType;

    /**
     * @var int|null The size of the media in bytes.
     */
    public ?int $sizeBytes;

    public function __construct(
        ?string $url = null,
        ?string $type = null,
        ?string $mimeType = null,
        ?int $sizeBytes = null,
    ) {
        $this->url = $url;
        $this->type = $type;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
    }

    /**
     * Create a Media instance from an associative array.
     *
     * @param array|null $data The associative array containing media data.
     * @return self|null A Media instance or null if the input data is null.
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        $data = $data['media'] ?? $data;

        return new self(
            $data['url'] ?? null,
            $data['type'] ?? null,
            $data['mime_type'] ?? null,
            $data['size_bytes'] ?? null,
        );
    }

    /**
     * Convert the Media instance to an associative array.
     *
     * @return array The associative array representation of the Media instance.
     */
    public function toArray(): array
    {
        return [
            'media' => [
                'url' => $this->url,
                'type' => $this->type,
                'mime_type' => $this->mimeType,
                'size_bytes' => $this->sizeBytes,
            ]
        ];
    }

    /**
     * Get the size of the media in bytes.
     *
     * @return int|null
     */
    public function sizeBytes(): ?int
    {
        return $this->sizeBytes;
    }

    /**
     * Set the size of the media in bytes.
     *
     * @param int|null $sizeBytes
     */
    public function setSizeBytes(?int $sizeBytes): void
    {
        $this->sizeBytes = $sizeBytes;
    }

    /**
     * Get the MIME type of the media.
     *
     * @return string|null
     */
    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Set the MIME type of the media.
     *
     * @param string|null $mimeType
     */
    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Get the type of the media.
     *
     * @return string|null
     */
    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type of the media.
     *
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the URL of the media.
     *
     * @return string|null
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Set the URL of the media.
     *
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray()['media'];
    }
}
