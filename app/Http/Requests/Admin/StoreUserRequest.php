<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;

class StoreUserRequest extends FormRequest
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
        $roleId = $this->input('role_id');
        $isUser = Role::find($roleId)?->role_name === 'user';

        return [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'dni' => [
                'required',
                'string',
                'unique:users,dni',
                'regex:/^[0-9]{8}[A-Za-z]$/'
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email',
                'regex:/^.+@.+\..+$/'
            ],
            'phone' => $isUser ? ['required', 'string', 'regex:/^\d{9,}$/'] : 'nullable',
            'username' => 'required|string|unique:users,username',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.#_\-])[A-Za-z\d@$!%*?&.#_\-]{8,}$/'
            ],
            'photo' => 'nullable|image|max:2048',
            'role_id' => 'required|exists:roles,id',

            //Conditional fields:
            'company_id' => $isUser ? 'required|exists:companies,id' : 'nullable',
            'work_schedule' => $isUser ? 'required|string|max:255' : 'nullable',
            'contract_type' => $isUser ? 'required|in:Temporal,Indefinido' : 'nullable',
            'contract_start_date' => $isUser ? 'required|date' : 'nullable',
            'notification_type' => $isUser ? 'required|in:none,visual,visual_audio' : 'nullable',
            'can_receive_notifications' => $isUser ? 'required|boolean' : 'nullable',
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
            'name.required' => 'El nombre es obligatorio.',
            'surname.required' => 'El apellido es obligatorio.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'El DNI ya está en uso.',
            'dni.regex' => 'El DNI debe tener 8 números y 1 letra (ejemplo: 12345678A).',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'El email ya está registrado.',
            'email.regex' => 'El email debe contener un "@" y un ".".',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La imagen no puede pesar más de 2MB.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'El teléfono debe contener al menos 9 números.',
            'contract_start_date.date' => 'La fecha de inicio de contrato no es válida.',
            'notification_type.in' => 'El tipo de notificación seleccionado no es válido.',
        ];
    }
}
