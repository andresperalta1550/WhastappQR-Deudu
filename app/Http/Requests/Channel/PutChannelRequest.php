<?php

namespace App\Http\Requests\Channel;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;
use App\Rules\Unique;
use App\Models\Channel;

class PutChannelRequest extends FormRequest
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
            'coordination_id' => 'required|int',
            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:10',
                new Unique(
                    Channel::class,
                    'priority',
                    ['coordination_id' => $this->coordination_id],
                    $this->route('channel') // Ignore the current record
                )
            ]
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
            'coordination_id.required' => 'El id de la coordinación es requerido',
            'priority.required' => 'La prioridad es requerida',
            'priority.integer' => 'La prioridad debe ser un número entero',
            'priority.min' => 'La prioridad debe ser al menos 1',
            'priority.max' => 'La prioridad no puede ser mayor a 10',
            'priority.unique' => 'La prioridad debe ser única, ya existe un canal con la misma prioridad'
        ];
    }

    /**
     * Returns errors if the data validate fails.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], Response::HTTP_BAD_REQUEST));
    }

    public function getCoordinationId(): int
    {
        return $this->coordination_id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
