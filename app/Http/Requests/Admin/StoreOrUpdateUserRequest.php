<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrUpdateUserRequest extends FormRequest
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
        $editing = $this->route('user') != null;
        $userId = $editing ? $this->input('id') : null;

        $type = $this->input('type', UserTypeEnum::MANAGEMENT->value);
        $isUser = $type === UserTypeEnum::MOBILE->value;

        $passwordRules = $editing
            ? ['nullable','string','min:8','confirmed','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.#_\-])[A-Za-z\d@$!%*?&.#_\-]{8,}$/']
            : ['required','string','min:8','confirmed','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.#_\-])[A-Za-z\d@$!%*?&.#_\-]{8,}$/'];

        if ($isUser) {
            // Mobile users: simpler password
            $passwordRules = $editing
                ? ['nullable', 'string', 'min:6', 'confirmed', 'regex:/^[a-zA-Z0-9]+$/']
                : ['required', 'string', 'min:6', 'confirmed', 'regex:/^[a-zA-Z0-9]+$/'];
        }

        return [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'dni' => [
                'required',
                'string',
                'regex:/^([0-9]{8}[A-Za-z]|[XYZ][0-9]{7}[A-Za-z])$/',
                Rule::unique('users', 'dni')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                'regex:/^.+@.+\..+$/',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => $passwordRules,
            'photo' => 'nullable|image|max:2048',
            'photo_base64' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',

            //Conditional fields:
            'company_id' => $isUser ? 'required|exists:companies,id' : 'nullable',
            'work_calendar_template_id' => $isUser ? 'required|exists:work_calendar_templates,id' : 'nullable',
            'work_schedule' => $isUser ? 'required|string|max:255' : 'nullable',
            'contract_type' => $isUser ? 'required|in:temporary,permanent,fixed_discontinuous' : 'nullable',
            'contract_start_date' => $isUser ? 'required|date' : 'nullable',
            'notification_type' => $isUser ? 'required|in:none,visual,visual_audio' : 'nullable'
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('dni')) {
            $dni = strtoupper(trim($this->input('dni')));
            $this->merge(['dni' => $dni]);
        }
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        $type = $this->input('type', UserTypeEnum::MOBILE->value);
        $isUser = $type === UserTypeEnum::MOBILE->value;

        // Messages for management users
        $passwordMessages = [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales.',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales.'
        ];
        if ($isUser) {
            //Messages for mobile users
            $passwordMessages = [
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'password.regex' => 'La contraseña solo puede contener letras y números, sin caracteres especiales.',
            ];
        }
        return array_merge([
            'name.required' => 'El nombre es obligatorio.',
            'surname.required' => 'El apellido es obligatorio.',
            'dni.required' => 'El DNI/NIE es obligatorio.',
            'dni.unique' => 'El DNI/NIE ya está en uso.',
            'dni.regex' => 'El DNI/NIE debe tener un formato válido (DNI: 12345678A, NIE: X1234567B).',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'El email ya está registrado.',
            'email.regex' => 'El email debe contener un "@" y un ".".',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password_confirmation.same' => 'La confirmación debe coincidir con la contraseña.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La imagen no puede pesar más de 2MB.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'El teléfono debe contener 9 dígitos.',
            'contract_start_date.date' => 'La fecha de inicio de contrato no es válida.',
            'notification_type.in' => 'El tipo de notificación seleccionado no es válido.',
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no es válida.',
            'work_calendar_template_id.required' => 'El calendario laboral es obligatorio.',
            'work_calendar_template_id.exists' => 'El calendario laboral seleccionado no es válido.',
            'work_schedule.required' => 'El horario de trabajo es obligatorio.',
            'contract_type.required' => 'El tipo de contrato es obligatorio.',
            'contract_type.in' => 'El tipo de contrato seleccionado no es válido.',
            'notification_type.required' => 'El tipo de notificación es obligatorio.',
            'contract_start_date.required' => 'La fecha de inicio del contrato es obligatoria.'
        ], $passwordMessages);
    }
}
