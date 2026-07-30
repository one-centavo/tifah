<?php

declare(strict_types=1);

namespace App\Http\Requests\SanitaryRegistry;

class StoreSanitaryRegistryRequest extends SanitaryRegistryRequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->commonRules(),
            [
                'registration_number' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^INVIMA\s+\d{4}[A-Z]-\d{7}$/i',
                    'unique:sanitary_registries,registration_number',
                ],
                'expiration_date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
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
            'registration_number.required' => 'El número de registro sanitario es obligatorio.',
            'registration_number.regex' => 'El formato del registro sanitario es inválido. Debe seguir el formato oficial (ej. INVIMA 2026M-1234567).',
            'registration_number.unique' => 'Este número de registro sanitario ya se encuentra registrado.',
            'laboratory_id.required' => 'El laboratorio fabricante es obligatorio.',
            'laboratory_id.exists' => 'El laboratorio fabricante seleccionado es inválido.',
            'expiration_date.required' => 'La fecha de vencimiento es obligatoria.',
            'expiration_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a la fecha actual.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado es inválido.',
        ];
    }
}
