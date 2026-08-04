<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'boolean' => 'El campo :attribute debe ser booleano.',
    'unique' => 'El campo :attribute ya está en uso.',
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],

    'attributes' => [
        'name' => 'nombre',
        'description' => 'descripción',
        'is_cold_chain' => 'cadena de frío',
        'is_special_control' => 'control especial',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'form.email' => 'correo electrónico',
        'form.password' => 'contraseña',
    ],
];
