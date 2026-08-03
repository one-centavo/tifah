# 008 · Laboratory Soft Deletion — Plan

## Approach

We will implement the laboratory soft deletion workflow using the existing `LaboratoryService` and the Livewire Volt component for laboratories (`App\Livewire\Laboratories\Index` at `resources/views/livewire/laboratories/index.blade.php`).

### 1. Business Logic & Validation (`LaboratoryService`)
- In `LaboratoryService.php` (at `app/Services/LaboratoryService.php`), update/implement the `delete` method:
  - Check if the laboratory has any active medicines (`$laboratory->medicines()->exists()`).
  - If active medicines are associated with the laboratory, abort the action by throwing a `ValidationException` with the error message: `"No se puede eliminar el laboratorio porque tiene medicamentos activos asociados."`
  - If validation passes, set `deleted_by` to the current user's ID, save, and perform the soft delete (`$laboratory->delete()`).
- Implement the `restore(Laboratory $laboratory)` method:
  - Reset the `deleted_by` attribute to `null`.
  - Restore the model (`$laboratory->restore()`).

### 2. Livewire Volt Component Integration (`index.blade.php`)
- In `resources/views/livewire/laboratories/index.blade.php`:
  - Introduce properties to manage the deletion confirmation modal state:
    - `$laboratoryIdBeingDeleted` (integer|null) to store the target laboratory ID.
    - `$laboratoryNameBeingDeleted` (string) to store the target laboratory name for display in the modal.
  - Implement a method `confirmLaboratoryDeletion(int $id)` to set the target laboratory properties and dispatch the event to open the confirmation modal.
  - Implement/update `deleteLaboratory(LaboratoryService $laboratoryService)`:
    - Call `$laboratoryService->delete()` on the laboratory being deleted.
    - Wrap the service call inside a `try/catch` block for `ValidationException`.
    - If a validation exception is caught, add the error to the component errors bag (`deletion_error`) to be displayed in the modal.
    - If successful, close the modal, reset target properties, flash the success message (`"El laboratorio ha sido eliminado con éxito."`), and refresh/re-render.
  - Implement `restoreLaboratory(int $id, LaboratoryService $laboratoryService)`:
    - Retrieve the archived laboratory using `Laboratory::onlyTrashed()->findOrFail($id)`.
    - Call `$laboratoryService->restore()`.
    - Flash the success message (`"El laboratorio ha sido restaurado con éxito."`).

### 3. UI/UX Elements
- Implement the confirmation modal using the `<x-modal name="confirm-laboratory-deletion">` component in the Blade file.
- Display potential validation errors within the modal using the `<x-input-error :messages="$errors->get('deletion_error')" />` component.
- Display status badges for laboratories:
  - `"Activo"` (Lime/Green badge) if `deleted_at` is null.
  - `"Archivado"` (Gray/Slate badge) if `deleted_at` is not null.
- Adjust action buttons:
  - Active: Show "Editar" and "Eliminar" (which triggers the confirmation modal).
  - Archived: Show "Restaurar" (which triggers `restoreLaboratory`).

### 4. Database & Query Considerations
- The soft delete is handled by Laravel's built-in `SoftDeletes` trait on the `Laboratory` model.
- Check relation existence using `$laboratory->medicines()->exists()` (since `Medicine` also utilizes `SoftDeletes`).
- In the `with()` query:
  - Apply soft-delete status scope based on the active `$status` filter:
    - `'active'` (default) -> normal query (only active/non-deleted laboratories).
    - `'archived'` -> use `onlyTrashed()`.
    - `'all'` -> use `withTrashed()`.

### 5. Automated Tests
- Update/add test scenarios in `tests/Feature/Pages/Laboratories/LaboratoryManagementTest.php`:
  - Redirection of guest users when accessing the laboratories index.
  - Display list of active laboratories by default.
  - Successful soft deletion of a laboratory without associated active medicines, validating that `deleted_by` is recorded.
  - Failed soft deletion of a laboratory with associated active medicines, displaying a validation error.
  - Searching laboratories by name and filtering by status.
  - Successful restoration of a soft-deleted/archived laboratory.
