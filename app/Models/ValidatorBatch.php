<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * This class represents a validator batch.
 */
class ValidatorBatch extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = "pending";
    public const STATUS_IN_PROCESS = "in_process";
    public const STATUS_VALIDATED = "validated";
    public const STATUS_REJECTED = "rejected";
    /**
     * @var string $collection The name of the collection.
     */
    protected $collection = 'validator_batches';

    /**
     * @var string $connection The database connection name.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'consecutive',
        'created_at',
        'created_by',
        'approved_at',
        'approved_by',
        'status',
        'total_numbers',
        'processed_numbers',
        'file_path',
    ];

    /**
     * @var array $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        // Simple fields
        'consecutive' => 'integer',
        'created_at' => 'datetime',
        'created_by' => 'integer',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'status' => 'string',
        'total_numbers' => 'integer',
        'processed_numbers' => 'integer',
        'file_path' => 'string',
    ];

    /**
     * @var string[] $hidden The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'updated_at',
        'deleted_at'
    ];

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getConsecutive(): int
    {
        return $this->consecutive;
    }

    public function setConsecutive(int $value): void
    {
        $this->consecutive = $value;
    }

    public function getCreatedAt(): \Carbon\Carbon
    {
        return $this->created_at;
    }

    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    public function setCreatedBy(int $value): void
    {
        $this->created_by = $value;
    }

    public function getApprovedAt(): ?\Carbon\Carbon
    {
        return $this->approved_at;
    }

    public function setApprovedAt(?\Carbon\Carbon $value): void
    {
        $this->approved_at = $value;
    }

    public function getApprovedBy(): ?int
    {
        return $this->approved_by;
    }

    public function setApprovedBy(?int $value): void
    {
        $this->approved_by = $value;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $value): void
    {
        $this->status = $value;
    }

    public function getTotalNumbers(): int
    {
        return $this->total_numbers;
    }

    public function setTotalNumbers(int $value): void
    {
        $this->total_numbers = $value;
    }

    public function getProcessedNumbers(): int
    {
        return $this->processed_numbers;
    }

    public function setProcessedNumbers(int $value): void
    {
        $this->processed_numbers = $value;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function setFilePath(string $value): void
    {
        $this->file_path = $value;
    }
}
