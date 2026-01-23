<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This class is only for read values from the database.
 * It is not used for any other purpose.
 */
class Debtor extends Model
{
    /**
     * @var string $connection The connection name for the model
     */
    protected $connection = 'mysql-aquila';

    /**
     * @var string $table The table associated with the model
     */
    protected $table = 'debtors';

    /**
     * @var array $visible The attributes that should be visible in arrays.
     */
    protected $visible = [
        'id',
        'name',
        'lastname',
        'coordinator_id'
    ];

    /**
     * Get the debtor ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the debtor name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the debtor last name.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastname;
    }

    /**
     * Get the debtor full name.
     *
     * @return string
     */
    public function getFullname(): string
    {
        return $this->name . ' ' . $this->lastname;
    }

    public function getIdentification(): string
    {
        return $this->identification;
    }

    /**
     * Get the debtor coordinator ID.
     *
     * @return int
     */
    public function getCoordinatorId(): int
    {
        return $this->coordinator_id;
    }

    public function getAnalyst(): int
    {
        return $this->analyst_id;
    }
}
