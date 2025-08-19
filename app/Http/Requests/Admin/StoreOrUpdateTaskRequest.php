<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateTaskRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'estimated_duration_minutes' => 'nullable|integer',
            'pictogram_path' => 'nullable|string',
            'color' => 'nullable|string',

            // Recurrente
            'is_recurrent' => 'nullable|boolean',
            'recurrent_start_date' => 'nullable|date',
            'recurrent_end_date' => 'nullable|date',

            // Días de la semana
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',

            // Subtareas
            'subtasks' => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
            'subtasks.*.description' => 'nullable|string',
            'subtasks.*.note' => 'nullable|string',
            'subtasks.*.order' => 'nullable|integer',

            // Pictograma
            'pictogram' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'title.required' => 'El campo título es obligatorio.',
            'scheduled_date.required' => 'La fecha es obligatoria.',
            'scheduled_time.required' => 'La hora es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
            'subtasks.*.title.required' => 'El título de la subtarea es obligatorio.',
            'subtasks.*.status.required' => 'El estado de la subtarea es obligatorio.'
        ];
    }
}
