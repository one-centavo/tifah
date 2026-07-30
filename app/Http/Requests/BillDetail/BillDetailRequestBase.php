<?php

declare(strict_types=1);

namespace App\Http\Requests\BillDetail;

use Illuminate\Foundation\Http\FormRequest;

abstract class BillDetailRequestBase extends FormRequest
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
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
            'lot_id' => ['required', 'integer', 'exists:lots,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }
}
