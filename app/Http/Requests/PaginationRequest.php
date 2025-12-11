<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sort_by' => 'string',
            'sort_order' => 'in:asc,desc',
            'search' => 'string|max:255',
        ];
    }

    /**
     * Get the page number.
     */
    public function getPage(): int
    {
        return $this->input('page', 1);
    }

    /**
     * Get the number of items per page.
     */
    public function getPerPage(): int
    {
        return $this->input('per_page', 15);
    }

    /**
     * Get the sort field.
     */
    public function getSortBy(): ?string
    {
        return $this->input('sort_by');
    }

    /**
     * Get the sort order.
     */
    public function getSortOrder(): string
    {
        return $this->input('sort_order', 'desc');
    }

    /**
     * Get the search term.
     */
    public function getSearch(): ?string
    {
        return $this->input('search');
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'page' => 'página',
            'per_page' => 'elementos por página',
            'sort_by' => 'ordenar por',
            'sort_order' => 'orden',
            'search' => 'búsqueda',
        ];
    }
}
