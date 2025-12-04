<?php

namespace App\Casts;

use App\ValueObjects\Reaction;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ReactionsCast implements CastsAttributes
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
            fn($item) => Reaction::fromArray($item),
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
        if (!is_array($value)) {
            return [];
        }
        return [
            'reactions' => array_map(
                fn($item) => $item->toArray(),
                $value
            )
        ];
    }
}
