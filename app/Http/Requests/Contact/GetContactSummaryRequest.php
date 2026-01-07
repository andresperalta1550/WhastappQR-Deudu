<?php

namespace App\Http\Requests\Contact;

use App\Http\Requests\FilterableRequest;

class GetContactSummaryRequest extends FilterableRequest
{
    /**
     * Fields that are allowed to be filtered for Contact model.
     *
     * @var array
     */
    protected array $filterableFields = [
        'id',
        'debtor_id',
        'channel_phone_number',
        'remote_phone_number',
        'is_resolved',
        'coordination_id',
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
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1'
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'per_page.integer' => 'El parámetro per_page debe ser un número entero',
            'per_page.min' => 'El parámetro per_page debe ser al menos 1',
            'per_page.max' => 'El parámetro per_page no puede ser mayor a 100',
            'page.integer' => 'El parámetro page debe ser un número entero',
            'page.min' => 'El parámetro page debe ser al menos 1'
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'per_page' => 'elementos por página',
            'page' => 'número de página'
        ]);
    }

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return (int) $this->query('per_page', 15);
    }

    /**
     * Get the page number.
     *
     * @return int
     */
    public function getPage(): int
    {
        return max(1, (int) $this->query('page', 1));
    }
}
