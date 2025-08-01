<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalendarTypeRequest extends FormRequest
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
            'type' => ['required', 'in:vacation,holiday,sick_leave'],
        ];
    }

    /**
     * Customize the error messages if needed.
     * @return array
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The selected type is invalid. Allowed values are: holiday, vacation, sick_leave.',
            'type.required' => 'The type field is required.',
        ];
    }

    // Inject route parameter into validation data
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route('type'),
        ]);
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
