# 012 · Sanitary Registry Soft Deletion — Plan

## Approach

We will document the soft deletion workflow for sanitary registries, which leverages the existing `SanitaryRegistryService` and the Livewire Volt component (`App\Livewire\SanitaryRegistries\Index` at `resources/views/livewire/sanitary-registries/index.blade.php`).

### 1. Business Logic & Validation (`SanitaryRegistryService`)
- In `SanitaryRegistryService.php` (at `app/Services/SanitaryRegistryService.php`), implement the `delete` method:
  - Check if the registry has active medicines: `$registry->medicines()->exists()`.
  - If active medicines exist, abort by throwing a `ValidationException` with the message: `"No se puede eliminar el registro sanitario porque tiene medicamentos activos asociados."`
  - If validation passes, set `deleted_by` to the current user's ID, save, and perform the soft delete: `$registry->delete()`.

### 2. Livewire Volt Component Integration (`index.blade.php`)
- In `resources/views/livewire/sanitary-registries/index.blade.php`:
  - Manage confirmation modal state using:
    - `$confirmingRegistryDeletion` (boolean) to control the modal visibility.
    - `$registryIdBeingDeleted` (integer|null) to store the target registry ID.
  - Implement a method `confirmRegistryDeletion(int $id)` to set the target registry and trigger the modal.
  - Implement `deleteRegistry(SanitaryRegistryService $service)`:
    - Wrap `$service->delete()` in a `try/catch` block for `ValidationException`.
    - If a validation error occurs, catch it and register the error in the component error bag (`deletion_error`) to display it.
    - If deletion succeeds, reset modal state, flash success message `"El registro sanitario ha sido eliminado con éxito."`, and re-render.
  - Implement `restoreRegistry(int $id, SanitaryRegistryService $service)` to allow restoring archived registries if needed, flashing `"El registro sanitario ha sido restaurado con éxito."`.

### 3. UI/UX Elements
- Confirmation modal using standard `<x-modal name="confirm-sanitary-registry-deletion">`.
- Validation errors display using `<x-input-error :messages="$errors->get('deletion_error')" />`.
- Success notifications using Livewire session flash messages.

### 4. Database & Query Considerations
- Database level soft-deletes via Eloquent's `SoftDeletes` trait on the `SanitaryRegistry` model.
- Record the user responsible via `deleted_by` column on deletion.

### 5. Automated Tests
- Test file: `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php`.
- Test cases:
  - Guest users are redirected to login.
  - Authorized users can access the index page.
  - Soft deletion is blocked when active medicines are associated, showing the validation error.
  - Soft deletion succeeds when no active medicines are associated, recording `deleted_by` and `deleted_at`.
  - Restoration of soft-deleted registries works.
