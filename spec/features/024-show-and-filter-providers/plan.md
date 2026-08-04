# 024 · Show and Filter Providers — Plan

## Approach

We will implement the supplier/provider visualization and filtering system using a reactive Livewire Volt component. The component will query the `Supplier` model, apply filters dynamically, and render a responsive admin table with pagination and sorting.

---

### 1. Database & Models
- We query the `suppliers` table using Eloquent.
- For the soft delete state check, we check the `deleted_at` field.
- **Active Supplier**: `deleted_at` is null.
- **Archived Supplier**: `deleted_at` is NOT null.
- To filter by soft-delete state:
  - If status is `active`: use standard query `Supplier::query()` which automatically excludes trashed records.
  - If status is `archived`: use `Supplier::onlyTrashed()`.
  - If status is `all`: use `Supplier::withTrashed()`.

---

### 2. Livewire Volt Component (`resources/views/livewire/suppliers/index.blade.php`)
The component handles component state and handles reactive query construction:
- **Properties**:
  - `public string $search = ''`: The search query.
  - `public string $status = 'active'`: Current status filter (defaults to `'active'`).
  - `public string $sortField = 'name'`: Sort column (defaults to `'name'` / Razón Social).
  - `public string $sortDirection = 'asc'`: Sort direction (defaults to `'asc'`).
- **Reactive Hooks**:
  - `updatingSearch()`: Resets pagination page to 1.
  - `updatingStatus()`: Resets pagination page to 1.
- **Methods**:
  - `sortBy(string $field)`: Toggles sorting directions between `'asc'` and `'desc'` or changes the sorting field.
  - `confirmSupplierDeletion(int $id)`: Opens the confirmation modal and saves the ID of the supplier to delete.
  - `deleteSupplier(SupplierService $supplierService)`: Invokes the `SupplierService` to handle soft deletion.
  - `with()`: Computes paginated suppliers using matching filters and sorting rules.

---

### 3. User Interface (Blade template)
- **Filters**:
  - A text input for `$search` with `wire:model.live.debounce.300ms` for reactive global search.
  - A select element for `$status` with `wire:model.live`.
- **Table**:
  - Responsive table showing columns: NIT (format: `NIT-DV`), Razón Social, Contacto, Teléfono / Email, Dirección, Estado (represented by Activo/Archivado badges).
  - Headers for sortable columns include a button running `sortBy('column_name')` and display sorting indicators.
- **Action Buttons**:
  - `Editar` link redirecting to `route('suppliers.edit', $id)`.
  - `Eliminar` button triggering the confirmation modal for active suppliers.
- **Confirmation Modal**:
  - Standard `<x-modal>` component asking for deletion confirmation.
  - Displays error messages inside the modal if the deletion check fails.

---

### 4. Service Layer Validation (`App\Services\SupplierService`)
- Deletion verification logic:
  ```php
  $hasActiveLots = Lot::whereHas('purchaseOrder', function ($query) use ($supplier) {
      $query->where('supplier_id', $supplier->id);
  })->where('current_quantity', '>', 0)->exists();
  ```
- If the supplier is linked to active lots in stock, throwing `ValidationException` which gets captured by the Livewire component and rendered in the modal.
- If safe to delete, setting `deleted_by = auth()->id()` and calling `$supplier->delete()`.

---

### 5. Testing Plan
We verify features via Pest:
- Guest users are redirected to login when visiting `/suppliers`.
- Authorized roles can access the page.
- Checking that active list loads by default.
- Verifying search filters partial matches of NIT and name.
- Verifying status filters switch between active and archived.
- Verifying deletion errors out if active inventory lots exist, and successfully archives if none exist.
