<?php

declare(strict_types=1);

namespace App\Http\Requests\SanitaryRegistry;

use Illuminate\Foundation\Http\FormRequest;

abstract class SanitaryRegistryRequestBase extends FormRequest
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
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'expiration_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:expired,valid,under_renewal'],
            'description' => ['nullable', 'string'],
        ];
    }
}
