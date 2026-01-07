<?php

namespace App\Services;

use App\ValueObjects\Filter;
use App\ValueObjects\FilterCollection;
use InvalidArgumentException;

/**
 * Service for converting FilterCollection to MongoDB query conditions.
 */
class MongoFilterService
{
    /**
     * Build MongoDB match conditions from a FilterCollection.
     *
     * @param FilterCollection $filters
     * @return array MongoDB match conditions
     */
    public function buildMatchConditions(FilterCollection $filters): array
    {
        if ($filters->isEmpty()) {
            return [];
        }

        $conditions = [];

        foreach ($filters->all() as $filter) {
            $fieldCondition = $this->buildFilterCondition($filter);

            if (!empty($fieldCondition)) {
                // Merge conditions for the same field
                $field = $filter->getField();
                if (isset($conditions[$field])) {
                    $conditions[$field] = array_merge($conditions[$field], $fieldCondition[$field]);
                } else {
                    $conditions = array_merge($conditions, $fieldCondition);
                }
            }
        }

        return $conditions;
    }

    /**
     * Build MongoDB condition for a single filter.
     *
     * @param Filter $filter
     * @return array
     */
    protected function buildFilterCondition(Filter $filter): array
    {
        $field = $filter->getField();
        $operator = $filter->getOperator();
        $value = $filter->getValue();

        return match ($operator) {
            Filter::OPERATOR_EQUAL => [$field => $value],
            Filter::OPERATOR_NOT_EQUAL => [$field => ['$ne' => $value]],
            Filter::OPERATOR_LIKE => [$field => ['$regex' => $value, '$options' => 'i']],
            Filter::OPERATOR_NOT_LIKE => [$field => ['$not' => ['$regex' => $value, '$options' => 'i']]],
            Filter::OPERATOR_IN => [$field => ['$in' => $this->ensureArray($value, $field)]],
            Filter::OPERATOR_NOT_IN => [$field => ['$nin' => $this->ensureArray($value, $field)]],
            Filter::OPERATOR_GREATER_THAN => [$field => ['$gt' => $value]],
            Filter::OPERATOR_GREATER_THAN_OR_EQUAL => [$field => ['$gte' => $value]],
            Filter::OPERATOR_LESS_THAN => [$field => ['$lt' => $value]],
            Filter::OPERATOR_LESS_THAN_OR_EQUAL => [$field => ['$lte' => $value]],
            Filter::OPERATOR_IS_NULL => [$field => null],
            Filter::OPERATOR_IS_NOT_NULL => [$field => ['$ne' => null]],
            Filter::OPERATOR_BETWEEN => $this->buildBetweenCondition($field, $value),
            default => throw new InvalidArgumentException("Unsupported operator: {$operator}")
        };
    }

    /**
     * Ensure value is an array for IN/NOT_IN operators.
     *
     * @param mixed $value
     * @param string $field
     * @return array
     */
    protected function ensureArray(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "IN/NOT_IN operator requires an array value for field: {$field}"
            );
        }

        return $value;
    }

    /**
     * Build BETWEEN condition.
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    protected function buildBetweenCondition(string $field, mixed $value): array
    {
        if (!is_array($value) || count($value) !== 2) {
            throw new InvalidArgumentException(
                "BETWEEN operator requires an array with exactly 2 values for field: {$field}"
            );
        }

        return [
            $field => [
                '$gte' => $value[0],
                '$lte' => $value[1]
            ]
        ];
    }
}
