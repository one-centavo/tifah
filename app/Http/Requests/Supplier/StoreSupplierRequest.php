<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Validation\Rule;

class StoreSupplierRequest extends SupplierRequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->commonRules(),
            [
                'nit' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('suppliers', 'nit')->whereNull('deleted_at'),
                    'regex:/^\d{1,3}(\.\d{3})*$/',
                ],
            ]
        );
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.required' => 'El NIT es obligatorio.',
            'nit.unique' => 'Este NIT ya se encuentra registrado.',
            'nit.max' => 'El NIT no debe exceder los 20 caracteres.',
            'nit.regex' => 'El formato del NIT es inválido. Debe ser un número con puntos de verificación (ej: 900.123.456).',
            'dv.required' => 'El dígito de verificación (DV) es obligatorio.',
            'dv.integer' => 'El dígito de verificación debe ser un número entero.',
            'dv.min' => 'El dígito de verificación debe estar entre 0 y 9.',
            'dv.max' => 'El dígito de verificación debe estar entre 0 y 9.',
            'name.required' => 'La razón social es obligatoria.',
            'name.max' => 'La razón social no debe exceder los 150 caracteres.',
            'contact_person.max' => 'El nombre de contacto no debe exceder los 100 caracteres.',
            'phone_number.required' => 'El teléfono es obligatorio.',
            'phone_number.max' => 'El teléfono no debe exceder los 20 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato de email estándar (ejemplo@proveedor.com).',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
            'address.required' => 'La dirección física es obligatoria.',
            'address.max' => 'La dirección física no debe exceder los 200 caracteres.',
        ];
    }
}
