<?php

namespace App\Casts;

use App\ValueObjects\DebtorHistoryEntry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class DebtorHistoryCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (!$value || !is_array($value)) {
            return [];
        }

        return array_map(
            fn($item) => DebtorHistoryEntry::fromArray($item),
            $value
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_array($value)) {
            return array_map(
                fn($item) => $item instanceof DebtorHistoryEntry ? $item->toArray() : $item,
                $value
            );
        }

        return $value;
    }
}
