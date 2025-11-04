<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Task;
use Illuminate\Http\Request;

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
    public function rules(Request $request): array
    {
        $isCreate = $this->isMethod('post');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $isRecurrent = $this->boolean('is_recurrent');
        $isEditSeries = $this->boolean('edit_series');

        // Cases:
        $isNewRecurrent = $isCreate && $isRecurrent; // create new recurring series
        $isRecurrentSeriesEdit = $isUpdate && $isRecurrent && $isEditSeries; // edit the entire series
        $isSingleRecurrentEdit = $isUpdate && $isRecurrent && !$isEditSeries;

        $rules = [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'scheduled_date' => $isRecurrent ? 'nullable|date' : 'required|date|after_or_equal:today',
            'scheduled_time' => 'required',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'color' => 'nullable|string',

            // Recurrent
            'is_recurrent' => 'nullable|boolean',

            // Notifications
            'notifications_enabled' => 'boolean',
            'reminder_minutes' => 'required_if:notifications_enabled,1|integer|min:1',

            // Subtasks
            'subtasks.*.description' => 'nullable|string',
            'subtasks.*.note' => 'nullable|string',
        ];

        if ($isCreate || $isNewRecurrent || $isRecurrentSeriesEdit) {
            $rules['pictogram'] = 'nullable|image|max:2048';

            $rules['subtasks'] = 'required|array|min:1';
            $rules['subtasks.*.title'] = 'required|string|max:255';
            $rules['subtask_pictograms'] = 'nullable|array';
            $rules['subtask_pictograms.*'] = 'nullable|image|max:2048';
        } elseif ($isSingleRecurrentEdit) {
            $rules['pictogram'] = 'nullable|string';

            // For single-instance edit, subtasks are not required and we won't merge from model
            $rules['subtasks'] = 'nullable|array';
            $rules['subtask_pictograms'] = 'nullable|array';
            $rules['subtask_pictograms.*'] = 'nullable|string';
        }

        // --- Dynamic recurring rules ---
        if ($isNewRecurrent || $isRecurrentSeriesEdit) {
            // Create new series or edit the entire series -> required and start_date >= today
            $rules['days_of_week'] = ['required', 'array', 'min:1'];
            $rules['days_of_week.*'] = ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];

            $rules['recurrent_start_date'] = ['required', 'date', 'after_or_equal:today'];
            $maxEndDate = now()->addMonths(2)->toDateString();

            $rules['recurrent_end_date'] = [
                'required',
                'date',
                'after_or_equal:recurrent_start_date',
                'before_or_equal:' . $maxEndDate,
            ];
        } else {
            //Normal task or single-instance editing -> nullable, no "as of today" restriction
            $rules['days_of_week'] = ['nullable', 'array'];
            $rules['days_of_week.*'] = ['nullable', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];

            $rules['recurrent_start_date'] = ['nullable', 'date'];
            $rules['recurrent_end_date'] = ['nullable', 'date', 'after_or_equal:recurrent_start_date'];
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
            'scheduled_date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'scheduled_time.required' => 'La hora es obligatoria.',

            // Notifications
            'reminder_minutes.required_if' => 'Debes indicar el recordatorio si activas las notificaciones.',

            // Recurrent
            'days_of_week.required' => 'Debes seleccionar al menos un día si la tarea es recurrente.',
            'recurrent_start_date.required' => 'La fecha de inicio es obligatoria para tareas recurrentes.',
            'recurrent_end_date.required' => 'La fecha de fin es obligatoria para tareas recurrentes.',
            'recurrent_start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'recurrent_end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'recurrent_end_date.before_or_equal' => 'La fecha de fin no puede ser posterior a dos meses desde hoy.',

            // Subtasks
            'subtasks.*.title.required' => 'El título de la subtarea es obligatorio.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Fills disabled fields with current model values to avoid validation errors.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Only apply this for single-instance edits of an existing task
        if ($this->route('task') && $this->isMethod('put') && !$this->boolean('edit_series')) {
            /** @var Task $task */
            $task = $this->route('task');

            // Fields that are disabled in the form and need to be filled from the model
            $fieldsToKeep = [
                'title',
                'description',
                'scheduled_date',
                'estimated_duration_minutes',
                'notifications_enabled',
                'reminder_minutes',
                'pictogram_path',
                'color',
                'status',
                'is_recurrent',
                'days_of_week',
                'recurrent_start_date',
                'recurrent_end_date',
            ];

            $data = [];

            foreach ($fieldsToKeep as $field) {
                // Only merge from model if the request did not send the field
                if (!$this->has($field)) {
                    $data[$field] = $task->$field ?? null;
                }
            }
            // Merge prepared data into the request
            $this->merge($data);
        }
    }
}
