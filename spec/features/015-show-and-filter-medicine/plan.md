# 015 · Show and Filter Medicine — Plan

## Approach

We will modify the Medicine Management Index page to align with the requirements of HU 08: Visualización y Búsqueda de Medicamentos. The implementation details are as follows:

### 1. Data Model Enhancements (`App\Models\Medicine`)
We will add helper methods and accessors to format data and calculate stock:
- **`totalStock` Attribute (or helper):** Returns the sum of `current_quantity` of all associated lots:
  `$this->lots()->sum('current_quantity')`
- **`hasActiveLots()` Helper:** Returns a boolean indicating if there is any lot associated with the medicine that has `status = 'active'` and `current_quantity > 0`:
  `$this->lots()->where('status', 'active')->where('current_quantity', '>', 0)->exists()`
- **`concentration_formatted` Attribute:** Formats the technical concentration as a concatenated string (e.g., `500 mg`):
  `number_format((float) $this->concentration_value, 0) . ' ' . ($this->concentrationUnit->symbol ?? '')`

### 2. Service Layer Enforcement (`App\Services\MedicineService`)
- In `delete(Medicine $medicine)`, validate if the medicine has active lots using `$medicine->hasActiveLots()`.
- If true, throw a `ValidationException` with an appropriate error message in Spanish:
  `"No se puede archivar el medicamento porque existen lotes activos en el inventario asociados a este producto."`
- Otherwise, proceed with the transaction to soft-delete the medicine and its barcodes.

### 3. Livewire View and Component (`resources/views/livewire/medicines/index.blade.php`)
- **Table Headers and Data:**
  - Add a **Stock Actual / Alerta** column.
  - Render the formatted concentration using the new attribute or formatting inline: `{{ $medicine->concentration_value }} {{ $medicine->concentrationUnit->symbol }}`.
  - Render the presentation using `$medicine->presentation`.
  - In the **Stock Actual / Alerta** column:
    - Display the total stock (`$medicine->lots->sum('current_quantity')`).
    - If total stock is equal to or lower than `$medicine->min_stock`, display an alert badge (e.g., orange/red with a warning icon).
- **Actions:**
  - **Ver Detalle (View Detail):**
    - Store the selected medicine ID or model in a component state variable (e.g. `$selectedMedicine` or `$detailMedicineId`).
    - Implement a `viewDetails(int $id)` method that sets this state and opens the detail modal.
    - Implement an elegant modal component `<x-modal name="medicine-detail-modal">` showing all technical details of the selected medicine (INVIMA Registry, Cold Chain requirements, Special Control requirements, Laboratory, full list of Barcodes, Creator, Creation date, updater, description, and details of stock).
  - **Editar (Edit):** Keep the existing link redirecting to `/medicines/{medicine}/edit`.
  - **Archivar (Archive):**
    - The delete confirm modal is already in place. Keep the existing Flow, which calls `deleteMedicine`.
    - Ensure `deleteMedicine` catches the `ValidationException` thrown by the service layer and populates the `deletion_error` key in the Livewire error bag to show the error inside the modal.

### 4. Tests (`tests/Feature/Pages/Medicines/MedicineManagementTest.php`)
We will add feature tests to cover:
- Checking that the "Stock Actual / Alerta" column correctly reflects the sum of lot quantities.
- Checking that a warning indicator is shown when stock is <= `min_stock`.
- Checking that clicking "Ver Detalle" loads the modal with all technical details.
- Checking that archiving a medicine with active lots fails and shows an error message.
- Checking that archiving a medicine with no active lots successfully soft-deletes it.

## Risks & Mitigations

- **Performance Issues with Eager Loading:** Summing lot quantities on each row can trigger N+1 query problems.
  - *Mitigation:* In the component's query, we must eager load `lots` along with `category`, `laboratory`, `sanitaryRegistry`, etc., or select the sum via a subquery. Eager loading `lots` is simple and sufficient for standard pagination sizes.
- **Race Condition in Deletion:** A lot could be created or stock added after the deletion check but before the transaction commits.
  - *Mitigation:* Performing the check inside the database transaction in `MedicineService@delete` ensures atomic safety.
