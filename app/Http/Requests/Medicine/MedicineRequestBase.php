<?php

declare(strict_types=1);

namespace App\Http\Requests\Medicine;

use Illuminate\Foundation\Http\FormRequest;

abstract class MedicineRequestBase extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the common validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    protected function commonRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'sanitary_registry_id' => ['required', 'integer', 'exists:sanitary_registries,id'],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'concentration_value' => ['required', 'numeric', 'min:0'],
            'concentration_unit_id' => ['required', 'integer', 'exists:concentration_units,id'],
            'container_id' => ['required', 'integer', 'exists:containers,id'],
            'content_quantity' => ['required', 'integer', 'min:0'],
            'content_unit_id' => ['required', 'integer', 'exists:content_units,id'],
            'is_cold_chain' => ['boolean'],
            'is_special_control' => ['boolean'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
