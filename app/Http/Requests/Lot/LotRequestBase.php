<?php

declare(strict_types=1);

namespace App\Http\Requests\Lot;

use Illuminate\Foundation\Http\FormRequest;

abstract class LotRequestBase extends FormRequest
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
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'batch_number' => ['required', 'string', 'max:255'],
            'expiration_date' => ['required', 'date'],
            'current_quantity' => ['required', 'integer', 'min:0'],
            'initial_quantity' => ['required', 'integer', 'min:0'],
            'reception_date' => ['required', 'date'],
            'unit_purchase_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,blocked,damaged'],
        ];
    }
}
