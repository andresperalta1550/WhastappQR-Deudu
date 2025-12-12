<?php

namespace App\Http\Requests\Contact;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class GetSummaryConversationsRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'coordination_id' => 'required|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coordination_id.required' => 'El parámetro coordination_id es requerido',
            'coordination_id.integer' => 'El parámetro coordination_id debe ser un número entero',
            'coordination_id.min' => 'El parámetro coordination_id debe ser al menos 1',
            'per_page.integer' => 'El parámetro per_page debe ser un número entero',
            'per_page.min' => 'El parámetro per_page debe ser al menos 1',
            'per_page.max' => 'El parámetro per_page no puede ser mayor a 100',
            'page.integer' => 'El parámetro page debe ser un número entero',
            'page.min' => 'El parámetro page debe ser al menos 1',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'coordination_id' => 'ID de coordinación',
            'per_page' => 'elementos por página',
            'page' => 'número de página',
        ];
    }

    /**
     * Get the coordination ID from the request.
     *
     * @return int
     */
    public function getCoordinationId(): int
    {
        return (int) $this->query('coordination_id');
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
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST)
        );
    }
}
