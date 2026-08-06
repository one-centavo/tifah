<?php

declare(strict_types=1);

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

abstract class SupplierRequestBase extends FormRequest
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
            'dv' => ['required', 'integer', 'min:0', 'max:9'],
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:200'],
        ];
    }
}
