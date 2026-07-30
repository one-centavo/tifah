<?php

declare(strict_types=1);

namespace App\Http\Requests\Laboratory;

class StoreLaboratoryRequest extends LaboratoryRequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'name' => ['required', 'string', 'max:255', 'unique:laboratories,name'],
            ],
            $this->commonRules()
        );
    }

    /**
     * Get the custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del laboratorio es obligatorio.',
            'name.unique' => 'Este laboratorio ya se encuentra registrado.',
            'name.max' => 'El nombre del laboratorio no debe exceder los 255 caracteres.',
            'description.max' => 'La descripción no debe exceder los 255 caracteres.',
        ];
    }
}
