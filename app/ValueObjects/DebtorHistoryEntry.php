<?php

namespace App\ValueObjects;

/**
 * This class represents a ValueObject specific for debtor history entries.
 */
class DebtorHistoryEntry implements \JsonSerializable
{
    /**
     * @var int|null $debtorId The ID of the debtor.
     */
    private ?int $debtorId;

    /**
     * @var string|null $from The previous debtor link source.
     */
    private ?string $from;

    /**
     * @var string|null $to The new debtor link source.
     */
    private ?string $to;

    /**
     * @var string|null $reason The reason for the change.
     */
    private ?string $reason;

    public function __construct(
        ?int $debtorId = null,
        ?string $from = null,
        ?string $to = null,
        ?string $reason = null,
    ) {
        $this->debtorId = $debtorId;
        $this->from = $from;
        $this->to = $to;
        $this->reason = $reason;
    }

    /**
     * Create a DebtorHistoryEntry instance from an associative array.
     *
     * @param array|null $data
     * @return self|null
     */
    public static function fromArray(?array $data): ?self
    {
        if (!$data) return null;

        return new self(
            $data['debtor_id'] ?? null,
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['reason'] ?? null,
        );
    }

    /**
     * Convert the DebtorHistoryEntry instance to an associative array.
     *
     * @return array The associative array representation of the DebtorHistoryEntry instance.
     */
    public function toArray(): array
    {
        return [
            'debtor_id' => $this->debtorId,
            'from' => $this->from,
            'to' => $this->to,
            'reason' => $this->reason,
        ];
    }

    /**
     * @return int|null
     */
    public function getDebtorId(): ?int
    {
        return $this->debtorId;
    }

    /**
     * @param int|null $debtorId
     */
    public function setDebtorId(?int $debtorId): void
    {
        $this->debtorId = $debtorId;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param string|null $to
     */
    public function setTo(?string $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string|null $reason
     */
    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }


    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
