<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskStatus;
use Illuminate\Validation\Rule;

class UserTaskFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for the request parameters.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(TaskStatus::values())],
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
            'scheduled_date.date' => 'La fecha programada debe ser una fecha válida.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
