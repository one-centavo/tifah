# 014 · Edit Medicine & Medicine Catalog Management — Plan

## Approach

We will implement the Medicine Edit feature using Livewire Volt, a service-layer method in `MedicineService`, and appropriate form validation.

- **Service Layer (`App\Services\MedicineService`):**
  - Implement `update(Medicine $medicine, array $data, array $barcodesData): Medicine` to handle medicine updates, setting `updated_by` to the authenticated user, and managing barcodes (creating new ones, updating allowed ones, and deleting allowed ones) within a database transaction.
- **Verification Helpers on `Medicine`:**
  - Add helper methods to the `Medicine` model (or check in service/component) to easily check if it has lots or sales:
    - `hasLotsOrSales(): bool` -> returns true if `$this->lots()->exists()` or `$this->lots()->whereHas('billDetails')->exists()`.
- **Validation Rules:**
  - If the medicine has lots or sales, validation for Master Data is skipped since they are read-only.
  - If editable, we validate the unique combination of `[name, generic_name, concentration_value, concentration_unit_id, container_id, content_quantity, content_unit_id, laboratory_id]` ignoring the current medicine's ID.
  - Verify barcode uniqueness across `medicine_barcodes` table.
- **Livewire Volt Component (`resources/views/livewire/medicines/edit.blade.php`):**
  - Path: `/medicines/{medicine}/edit` mapped via route model binding.
  - In `mount(Medicine $medicine)`:
    - Load medicine fields into component properties.
    - Check if the medicine has lots or sales to set `$isMasterDataReadOnly` boolean flag.
    - Populate `$barcodes` array. Each item will track: `id`, `barcode`, `is_main`, `is_new`, and whether it is editable.
  - Barcode manager actions:
    - `addBarcode(string $newBarcode)`: Validates format and adds to local `$barcodes` state array with `is_new => true`.
    - `removeBarcode(int $index)`: Removes from state (only if `is_new` is true or if medicine has no lots/sales).
    - `updateBarcode(int $index, string $value)`: Updates the value in the state (only if allowed).
  - Validation:
    - Use custom rules or inline validation.
  - Success Feedback:
    - Success flash message in Spanish (Colombia) and navigation back to index or page reload.

## Implementation Steps

1. **Model Helpers (`App\Models\Medicine`):**
   - Add `hasLotsOrSales(): bool` method:
     ```php
     public function hasLotsOrSales(): bool
     {
         return $this->lots()->exists() || $this->lots()->whereHas('billDetails')->exists();
     }
     ```

2. **Service Layer Updates (`App\Services\MedicineService`):**
   - Implement `update(Medicine $medicine, array $data, array $barcodes): Medicine` in a database transaction:
     - Update medicine fields with `$data` (only updating fields that are allowed to change or all if master data is editable).
     - Set `updated_by = auth()->id()`.
     - Sync barcodes:
       - For barcodes in array with `is_new == true`, create new `MedicineBarcode` records (with `created_by = auth()->id()`).
       - For existing barcodes, if modified and allowed, update them (with `updated_by = auth()->id()`).
       - For deleted barcodes (present in database but missing from the array), soft-delete them (with `deleted_by = auth()->id()`), but only if the medicine has no lots or sales.

3. **Livewire Component - Edit Page:**
   - Create Volt component at `resources/views/livewire/medicines/edit.blade.php`.
   - Layout should use `layouts.app`.
   - Render form:
     - Master Data fields (`name`, `generic_name`, etc.) should have `:disabled="$isMasterDataReadOnly"` or `readonly` attribute.
     - Operational fields (`selling_price`, `min_stock`, etc.) are always enabled.
     - Barcode list manager section:
       - Table or list of barcodes.
       - Input field with "Añadir" button.
       - Each barcode row should have:
         - Input field for the barcode (disabled if not editable).
         - "Eliminar" icon/button (disabled if not editable).
     - "Guardar Cambios" button.
     - "Cancelar" button redirecting to index.

4. **Routing:**
   - In `routes/web.php` inside the auth group, register:
     `Volt::route('medicines/{medicine}/edit', 'medicines.edit')->name('medicines.edit');`

5. **Testing:**
   - Create `tests/Feature/Pages/Medicines/EditMedicineTest.php`:
     - Test access: guest is redirected, authorized user can view.
     - Test form loads correct values.
     - Test that when medicine has lots or sales, Master Data fields are disabled/read-only and cannot be updated.
     - Test that when medicine has no lots or sales, Master Data fields can be updated.
     - Test validation on unique combination of master data when editing.
     - Test barcode list management:
       - Adding a new unique barcode.
       - Preventing adding a duplicate barcode.
       - Preventing editing/deleting of existing barcodes if medicine has lots/sales.
       - Allowing editing/deleting of new barcodes in the session or if medicine has no lots/sales.
     - Test successful update redirects to index with Spanish success message.

## Decisions

- **Session Barcode Tracking:** We will use a state array of barcodes in Livewire containing `id`, `barcode`, `is_new`, `is_main`. Temporary additions will have `is_new => true`.
- **Master Data Read-only Enforcement:** Beyond HTML `disabled` attributes (which can be bypassed), the backend validation and service layer must strictly enforce that if `$medicine->hasLotsOrSales()` is true, no master data fields are updated in the database.

## Risks

- **Bypassing client-side read-only restriction:** If a malicious user inspects the page and removes the `disabled` attributes to submit modified master data.
  - *Mitigation:* The `MedicineService@update` must ignore any changes to master data if `$medicine->hasLotsOrSales()` is true, and the validation should guard against this.
