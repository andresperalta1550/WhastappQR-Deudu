<?php

namespace App\Services;

use App\ValueObjects\Filter;
use App\ValueObjects\FilterCollection;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class QueryFilterService
{
    /**
     * Apply filters to an Eloquent query builder.
     *
     * @param Builder $query The Eloquent query builder
     * @param FilterCollection $filters The collection of filters to apply
     * @return Builder The modified query builder
     */
    public function apply(Builder $query, FilterCollection $filters): Builder
    {
        if ($filters->isEmpty()) {
            return $query;
        }

        foreach ($filters->all() as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     *
     * @param Builder $query
     * @param Filter $filter
     * @return void
     */
    protected function applyFilter(Builder $query, Filter $filter): void
    {
        $field = $filter->getField();
        $operator = $filter->getOperator();
        $value = $filter->getValue();

        match ($operator) {
            Filter::OPERATOR_EQUAL => $query->where($field, '=', $value),
            Filter::OPERATOR_NOT_EQUAL => $query->where($field, '!=', $value),
            Filter::OPERATOR_LIKE => $query->where($field, 'LIKE', $value),
            Filter::OPERATOR_NOT_LIKE => $query->where($field, 'NOT LIKE', $value),
            Filter::OPERATOR_IN => $this->applyInFilter($query, $field, $value),
            Filter::OPERATOR_NOT_IN => $this->applyNotInFilter($query, $field, $value),
            Filter::OPERATOR_GREATER_THAN => $query->where($field, '>', $value),
            Filter::OPERATOR_GREATER_THAN_OR_EQUAL => $query->where($field, '>=', $value),
            Filter::OPERATOR_LESS_THAN => $query->where($field, '<', $value),
            Filter::OPERATOR_LESS_THAN_OR_EQUAL => $query->where($field, '<=', $value),
            Filter::OPERATOR_IS_NULL => $query->whereNull($field),
            Filter::OPERATOR_IS_NOT_NULL => $query->whereNotNull($field),
            Filter::OPERATOR_BETWEEN => $this->applyBetweenFilter($query, $field, $value),
            default => throw new InvalidArgumentException("Unsupported operator: {$operator}")
        };
    }

    /**
     * Apply IN filter.
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    protected function applyInFilter(Builder $query, string $field, mixed $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "IN operator requires an array value for field: {$field}"
            );
        }

        $query->whereIn($field, $value);
    }

    /**
     * Apply NOT IN filter.
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    protected function applyNotInFilter(Builder $query, string $field, mixed $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "NOT_IN operator requires an array value for field: {$field}"
            );
        }

        $query->whereNotIn($field, $value);
    }

    /**
     * Apply BETWEEN filter.
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    protected function applyBetweenFilter(Builder $query, string $field, mixed $value): void
    {
        if (!is_array($value) || count($value) !== 2) {
            throw new InvalidArgumentException(
                "BETWEEN operator requires an array with exactly 2 values for field: {$field}"
            );
        }

        $query->whereBetween($field, $value);
    }
}
