<?php

declare(strict_types=1);

namespace App\Http\Requests\InventoryMovement;

use Illuminate\Foundation\Http\FormRequest;

abstract class InventoryMovementRequestBase extends FormRequest
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
            'lot_id' => ['required', 'integer', 'exists:lots,id'],
            'type' => ['required', 'string', 'in:entry,exit,adjustment'],
            'quantity' => ['required', 'integer'],
            'previous_balance' => ['required', 'integer', 'min:0'],
            'new_balance' => ['required', 'integer', 'min:0'],
            'concept' => ['required', 'string', 'max:255'],
            'reference_id' => ['required', 'integer'],
        ];
    }
}
