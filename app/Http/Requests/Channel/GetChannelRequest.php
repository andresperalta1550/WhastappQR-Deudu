<?php

namespace App\Http\Requests\Channel;

use App\Http\Requests\FilterableRequest;

class GetChannelRequest extends FilterableRequest
{
    /**
     * Fields that are allowed to be filtered for Channel model.
     *
     * @var array
     */
    protected array $filterableFields = [
        'id',
        'name',
        'phone_number',
        'status',
        'coordination_id',
        'enabled',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Add any additional validation rules specific to Channel here
        ]);
    }
}
