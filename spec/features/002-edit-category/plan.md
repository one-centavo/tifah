# 002 · Edit Category — Plan

## Approach

We will implement the Category Modification feature using the established decoupled architecture:

- The service layer (`App\Services\CategoryService`) will handle updating the Eloquent model instance, ensuring `updated_by` is set to the currently authenticated user's ID.
- A dedicated `UpdateCategoryRequest` class will encapsulate the validation rules, specifically implementing a unique check that ignores the category currently being updated and filters out soft-deleted records.
- A Livewire Volt Single File Component (`resources/views/livewire/categories/edit.blade.php`) will handle the view and controller logic: binding form state, initiating the validation, calling the service, showing confirmation messages, and listing the affected medicines.

This keeps user interface and validation separate, maintains clean controllers, and ensures testability of the update process.

## Implementation

1. **Service Layer Update:**
   - Open [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php).
   - Add a public method `update(Category $category, array $data): Category` that accepts the category model and updated inputs.
   - Automatically assign `updated_by` to the authenticated user's ID via `auth()->id()`.
   - Update the model instance and return it.

2. **Validation Strategy:**
   - Create a new concrete FormRequest class `UpdateCategoryRequest` at `app/Http/Requests/Category/UpdateCategoryRequest.php` extending `CategoryRequestBase`.
   - Implement the `rules(?int $ignoreId = null)` method:
     - Merge the specific name rules with the common rules from the base class.
     - Use `Rule::unique('categories', 'name')->ignore($ignoreId)->whereNull('deleted_at')` to validate name uniqueness against active categories (excluding itself).
   - Implement `messages()` returning Spanish (Colombia) error messages.

3. **Livewire Component Development:**
   - Create the Volt component at `resources/views/livewire/categories/edit.blade.php`.
   - Define properties for `$category` (Model binding), `$name`, `$description`, `$is_cold_chain`, and `$is_special_control`.
   - In the `mount(Category $category)` method, populate the form fields and load the category's medicines.
   - Implement a dynamic `rules()` method that returns `(new UpdateCategoryRequest())->rules($this->category->id)`.
   - Implement `save(CategoryService $categoryService)` method:
     - Perform validation.
     - Call `$categoryService->update($this->category, $validated)`.
     - Flash a success message and keep the updated values in the form.
   - Build the view layout:
     - The top section contains the category update form matching the design of the create form.
     - The bottom section displays the list/table of affected medicines belonging to this category.

4. **Routing:**
   - Open `routes/web.php`.
   - Add `Volt::route('categories/{category}/edit', 'categories.edit')->name('categories.edit');` inside the `auth` middleware group.

5. **Testing & QA:**
   - Create `tests/Feature/Pages/Categories/EditCategoryTest.php`.
   - Write Pest feature tests covering:
     - Redirect guest users.
     - Authorized user access.
     - Validation: name required, min 3, max 50 characters, description max 255.
     - Validation: uniqueness check ignoring itself and ignoring soft-deleted categories.
     - Database verification for updated data and `updated_by`.
     - Verification of the affected medicines listing on the edit page.

## Decisions

- **Dynamic Request Passing in Volt** — Since Volt components compile into standard Livewire components, passing `$this->category->id` into `UpdateCategoryRequest::rules` manually during execution allows us to utilize Laravel's powerful `FormRequest` validation rules while keeping Livewire reactive binding working cleanly.
- **Displaying Affected Medicines** — Displaying the medicines directly on the edit page meets the requirement of showing the user which products are affected. The medicines will be fetched via the Eloquent `$category->medicines` relationship.

## Risks

- **Concurrent Modification** — If another user changes the category name to a name that was recently occupied, standard database unique index might raise errors.
  - _Mitigation:_ The uniqueness rule includes the `whereNull('deleted_at')` filter, matching the DB structure and ensuring the user gets a friendly error instead of a database crash.
- **SQL Integrity Errors with Soft Deletes** — Because the database has a unique index on `name` without taking `deleted_at` into account, trying to rename a category to a name that belongs to a soft-deleted category will cause a database-level integrity error.
  - _Mitigation:_ The `UpdateCategoryRequest` rule will check uniqueness across all categories, or we will handle database constraint validation. Since the database unique index is on `name` only, we must validate uniqueness against all rows (including soft deleted) to prevent DB 500 errors, even though the HU specifies "otro registro activo". We will add a fallback validation or catch `UniqueConstraintViolationException` during save to give a clean message.
