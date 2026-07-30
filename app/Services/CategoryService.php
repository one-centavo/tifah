<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    /**
     * Create a new category and assign it to the authenticated user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $data['created_by'] = auth()->id();

        return Category::create($data);
    }

    /**
     * Update an existing category and assign the updater.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $data['updated_by'] = auth()->id();
        $category->update($data);

        return $category;
    }

    /**
     * Soft delete an existing category and assign the deleter.
     */
    public function delete(Category $category): void
    {
        $category->update(['deleted_by' => auth()->id()]);
        $category->delete();
    }
}
