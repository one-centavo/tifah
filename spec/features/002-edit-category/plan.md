# 002 · Edit Category & Category Management — Plan

## Approach

We will implement the Category Management page (Index) and Category Edit feature using the decoupled MVC architecture with Livewire Volt:

- **Service Layer (`App\Services\CategoryService`):**
  - Handles category updates: persists changes and sets the `updated_by` field to the currently authenticated user's ID.
  - Handles category deletions: sets the `deleted_by` field to the currently authenticated user's ID and performs the soft delete operation.
- **Form Request (`App\Http\Requests\Category\UpdateCategoryRequest`):**
  - Extends `CategoryRequestBase`.
  - Implements the validation rules for editing, ensuring name uniqueness excludes the current category and ignores soft-deleted ones.
- **Livewire Volt Components:**
  - **Index View (`resources/views/livewire/categories/index.blade.php`):**
    - Lists active categories in a paginated list/table.
    - Links to category creation and edit pages.
    - Provides a delete button that calls the service layer to soft-delete the category.
  - **Edit View (`resources/views/livewire/categories/edit.blade.php`):**
    - Binds the category properties and handles editing form.
    - Queries `$category->medicines` to list all medicines belonging to the category.
    - Calls the service layer to save updates.

## Implementation

1. **Service Layer Setup:**
   - In [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php), implement:
     - `update(Category $category, array $data): Category`: Sets `updated_by = auth()->id()` and updates the category.
     - `delete(Category $category): void`: Sets `deleted_by = auth()->id()`, saves the model, and then calls `$category->delete()` to soft-delete it.

2. **Validation Setup:**
   - Create `app/Http/Requests/Category/UpdateCategoryRequest.php` extending `CategoryRequestBase`.
   - Implement `rules(?int $ignoreId = null): array` merging name uniqueness check with common rules:
     - Name validation: `['required', 'string', 'min:3', 'max:50', Rule::unique('categories', 'name')->ignore($ignoreId)->whereNull('deleted_at')]`.
   - Implement `messages()` returning Spanish (Colombia) error messages.

3. **Livewire Component - Index Page:**
   - Create Volt component at `resources/views/livewire/categories/index.blade.php` using layout `layouts.app`.
   - Define a computed property or query for `$categories` with pagination.
   - Design a table showing Name, Description, Cold Chain status, and Special Control status.
   - Add navigation link to `/categories/create` for creating categories.
   - For each category row, add:
     - An edit button linking to `/categories/{category}/edit`.
     - A delete button that prompts user confirmation and calls a `delete(int $id, CategoryService $service)` action.
   - On delete, call `$service->delete($category)`, flash a success message, and refresh the component.

4. **Livewire Component - Edit Page:**
   - Create Volt component at `resources/views/livewire/categories/edit.blade.php` using layout `layouts.app`.
   - Properties: `$category`, `$name`, `$description`, `$is_cold_chain`, `$is_special_control`.
   - In `mount(Category $category)`:
     - Load values: `$this->category = $category;`, `$this->name = $category->name;`, etc.
   - In `save(CategoryService $service)` action:
     - Validate input using rules from `UpdateCategoryRequest` passing the category's ID.
     - Call `$service->update($this->category, $validated)`.
     - Flash success message in Spanish (Colombia).
   - In the view:
     - Render the form (top section).
     - Render a table listing `$category->medicines` displaying their Name, Generic Name, and Selling Price (bottom section).

5. **Routing:**
   - Open `routes/web.php` and add inside the `auth` middleware group:
     - `Volt::route('categories', 'categories.index')->name('categories.index');`
     - `Volt::route('categories/{category}/edit', 'categories.edit')->name('categories.edit');`
   - Update layouts/navigation if necessary to point the "Categories" navigation menu to the index page instead of direct creation.

6. **Testing & QA:**
   - Create `tests/Feature/Pages/Categories/CategoryManagementTest.php` covering:
     - Redirection of guests, access of authorized users.
     - Correct rendering of categories list.
     - Deletion action: database has soft-deleted category, `deleted_by` is set correctly, and success flash message is rendered.
   - Create `tests/Feature/Pages/Categories/EditCategoryTest.php` covering:
     - Route accessibility.
     - Validations and unique constraint checks.
     - Successful update with correct `updated_by` attribute.
     - Correct rendering of belonging medicines.

## Decisions

- **Index as the Main Entry Point** — The "Categories" link in the main navigation sidebar should point to `/categories` (Index page) rather than `/categories/create`. This acts as the command center for category operations.
- **Soft Deleting from Index** — Implementing deletion directly in the index table with a standard confirmation dialog provides a clean user experience and respects the DB structure.

## Risks

- **SQL Integrity Errors on Rename/Recreate** — If a user tries to rename a category to a name that matches a soft-deleted category, the unique index on `categories.name` will throw an SQL constraint error.
  - _Mitigation:_ We will validate unique constraints against all categories, or catch the exception, informing the user.
