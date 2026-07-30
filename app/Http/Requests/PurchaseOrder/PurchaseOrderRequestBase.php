<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

abstract class PurchaseOrderRequestBase extends FormRequest
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
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'status' => ['required', 'string', 'in:pending,received,cancelled'],
            'expected_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'total_estimated' => ['required', 'numeric', 'min:0'],
        ];
    }
}
