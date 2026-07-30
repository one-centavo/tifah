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
}
