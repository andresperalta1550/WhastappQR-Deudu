<?php

namespace App\Models;

use App\Casts\AsObjectId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * This model is used to store the temporary data of the validator batch.
 * It is not used for any other purpose.
 */
class ValidatorBatchTemp extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * @var string $collection The name of the collection.
     */
    protected $collection = 'validator_batch_temp';

    /**
     * @var string $connection The database connection name.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'batch_id',
        'row_number',
        'data',
        'status',
        'errors',
        'validation_result',
        'processed_at'
    ];

    /**
     * @var array $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        'row_number' => 'integer',
        'status' => 'string',
        'batch_id' => AsObjectId::class,
        'processed_at' => 'datetime',
    ];

    /**
     * Get the batch ID.
     *
     * @return string
     */
    public function getBatchId(): string
    {
        return (string) $this->batch_id;
    }

    /**
     * Get the row number.
     *
     * @return int
     */
    public function getRowNumber(): int
    {
        return $this->row_number;
    }

    /**
     * Get the data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the validation result.
     *
     * @return array
     */
    public function getValidationResult(): array
    {
        return $this->validation_result;
    }

    /**
     * Get the status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function generateConsecutive(): int
    {
        $lastBatch = $this->orderBy('consecutive', 'desc')->first();

        return $lastBatch ? $lastBatch->consecutive + 1 : 1;
    }
}
