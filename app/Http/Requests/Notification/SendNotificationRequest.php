<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendNotificationRequest extends FormRequest
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
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'task_id'  => ['required', 'integer', 'exists:tasks,id'],
        ];
    }

    /**
     * Customize the error messages if needed.
     * @return array
     */
    public function messages(): array
    {
        return [
            'title.required'   => 'A notification title is required.',
            'body.required'    => 'The notification body is required.',
            'task_id.exists'   => 'The selected task does not exist.',
        ];
    }

    /**
     * Returns the error in Json
     *
     * @param Validator $validator
     * @return array
     */
    protected function failedValidation(Validator $validator):array
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
