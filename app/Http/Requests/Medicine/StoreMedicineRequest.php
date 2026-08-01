<?php

declare(strict_types=1);

namespace App\Http\Requests\Medicine;

class StoreMedicineRequest extends MedicineRequestBase
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
                'barcode' => [
                    'required',
                    'string',
                    'regex:/^\d+$/',
                    'between:8,14',
                    'unique:medicine_barcodes,barcode',
                ],
                'name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'content_quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:500',
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
            'barcode.required' => 'El código de barras es obligatorio.',
            'barcode.regex' => 'El código de barras debe estar compuesto únicamente por números.',
            'barcode.between' => 'El código de barras debe tener entre 8 y 14 dígitos.',
            'barcode.unique' => 'Este código de barras ya se encuentra registrado.',

            'name.required' => 'El nombre comercial es obligatorio.',
            'name.max' => 'El nombre comercial no debe exceder los 100 caracteres.',

            'generic_name.max' => 'El nombre genérico no debe exceder los 255 caracteres.',

            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',

            'laboratory_id.required' => 'El laboratorio es obligatorio.',
            'laboratory_id.exists' => 'El laboratorio seleccionado no es válido.',

            'sanitary_registry_id.required' => 'El registro sanitario es obligatorio.',
            'sanitary_registry_id.exists' => 'El registro sanitario seleccionado no es válido.',

            'concentration_value.required' => 'El valor de concentración es obligatorio.',
            'concentration_value.numeric' => 'El valor de concentración debe ser un número.',
            'concentration_value.min' => 'El valor de concentración debe ser mayor o igual a 0.',

            'concentration_unit_id.required' => 'La unidad de concentración es obligatoria.',
            'concentration_unit_id.exists' => 'La unidad de concentración seleccionada no es válida.',

            'container_id.required' => 'El contenedor es obligatorio.',
            'container_id.exists' => 'El contenedor seleccionado no es válido.',

            'content_quantity.required' => 'La cantidad de contenido es obligatoria.',
            'content_quantity.integer' => 'La cantidad de contenido debe ser un número entero.',
            'content_quantity.min' => 'La cantidad de contenido debe ser mayor a cero.',

            'content_unit_id.required' => 'La unidad de contenido es obligatoria.',
            'content_unit_id.exists' => 'La unidad de contenido seleccionada no es válida.',

            'min_stock.required' => 'El stock mínimo es obligatorio.',
            'min_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'min_stock.min' => 'El stock mínimo debe ser mayor o igual a 0.',

            'selling_price.required' => 'El precio de venta es obligatorio.',
            'selling_price.numeric' => 'El precio de venta debe ser un número.',
            'selling_price.min' => 'El precio de venta debe ser mayor o igual a 0.',

            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
        ];
    }
}
