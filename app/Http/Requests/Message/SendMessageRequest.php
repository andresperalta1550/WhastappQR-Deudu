<?php

namespace App\Http\Requests\Message;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class SendMessageRequest extends FormRequest
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
        $rules = [
            'message' => 'required|array',
            'message.info' => 'required|array',
            'message.info.to' => 'required|string',
            'message.info.type' => 'required|string|in:text,file',
            'message.info.coordination_id' => 'required|int',
            'message.info.debtor_id' => 'required|int',
            'message.data' => 'required|array'
        ];

        switch ($rules['message.info.type']) {
            case 'text':
                $rules['message.data.text'] = 'required|string';
                break;
            case 'file':
                $rules['message.data.file'] = 'required|string';
        }

        return $rules;
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
}
