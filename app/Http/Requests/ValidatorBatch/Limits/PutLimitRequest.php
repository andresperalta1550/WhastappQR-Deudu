<?php

namespace App\Http\Requests\ValidatorBatch\Limits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class PutLimitRequest extends FormRequest
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
            'type' => 'required|string|in:by_administration,by_number',
            'limit' => 'required|integer|min:1',
            'period' => 'required|string|in:monthly,daily',
            'is_active' => 'required|boolean'
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
            'type.required' => 'El parámetro type es obligatorio',
            'type.string' => 'El parámetro type debe ser una cadena de texto',
            'type.in' => 'El parámetro type debe ser "by_administration" o "by_number"',
            'limit.required' => 'El parámetro limit es obligatorio',
            'limit.integer' => 'El parámetro limit debe ser un número entero',
            'limit.min' => 'El parámetro limit debe ser al menos 1',
            'period.required' => 'El parámetro period es obligatorio',
            'period.string' => 'El parámetro period debe ser una cadena de texto',
            'period.in' => 'El parámetro period debe ser "monthly" o "daily"',
            'is_active.required' => 'El parámetro is_active es obligatorio',
            'is_active.boolean' => 'El parámetro is_active debe ser un booleano'
        ];
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
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], Response::HTTP_BAD_REQUEST));
    }
}
