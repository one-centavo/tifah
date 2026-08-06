# 025 · Edit Provider — Plan

## Approach

We implement the Edit Provider feature by leveraging the existing `Supplier` model, `SupplierService`, and standard Laravel FormRequests. We build a reactive Livewire Volt edit component that replicates the same real-time validation and DIAN Modulo 11 DV calculation seen during registration.

---

### 1. Database & Models
- Reuses the `Supplier` model (`App\Models\Supplier`) and its schema.
- The `updated_by` audit column tracks the ID of the user modifying the supplier.
- The original `created_by` column remains untouched to preserve the origin audit trail.

---

### 2. Business Logic (`App\Services\SupplierService`)
- We use the `update(Supplier $supplier, array $data): Supplier` method in the `SupplierService` class.
- The `update` method:
  - Assigns `updated_by = auth()->id()`.
  - Performs the model update and persists it.

---

### 3. Request Validation (`App\Http\Requests\Supplier\UpdateSupplierRequest`)
- Inherits from `SupplierRequestBase`.
- Appends specific unique checks on the NIT field:
  - The NIT must be unique across all active records, excluding the current supplier ID: `unique:suppliers,nit,{$id}`.
  - The NIT must follow the Colombia NIT formatting regex: `/^\d{1,3}(\.\d{3})*$/`.
- Predefined error messages map user-friendly Spanish explanations directly below each validation failure point (e.g., `'Este NIT ya se encuentra registrado.'`).

---

### 4. Real-time Verification Digit (DV) Calculation
- **Backend recalculation (`resources/views/livewire/suppliers/edit.blade.php`)**:
  - Implements an `updatedNit(string $value)` lifecycle hook.
  - Cleans the input from dots/symbols: `preg_replace('/[^\d]/', '', $value)`.
  - Applies DIAN weights: `[3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71]`.
  - Multiplies each digit in reverse order by its weight, summing the results.
  - Calculates `remainder = sum % 11`.
  - If `remainder > 1`, `dv = 11 - remainder`. Otherwise, `dv = remainder`.
- **Frontend recalculation (AlpineJS inside form)**:
  - An inline Alpine.js component initializes properties `nit` and `dv` using Livewire's `@entangled`.
  - A `$watch('nit', ...)` handler recalculates the DV locally on keypress using the same modulo 11 algorithm.
  - Bindings keep the text input for DV disabled and read-only, ensuring no manual overrides.

---

### 5. Livewire Volt Edit Component (`resources/views/livewire/suppliers/edit.blade.php`)
- Standardized layout with navigation links to return to `/suppliers`.
- Pre-populates the properties `$nit`, `$dv`, `$name`, `$contact_person`, `$phone_number`, `$email`, and `$address` during the `mount` lifecycle.
- Handles submission via the `save()` method:
  - Synchronizes final DV recalculation.
  - Validates inputs using `UpdateSupplierRequest` rules.
  - Calls `SupplierService->update`.
  - Flashes a success message to the session.
  - Redirects back to the supplier index using SPA navigation (`wire:navigate`).

---

### 6. Verification & Feature Tests
- Feature test cases in `tests/Feature/Pages/Suppliers/SupplierManagementTest.php` cover:
  - Access control blocks for guest users.
  - Successful retrieval and form pre-fill.
  - Validation failures for empty fields or invalid formats.
  - Unique NIT checks excluding the supplier currently being edited.
  - Audit trail validation (`updated_by` is saved, `created_by` remains unchanged).
