<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * Limits Validator Batch.
 * 
 * This model represents the configuration of the
 * limits validator batch. I don't feel is necessary save
 * the types and periods in a different collection. For the moment,
 * we save this in constants
 */
class LimitsValidatorBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Types of the limits.
     * 
     * @var array
     */
    public const TYPES = [
        ['id' => 'by_administration', 'name' => 'Límite por administración'],
        ['id' => 'by_number', 'name' => 'Límite por número']
    ];

    /**
     * Periods of the limits.
     * 
     * @var array
     */
    public const PERIODS = [
        ['id' => 'monthly', 'name' => 'Mensual'],
        ['id' => 'daily', 'name' => 'Diario']
    ];

    /**
     * @var string $collection The MongoDB collection associated with the model.
     */
    protected string $collection = 'limits_validator_batch';

    /**
     * @var string $connection The name of the connection associated with the model.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'type', // In this case we have 'by_administration' and 'by_number'
        'limit', // The limit of the type
        'period', // In this case we have 'monthly' and 'daily',
        'is_active' // Verify if the limit is in operative or not
    ];

    /**
     * @var string[] $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        // Simple fields
        'type' => 'string',
        'limit' => 'integer',
        'period' => 'string',
        'is_active' => 'boolean'
    ];

    /**
     * Get the type of the limit validator batch.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type of the limit validator batch.
     *
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the limit of the limit validator batch.
     *
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Set the limit of the limit validator batch.
     *
     * @param int $limit
     * @return void
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Get the period of the limit validator batch.
     *
     * @return string|null
     */
    public function getPeriod(): ?string
    {
        return $this->period;
    }

    /**
     * Set the period of the limit validator batch.
     *
     * @param string $period
     * @return void
     */
    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    /**
     * Get the limit by administration.
     * 
     * @return LimitsValidatorBatch
     */
    public function byAdministration(): LimitsValidatorBatch
    {
        return $this->where('type', 'by_administration')->first();
    }

    /**
     * Get the limit by number.
     * 
     * @return LimitsValidatorBatch
     */
    public function byNumber(): LimitsValidatorBatch
    {
        return $this->where('type', 'by_number')->first();
    }
}
