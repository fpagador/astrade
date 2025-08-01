<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'dni' => 'required|string|unique:users,dni,' . $userId,
            'email' => 'nullable|email|unique:users,email,' . $userId,
            'username' => 'required|string|unique:users,username,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'work_schedule' => 'nullable|string|max:255',
            'contract_type' => 'nullable|string|max:255',
            'contract_start_date' => 'nullable|date',
            'notification_type' => 'nullable|in:none,visual,visual_audio',
            'can_receive_notifications' => 'nullable|boolean',
            'phone' => 'nullable|string|max:20',
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
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'El email ya está registrado.',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La imagen no puede pesar más de 2MB.',
            'contract_start_date.date' => 'La fecha de inicio de contrato no es válida.',
            'notification_type.in' => 'El tipo de notificación seleccionado no es válido.',
        ];
    }
}
