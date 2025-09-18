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
        $isEdit = $this->route('task') || $this->route('taskId');

        $rules = [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'pictogram_path' => 'nullable|image|max:2048',
            'color' => 'nullable|string',

            // Recurrente
            'is_recurrent' => 'nullable|boolean',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            // Notifications
            'notifications_enabled' => 'boolean',
            'reminder_minutes' => 'required_if:notifications_enabled,1|integer|min:1',

            // Subtareas
            'subtasks' => 'required|array|min:1',
            'subtasks.*.title' => 'required|string|max:255',
            'subtasks.*.description' => 'nullable|string',
            'subtasks.*.note' => 'nullable|string',
            'subtask_pictograms' => 'nullable|array',
            'subtask_pictograms.*' => 'nullable|image|max:2048',

            // Pictogram
            'pictogram' => 'nullable|image|max:2048',
        ];

        if ($this->boolean('is_recurrent')) {
            $rules['days_of_week'] = 'required|array|min:1';
            $rules['days_of_week.*'] = 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday';

            if (!$isEdit) {
                $rules['recurrent_start_date'] = 'required|date|after_or_equal:today';
                $rules['recurrent_end_date'] = 'required|date|after_or_equal:recurrent_start_date';
            } else {
                $rules['recurrent_start_date'] = 'required|date';
                $rules['recurrent_end_date'] = 'required|date|after_or_equal:recurrent_start_date';
            }
        }

        return $rules;
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'scheduled_date.required' => 'La fecha es obligatoria.',
            'scheduled_time.required' => 'La hora es obligatoria.',

            // Notificaciones
            'reminder_minutes.required_if' => 'Debes indicar el recordatorio si activas las notificaciones.',

            // Recurrente
            'days_of_week.required' => 'Debes seleccionar al menos un día si la tarea es recurrente.',
            'recurrent_start_date.required' => 'La fecha de inicio es obligatoria para tareas recurrentes.',
            'recurrent_end_date.required' => 'La fecha de fin es obligatoria para tareas recurrentes.',
            'recurrent_start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'recurrent_end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            // Subtareas
            'subtasks.*.title.required' => 'El título de la subtarea es obligatorio.',
        ];
    }
}
