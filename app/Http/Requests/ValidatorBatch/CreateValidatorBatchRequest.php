<?php

namespace App\Http\Requests\ValidatorBatch;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class CreateValidatorBatchRequest extends FormRequest
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
            'file' => 'required|file|mimes:xlsx,xls',
            'created_by' => 'required|exists:App\Models\User,id',
            'phone_number' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'created_by.required' => 'El campo created_by es obligatorio.',
            'created_by.exists' => 'El usuario de creación debe existir en la base de datos.',
            'file.required' => 'El campo file es obligatorio.',
            'file.file' => 'El campo file debe ser un archivo.',
            'file.mimes' => 'El campo file debe ser un archivo con extensión .xlsx o .xls.',
            'phone_number.required' => 'El campo phone_number es obligatorio.',
            'phone_number.string' => 'El campo phone_number debe ser una cadena de caracteres.',
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
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST)
        );
    }

    /**
     * Get the created by user.
     * 
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->validated('created_by');
    }

    /**
     * Get the phone number.
     * 
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->validated('phone_number');
    }
}
