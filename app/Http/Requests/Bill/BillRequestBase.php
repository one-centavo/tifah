<?php

declare(strict_types=1);

namespace App\Http\Requests\Bill;

use Illuminate\Foundation\Http\FormRequest;

abstract class BillRequestBase extends FormRequest
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
            'id_customer' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', 'string', 'in:active,annulled,draft'],
        ];
    }
}
