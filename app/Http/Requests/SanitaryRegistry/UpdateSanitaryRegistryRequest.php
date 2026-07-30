<?php

declare(strict_types=1);

namespace App\Http\Requests\SanitaryRegistry;

class UpdateSanitaryRegistryRequest extends SanitaryRegistryRequestBase
{
    public function rules(int $id): array
    {
        return array_merge(
            $this->commonRules(),
            [
                'registration_number' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:sanitary_registries,registration_number,'.$id,
                    'regex:/^INVIMA\s+\d{4}[A-Z]-\d{7}$/i',
                ],
                'expiration_date' => [
                    'required',
                    'date',
                ],
            ]
        );
    }

    public function messages(): array
    {
        return [
            'registration_number.required' => 'El número de registro sanitario es obligatorio.',
            'registration_number.unique' => 'Este número de registro sanitario ya se encuentra registrado.',
            'registration_number.max' => 'El número de registro sanitario no debe exceder los 50 caracteres.',
            'registration_number.regex' => 'El formato del número de registro sanitario es inválido. Debe ser como INVIMA 2026M-1234567.',
            'laboratory_id.required' => 'El laboratorio fabricante es obligatorio.',
            'laboratory_id.exists' => 'El laboratorio seleccionado no es válido.',
            'expiration_date.required' => 'La fecha de vencimiento es obligatoria.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'description.max' => 'La descripción no debe exceder los 65535 caracteres.',
        ];
    }
}
