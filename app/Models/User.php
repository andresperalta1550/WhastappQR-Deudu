<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This class is only for read values from the database.
 * It is not used for any other purpose.
 */
class User extends Model
{
    /**
     * @var string $connection The connection name for the model
     */
    protected $connection = 'mysql-aquila';

    /**
     * @var string $table The table associated with the model
     */
    protected $table = 'users';

    /**
     * @var array $visible The attributes that should be visible in arrays.
     */
    protected $visible = [
        'id',
        'name',
        'lastname',
        'coordination_id'
    ];

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the user name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the user last name.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastname;
    }

    /**
     * Get the user full name.
     *
     * @return string
     */
    public function getFullname(): string
    {
        return $this->name . ' ' . $this->lastname;
    }

    public function getCoordinationId(): int
    {
        return $this->coordination_id;
    }

    public function isAnalist(): bool
    {
        if ($this->role_id == 2 && $this->department_id == 1 && $this->level_id == 5) {
            return true;
        }

        return false;
    }

    public function isCoordination(): bool
    {
        if ($this->role_id == 14 && $this->department_id == 1 && $this->level_id == 2) {
            return true;
        }

        return false;
    }
}
