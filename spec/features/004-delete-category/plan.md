# 004 · Category Soft Deletion — Plan

## Approach

We will implement the category soft deletion workflow using the existing `CategoryService` and the Livewire Volt component for categories (`App\Livewire\Categories\Index` at `resources/views/livewire/categories/index.blade.php`).

### 1. Business Logic & Validation (`CategoryService`)
- In `CategoryService.php` (at `app/Services/CategoryService.php`), update the `delete` method:
  - Check if the category has any active medicines (`$category->medicines()->exists()`).
  - If active medicines are associated with the category, abort the action by throwing a `ValidationException` (or custom exception handled in the component) with the error message: `"No se puede eliminar la categoría porque tiene medicamentos activos asociados."`
  - If validation passes, set `deleted_by` to the current user's ID, save, and perform the soft delete (`$category->delete()`).

### 2. Livewire Volt Component Integration (`index.blade.php`)
- In `resources/views/livewire/categories/index.blade.php`:
  - Introduce properties to manage the deletion confirmation modal state:
    - `$confirmingCategoryDeletion` (boolean) to toggle the modal.
    - `$categoryIdBeingDeleted` (integer|null) to store the target category ID.
  - Implement a method `confirmCategoryDeletion(int $id)` to set the target category and open the confirmation modal.
  - Update `deleteCategory(CategoryService $service)`:
    - Wrap the service call inside a `try/catch` block (specifically catching `ValidationException` or handling service exceptions).
    - If a validation exception is caught, display the validation error message using standard Livewire error display or custom alert.
    - If successful, hide the modal, reset states, flash the success message (`"La categoría ha sido eliminada con éxito."`), and refresh/re-render.

### 3. UI/UX Elements
- Implement the confirmation modal in the Blade file (e.g. using a secondary action modal style compliant with Breeze).
- Display error alerts using a flash session message or a custom alert component.
- Display success feedback via flash messages (e.g. `session()->flash('status', ...)` or a toast component).

### 4. Database & Query Considerations
- The soft delete is handled by Laravel's built-in `SoftDeletes` trait, which is already configured in the `Category` model.
- Relationships check is performed using `medicines()->exists()` which queries the active medicines (since `Medicine` also uses `SoftDeletes`).

### 5. Automated Tests
- Update/add test scenarios in `tests/Feature/Pages/Categories/CategoryManagementTest.php` or a separate feature test file:
  - An authenticated user can trigger a soft delete.
  - A guest cannot delete a category.
  - Attempting to delete a category with active medicines throws/shows an error and does not delete it.
  - Deleting a category without medicines succeeds, setting the `deleted_at` and `deleted_by` fields.
