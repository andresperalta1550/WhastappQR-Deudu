<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * This class represents a ValueObject for a single filter criteria.
 */
class Filter implements \JsonSerializable
{
    /**
     * Supported filter operators
     */
    public const OPERATOR_EQUAL = 'EQUAL';
    public const OPERATOR_NOT_EQUAL = 'NOT_EQUAL';
    public const OPERATOR_LIKE = 'LIKE';
    public const OPERATOR_NOT_LIKE = 'NOT_LIKE';
    public const OPERATOR_IN = 'IN';
    public const OPERATOR_NOT_IN = 'NOT_IN';
    public const OPERATOR_GREATER_THAN = 'GREATER_THAN';
    public const OPERATOR_GREATER_THAN_OR_EQUAL = 'GREATER_THAN_OR_EQUAL';
    public const OPERATOR_LESS_THAN = 'LESS_THAN';
    public const OPERATOR_LESS_THAN_OR_EQUAL = 'LESS_THAN_OR_EQUAL';
    public const OPERATOR_IS_NULL = 'IS_NULL';
    public const OPERATOR_IS_NOT_NULL = 'IS_NOT_NULL';
    public const OPERATOR_BETWEEN = 'BETWEEN';

    /**
     * @var string $field The field to filter on.
     */
    private string $field;

    /**
     * @var string $operator The operator to apply.
     */
    private string $operator;

    /**
     * @var mixed $value The value to filter by.
     */
    private mixed $value;

    /**
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function __construct(string $field, string $operator, mixed $value = null)
    {
        $this->validateOperator($operator);

        $this->field = $field;
        $this->operator = strtoupper($operator);
        $this->value = $value;
    }

    /**
     * Get all supported operators.
     *
     * @return array
     */
    public static function getSupportedOperators(): array
    {
        return [
            self::OPERATOR_EQUAL,
            self::OPERATOR_NOT_EQUAL,
            self::OPERATOR_LIKE,
            self::OPERATOR_NOT_LIKE,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
            self::OPERATOR_GREATER_THAN,
            self::OPERATOR_GREATER_THAN_OR_EQUAL,
            self::OPERATOR_LESS_THAN,
            self::OPERATOR_LESS_THAN_OR_EQUAL,
            self::OPERATOR_IS_NULL,
            self::OPERATOR_IS_NOT_NULL,
            self::OPERATOR_BETWEEN,
        ];
    }

    /**
     * Validate that the operator is supported.
     *
     * @param string $operator
     * @throws InvalidArgumentException
     */
    private function validateOperator(string $operator): void
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, self::getSupportedOperators(), true)) {
            throw new InvalidArgumentException(
                "Unsupported operator: {$operator}. Supported operators are: " .
                implode(', ', self::getSupportedOperators())
            );
        }
    }

    /**
     * Create a Filter instance from an associative array.
     *
     * @param array $data
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['field']) || !isset($data['operator'])) {
            throw new InvalidArgumentException(
                'Filter must have both "field" and "operator" properties.'
            );
        }

        return new self(
            $data['field'],
            $data['operator'],
            $data['value'] ?? null
        );
    }

    /**
     * Convert the Filter instance to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Check if the operator requires a value.
     *
     * @return bool
     */
    public function requiresValue(): bool
    {
        return !in_array($this->operator, [
            self::OPERATOR_IS_NULL,
            self::OPERATOR_IS_NOT_NULL,
        ], true);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
