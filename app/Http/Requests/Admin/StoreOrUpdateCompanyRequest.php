<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateCompanyRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:500',
            'phones' => ['array'],
            'phones.*.name' => ['required', 'string', 'max:255'],
            'phones.*.phone_number' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'name.required' => 'El nombre de la empresa es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'address.string' => 'La dirección debe ser un texto válido.',
            'address.max' => 'La dirección no puede tener más de 500 caracteres.',
            'description.string' => 'La descripción debe ser un texto válido.',
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',
        ];

        //Dynamic messages for phones
        if ($this->phones) {
            foreach ($this->phones as $i => $phone) {
                $index = $i + 1;
                $messages["phones.$i.name.required"] = "El nombre del contacto #$index es obligatorio.";
                $messages["phones.$i.name.string"] = "El nombre del contacto #$index debe ser un texto válido.";
                $messages["phones.$i.name.max"] = "El nombre del contacto #$index no puede tener más de 255 caracteres.";

                $messages["phones.$i.phone_number.required"] = "El número del contacto #$index es obligatorio.";
                $messages["phones.$i.phone_number.string"] = "El número del contacto #$index debe ser un texto válido.";
                $messages["phones.$i.phone_number.regex"] = "El número del contacto #$index debe tener 9 dígitos.";
            }
        }

        return $messages;
    }
}
