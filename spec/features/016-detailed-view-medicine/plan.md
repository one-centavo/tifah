# 016 · Detailed View of Medicine — Plan

## Approach

We will implement the detailed view of a medicine as an interactive modal component or section in the Medicines Index page, ensuring that all data fields are populated dynamically, and incorporating audit fields including soft-delete metadata.

### 1. Data Model Enhancements (`App\Models\Medicine`)
The `Medicine` model already has the relations `creator`, `updater`, and `deleter` mapped. We will leverage them:
- `creator` (`belongsTo(User::class, 'created_by')`)
- `updater` (`belongsTo(User::class, 'updated_by')`)
- `deleter` (`belongsTo(User::class, 'deleted_by')`)
- Accessors `presentation` and `concentration_formatted` are already defined and will be used to render formatted values.

### 2. Component Logic (`resources/views/livewire/medicines/index.blade.php`)
- **State Property:** `$selectedMedicineId` tracks the current medicine details being viewed.
- **Eager Loading:** Ensure the component query includes `creator`, `updater`, `deleter`, `category`, `laboratory`, `sanitaryRegistry`, `barcodes`, and `lots` to prevent N+1 queries.
- **Soft-Deleted Medicines Details:**
  - Update the Livewire component list query to optionally fetch soft-deleted medicines depending on status filters.
  - Allow the `viewDetails(int $id)` method to retrieve soft-deleted medicines:
    ```php
    public function viewDetails(int $id): void
    {
        $this->selectedMedicineId = $id;
        $this->dispatch('open-modal', 'medicine-detail-modal');
    }
    ```
  - Retrieve the selected medicine using `Medicine::withTrashed()->with([...])->find($this->selectedMedicineId)` in a computed property or load logic so it supports archived medicines.

### 3. View Layout / Design System (`resources/views/livewire/medicines/index.blade.php`)
We will layout the detailed view in an elegant modal `medicine-detail-modal`:
- **Read-Only presentation:** Display information using text labels, badges, and read-only tables or cards. No inputs should be editable.
- **Section 1: General Info & Stock Badge:** Commercial name, Generic name, Concentration, Presentation, Category, Sale Price, Stock actual status (including minimum stock, and warning badges if stock <= min_stock).
- **Section 2: Detailed Specs:** Full name of Laboratory and complete INVIMA Sanitary Registry (Registration number, expiration date, manufacturer).
- **Section 3: Description:** Dedicated card displaying the description text up to 500 characters.
- **Section 4: Barcodes:** Grid or flex list of all barcodes, indicating and highlighting which one is the main barcode.
- **Section 5: Traceability / Audit:**
  - Created by (User name + Date).
  - Updated by (User name + Date) - only if modified.
  - Deleted by (User name + Date) - only if soft-deleted (when `deleted_at` is not null).
- **Action Footer:**
  - "Cerrar" (Close): Closes the modal.
  - "Editar" (Edit): Redirects to `/medicines/{id}/edit` (only render if `$selectedMedicine->deleted_at` is null).
  - "Archivar" (Archive): Triggers deletion confirmation (only render if `$selectedMedicine->deleted_at` is null).

### 4. Tests (`tests/Feature/Pages/Medicines/MedicineManagementTest.php`)
We will add test cases to ensure:
- Detailed view correctly loads all core, detailed, and audit fields (including creator and updater).
- If the medicine is soft-deleted, it loads with the soft delete audit details (deleter name and deletion date).
- Action buttons are dynamically disabled/hidden based on active/archived state.
- Ensure all detail view information is strictly read-only (no inputs).

## Risks & Mitigations
- **N+1 queries on nested relations (sanitary registry manufacturer, deleter):**
  - *Mitigation:* Explicitly load `sanitaryRegistry.manufacturer`, `creator`, `updater`, and `deleter` in the search query and the detail resolution logic.
- **Archived Medicines Retrieval:**
  - *Mitigation:* Use `withTrashed()` when retrieving the selected medicine instance for the modal.
