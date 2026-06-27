<?php

namespace App\Http\Requests\Admin\Suppliers;

final class SupplierRequestMessages
{
    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede tener más de 100 caracteres.',
            'primary_contact.required' => 'El contacto principal es obligatorio.',
            'primary_contact.min' => 'El contacto principal debe tener al menos 2 caracteres.',
            'primary_contact.regex' => 'El contacto solo puede contener letras y espacios.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'email.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min' => 'El correo electrónico debe tener al menos 10 caracteres.',
            'address.required' => 'La dirección es obligatoria.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
            'delivery_time.required' => 'El tiempo de entrega es obligatorio.',
            'delivery_time.min' => 'El tiempo de entrega debe ser al menos 1 día.',
            'delivery_time.max' => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'rating.max' => 'La evaluación no puede ser mayor a 5.',
        ];
    }
}
