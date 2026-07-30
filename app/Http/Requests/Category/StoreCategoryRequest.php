<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

class StoreCategoryRequest extends CategoryRequestBase
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
                'name' => ['required', 'string', 'min:3', 'max:50', 'unique:categories,name'],
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
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre de la categoría debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre de la categoría no debe exceder los 50 caracteres.',
            'name.unique' => 'El nombre de la categoría ya se encuentra registrado.',
            'description.max' => 'La descripción no debe exceder los 255 caracteres.',
        ];
    }
}
