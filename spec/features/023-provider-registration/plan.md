# 023 · Provider Registration — Plan

## Approach

We will implement the Supplier/Provider Registration and Management feature using a decoupled architecture, separating the database/service layers from the user interface. We will reuse and extend the existing `Supplier` model, migrations, and `SupplierService` to handle CRUD actions, audit logging, and soft-delete validation constraints.

---

### 1. Database & Models
- We will reuse the `Supplier` model (`App\Models\Supplier`) and the `suppliers` table migration, which already include standard auditing fields (`created_by`, `updated_by`, `deleted_by`, `deleted_at`).
- Ensure the relationship between `Supplier`, `PurchaseOrder`, and `Lot` is defined:
  - `Supplier` has many `PurchaseOrder`
  - `PurchaseOrder` has many `Lot`
- Deletion Constraint Check: A supplier has active inventory lots if there is any `Lot` with `current_quantity > 0` associated with any `PurchaseOrder` belonging to that supplier.

---

### 2. Business Logic (`App\Services\SupplierService`)
We will extend `App\Services\SupplierService` to implement the following methods:
- `create(array $data): Supplier`: Automatically assign `created_by` to the authenticated user ID and persist.
- `update(Supplier $supplier, array $data): Supplier`: Automatically assign `updated_by` to the authenticated user ID, update the supplier details, and persist.
- `delete(Supplier $supplier): void`:
  - Query for active inventory lots:
    ```php
    $hasActiveLots = Lot::whereHas('purchaseOrder', function ($query) use ($supplier) {
        $query->where('supplier_id', $supplier->id);
    })->where('current_quantity', '>', 0)->exists();
    ```
  - If `$hasActiveLots` is true, throw a `ValidationException` preventing the deletion with the message: `"No se puede eliminar el proveedor porque tiene lotes de mercancía activos en el inventario."`
  - Otherwise, record `deleted_by = auth()->id()`, save the model, and trigger the soft delete via `$supplier->delete()`.

---

### 3. Validation & Requests
We will create and update the request classes under `App\Http\Requests\Supplier`:
- **Modify `SupplierRequestBase`**:
  - Change `contact_person` rule to `['nullable', 'string', 'max:100']` to make it optional.
  - Set `nit` rule to `['required', 'string', 'max:20']`.
  - Set `address` rule to `['required', 'string', 'max:200']` (as required: max 200 characters).
- **Create `StoreSupplierRequest`**:
  - Merge common rules and append unique validation for NIT:
    ```php
    'nit' => ['required', 'string', 'max:20', 'unique:suppliers,nit', 'regex:/^\d{1,3}(\.\d{3})*$/']
    ```
    *(Regex allows digits with standard Colombian punctuation format, e.g. 900.123.456)*
  - Add custom messages in Spanish:
    - `nit.unique` => `"Este NIT ya se encuentra registrado"`
    - `nit.regex` => `"El formato del NIT es inválido (ej: 900.123.456)."`
- **Create `UpdateSupplierRequest`**:
  - Merge common rules and append unique validation for NIT excluding the current record:
    ```php
    'nit' => ['required', 'string', 'max:20', 'unique:suppliers,nit,' . $id, 'regex:/^\d{1,3}(\.\d{3})*$/']
    ```

---

### 4. Real-time DV Calculation Algorithm (DIAN Modulo 11)
To calculate the verification digit (DV) reactively as the user types the NIT:
1. **Frontend (Javascript / Livewire reactivity)**:
   - Listen to changes on the `nit` input field.
   - Clean the input value by removing dots, hyphens, and spaces: `const cleanNit = nit.replace(/[^\d]/g, '');`
   - Apply the DIAN Modulo 11 algorithm weights: `[3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71]`.
   - Calculate the sum of multiplying each digit from right to left by its corresponding weight.
   - Calculate `remainder = sum % 11`.
   - If `remainder === 0 || remainder === 1`, `dv = remainder`. Otherwise, `dv = 11 - remainder`.
   - Bind the result to the read-only `dv` property in the component. If `nit` is cleared, `dv` is reset to null/empty immediately.
2. **Backend (Form Request / Service Validation)**:
   - Implement a helper method `calculateDv(string $nit): int` replicating this logic to double-check and validate the correctness of the DV before saving.

---

### 5. Volt Components & Views
We will create three Livewire Volt components:
- `resources/views/livewire/suppliers/index.blade.php`:
  - List of non-deleted suppliers with search (by Razón Social or NIT).
  - Actions: Edit link and Delete button.
  - Confirmation modal for soft delete.
  - Display error flash if soft delete is rejected due to active inventory lots.
- `resources/views/livewire/suppliers/create.blade.php`:
  - Registration form with fields for NIT, DV (disabled), Razón Social, Contact Name, Telephone, Email, and Physical Address.
  - Reactive recalculation of DV upon typing NIT.
  - Validation error highlighting and feedback.
  - Call `SupplierService->create` upon submission.
  - Session success flash messaging and redirection back to list.
- `resources/views/livewire/suppliers/edit.blade.php`:
  - Pre-filled edit form.
  - Validate and call `SupplierService->update`.
  - Record audit logging for updating while keeping the original creator.

---

### 6. Routing & Navigation
- Add routes in `routes/web.php` for suppliers index, create, and edit, protected by the `auth` middleware.
- Add supplier navigation links in the layout navigation sidebar.

---

### 7. Automated Testing
We will create feature tests:
- `tests/Feature/Pages/Suppliers/SupplierManagementTest.php` using Pest:
  - Authorized users (Admin/Warehouse Assistant) can view index, register, edit, and delete suppliers.
  - Guest/unauthorized users are redirected to login.
  - Validate NIT format, uniqueness, and DV calculation checks.
  - Verify that soft deleting a supplier sets `deleted_at` and `deleted_by`.
  - Verify that deleting a supplier with active inventory lots throws a validation error and aborts.
  - Verify editing preserves `created_by` and updates `updated_by`.
