# 017 · Medicine Soft Deletion — Plan

## Approach

We will implement the logical deletion (archiving) of medicines using `MedicineService@delete`, integrating the workflow in the main Livewire Volt component for medicines (`App\Livewire\Medicines\Index`).

### 1. Database and Model Configuration (`App\Models\Medicine`)
- Use the `SoftDeletes` trait on the `Medicine` model to automatically filter out soft-deleted records from standard queries.
- Define relationships and attributes to track audit details:
  - `deleter` (`belongsTo(User::class, 'deleted_by')`) to link the user who deleted the medicine.
  - `hasActiveLots()` method to check if there are associated lots with `status = 'active'` and `current_quantity > 0`.
- Maintain Cascade Soft Deletion: when a medicine is deleted, all associated `MedicineBarcode` records must also be soft-deleted and update their `deleted_by` fields.

### 2. Business Logic Layer (`App\Services\MedicineService`)
- Implement/update the `delete(Medicine $medicine)` method:
  - Check `$medicine->hasActiveLots()`.
  - If true, throw a `ValidationException` with the message: `"No se puede archivar el medicamento porque existen lotes activos en el inventario asociados a este producto."`
  - In a database transaction:
    - Update the medicine's `deleted_by` to the authenticated user's ID.
    - Perform soft delete (`$medicine->delete()`).
    - Loop through all barcodes and update their `deleted_by` to the authenticated user's ID, then soft delete them.

### 3. Component Controller Integration (`resources/views/livewire/medicines/index.blade.php`)
- **State variables:**
  - `$medicineIdBeingDeleted` (integer|null) to hold the ID of the medicine to delete.
  - `$medicineNameBeingDeleted` (string) to hold the commercial name of the medicine for the confirmation modal.
- **Methods:**
  - `confirmMedicineDeletion(Medicine $medicine)`: Sets the deletion state variables and dispatches the open event for the confirmation modal.
  - `deleteMedicine(MedicineService $service)`:
    - Verifies `$medicineIdBeingDeleted` is set.
    - Resolves the medicine (using `Medicine::findOrFail`).
    - Calls `$service->delete($medicine)`.
    - Handles exceptions: if a `ValidationException` is caught, displays the validation error in the modal.
    - On success: closes the modal, resets state variables, flashes a success notification (`"El medicamento ha sido eliminado con éxito."`), and refreshes the list.
- **List Filtering:**
  - Filter the search query based on the active visibility filter (`$softDeleteFilter`):
    - `'active'` (default): only active medicines.
    - `'archived'`: only soft-deleted medicines (`onlyTrashed()`).
    - `'all'`: all medicines (`withTrashed()`).

### 4. User Interface Changes
- **Confirmation Modal:**
  - Include an `<x-modal name="confirm-medicine-deletion">` containing the warning and action buttons.
  - Render validation errors using `<x-input-error :messages="$errors->get('deletion_error')" />`.
- **List & Actions:**
  - Add an "Archivar" action button to the table rows and detailed view modal if the medicine is not archived.
  - Display the "Eliminado por..." audit text in the detailed view if the medicine is soft-deleted.

### 5. Automated Testing (`tests/Feature/Pages/Medicines/MedicineManagementTest.php`)
- Write tests to verify:
  - Unauthorized users cannot access/trigger deletion.
  - A medicine with active lots cannot be deleted and displays a validation error.
  - A medicine without active lots can be successfully soft-deleted:
    - `deleted_at` is populated.
    - `deleted_by` is recorded.
    - Associated barcodes are also soft-deleted with `deleted_by` recorded.
  - The index page list excludes archived medicines by default, but displays them when the archived filter is selected.
