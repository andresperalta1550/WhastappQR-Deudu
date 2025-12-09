<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate that a value is unique in a database table.
 *
 * @example
 *  $request->validate([
 *      'email' => ['required', 'email', new Unique(User::class, 'email')],
 *  ]);
 *
 * @author Jean-Dv-Net
 */
class Unique implements ValidationRule
{
    /**
     * @var string The model class to validate against.
     */
    protected string $modelClass;

    /**
     * @var string The column to validate against.
     */
    protected string $column;

    /**
     * @var array The conditions to validate against.
     */
    protected array $conditions;

    /**
     * @var ?string The ID to ignore.
     */
    protected ?string $ignoreId;

    public function __construct(
        string $modelClass,
        string $column,
        array $conditions = [],
        ?string $ignoreId = null
    ) {
        $this->modelClass = $modelClass;
        $this->column = $column;
        $this->conditions = $conditions;
        $this->ignoreId = $ignoreId;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** var Model $model */
        $model = new $this->modelClass();

        $query = $model->newQuery()
            ->where($this->column, $value);

        // Add another conditions
        foreach ($this->conditions as $column => $value) {
            $query->where($column, $value);
        }

        // Exclude the current record
        if ($this->ignoreId) {
            $query->where('_id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fields = implode(', ', array_keys($this->conditions));
            $message = "El valor ya existe";

            if (!empty($fields)) {
                $message .= " para la(s) condicion(es) {$fields}";
            }

            $fail($message . ".");
        }
    }
}
