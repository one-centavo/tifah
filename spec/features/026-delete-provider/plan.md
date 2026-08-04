# 026 · Delete Provider — Plan

## Approach

We implement the Delete Provider feature utilizing Laravel's native Eloquent `SoftDeletes` trait, mapping the deletion action via the `SupplierService` to enforce inventory constraints, and integrating the confirmation and exception workflow directly in the Livewire Volt listing component.

---

### 1. Database & Models
- **`Supplier` Model**:
  - Leverages the `SoftDeletes` trait to execute a soft delete on the `suppliers` database table.
  - The `deleted_by` column tracks the ID of the user performing the deletion.
  - The `deleted_at` column tracks the exact timestamp of deletion.
- **Relational Integrity**:
  - Soft-deleted suppliers are automatically filtered out from active queries (e.g. for selection dropdowns in operational forms) by Laravel, satisfying the out-of-operations requirement.

---

### 2. Business Logic (`App\Services\SupplierService`)
- Implements `delete(Supplier $supplier): void`.
- **Active inventory validation**:
  - Interrogates associated purchase orders and active lots in stock (`current_quantity > 0`).
  - Throws `ValidationException` if active lots exist with the message: `"No se puede eliminar el proveedor porque tiene lotes de mercancía activos en el inventario."`
- **Soft deletion execution**:
  - Assigns `deleted_by = auth()->id()`.
  - Calls `$supplier->delete()` to trigger Eloquent soft delete.

---

### 3. Livewire List Component (`resources/views/livewire/suppliers/index.blade.php`)
- **Initiation**:
  - The "Archivar" action button invokes `confirmSupplierDeletion(int $id)`.
- **Confirmation Flow**:
  - `confirmSupplierDeletion` sets the target supplier properties and triggers the `open-modal` event targeting the confirmation modal `#confirm-supplier-deletion`.
- **Execution**:
  - Submitting the modal triggers `deleteSupplier(SupplierService $supplierService)`.
  - Captures `ValidationException` on failure to set `deletion_error` inside the component errors array, displaying feedback inline.
  - On success, it closes the modal, resets state, flashes a success message to the session, and stays on the page using Livewire reactive state updates.

---

### 4. Verification & Feature Tests
- Feature test cases in `tests/Feature/Pages/Suppliers/SupplierManagementTest.php` cover:
  - Redirection of unauthenticated guest users.
  - Successful soft delete and user ID tracking on `deleted_by`.
  - Business constraint blocking deletion if active inventory lots are linked.
  - Allowed operations (creation/update) referencing the NIT of soft-deleted records.
