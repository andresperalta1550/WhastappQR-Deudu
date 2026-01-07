<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * This class represents a collection of Filter ValueObjects.
 */
class FilterCollection implements \JsonSerializable, \Countable, \IteratorAggregate
{
    /**
     * @var Filter[] $filters Array of Filter objects.
     */
    private array $filters = [];

    /**
     * @param Filter[] $filters
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter) {
            $this->add($filter);
        }
    }

    /**
     * Add a filter to the collection.
     *
     * @param Filter $filter
     * @return self
     */
    public function add(Filter $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Get all filters.
     *
     * @return Filter[]
     */
    public function all(): array
    {
        return $this->filters;
    }

    /**
     * Check if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->filters);
    }

    /**
     * Count the number of filters.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->filters);
    }

    /**
     * Get an iterator for the filters.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->filters);
    }

    /**
     * Create a FilterCollection instance from an array of filter data.
     *
     * @param array $data
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $filters = [];

        foreach ($data as $filterData) {
            if (!is_array($filterData)) {
                throw new InvalidArgumentException(
                    'Each filter must be an array with field, operator, and optionally value.'
                );
            }

            $filters[] = Filter::fromArray($filterData);
        }

        return new self($filters);
    }

    /**
     * Convert the FilterCollection to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function (Filter $filter) {
            return $filter->toArray();
        }, $this->filters);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
