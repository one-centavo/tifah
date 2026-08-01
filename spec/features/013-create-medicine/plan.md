# 013 · Create Medicine & Medicine Catalog Management — Plan

## Approach

We will implement the Medicine Registration and Catalog Management feature. The database schemas are already defined in the existing migrations (`create_medicines_table` and `create_medicine_barcodes_table`). We will create the service layer `MedicineService` to encapsulate business logic, implement accessors in models, design the Volt Livewire components with the necessary modals and validation logic, and create feature tests using PestPHP.

---

### 1. Model Updates (`App\Models\Medicine`)

- **Relationships**: Verify and ensure relations for `category`, `laboratory`, `sanitaryRegistry`, `concentrationUnit`, `container`, `contentUnit`, and `barcodes` are active (already present).
- **Presentation Accessor**:
  - Implement a new `presentation` attribute using the modern Laravel custom attribute casting style:
    ```php
    use Illuminate\Database\Eloquent\Casts\Attribute;

    protected function presentation(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->container->name} x {$this->content_quantity} {$this->content_unit->name}"
        );
    }
    ```
- **Audit Columns**: Ensure standard `created_by`, `updated_by`, and `deleted_by` are set.

---

### 2. Business Logic Layer (`App\Services\MedicineService`)

Create `App\Services\MedicineService` with the following methods:

- `findDuplicate(array $data): ?Medicine`
  - Searches for an existing active (non-soft-deleted) medicine matching:
    - `name` (Commercial Name)
    - `generic_name` (optional)
    - `concentration_value`
    - `concentration_unit_id`
    - `container_id`
    - `content_quantity`
    - `content_unit_id`
    - `laboratory_id`
  - Returns the matched `Medicine` instance, or `null` if none.

- `create(array $data): Medicine`
  - If barcode is empty or not provided, auto-generate a unique internal barcode (e.g. `999` prefix followed by unique digits, or a timestamp-hash based 12-digit numeric code).
  - Create the `Medicine` record with `$data`, automatically setting `created_by` to `auth()->id()`.
  - Create the main barcode record in the `medicine_barcodes` table with `is_main => true`, referencing the newly created medicine ID and setting `created_by => auth()->id()`.

- `linkBarcode(Medicine $medicine, string $barcode): MedicineBarcode`
  - Verify that the barcode is unique in the system.
  - Create a new barcode record in the `medicine_barcodes` table with `is_main => false`, referencing `$medicine->id` and setting `created_by => auth()->id()`.

- `delete(Medicine $medicine): void`
  - Set `deleted_by` to the authenticated user ID and save.
  - Soft-delete the medicine: `$medicine->delete()`.
  - Soft-delete all associated barcode records, recording `deleted_by` on each.

---

### 3. Validation & Requests

Define validation rules for creating medicines (e.g., in a request helper or within the component):
- `barcode`: If entered, must be numeric, between 8 and 14 digits, and unique in `medicine_barcodes.barcode`.
- `name`: Required, max 100 characters.
- `generic_name`: Optional, max 255 characters.
- `category_id`: Required, exists in `categories,id`.
- `concentration_value`: Required, positive numeric value.
- `concentration_unit_id`: Required, exists in `concentration_units,id`.
- `container_id`: Required, exists in `containers,id`.
- `content_quantity`: Required, integer greater than zero.
- `content_unit_id`: Required, exists in `content_units,id`.
- `laboratory_id`: Required, exists in `laboratories,id`.
- `sanitary_registry_id`: Required, exists in `sanitary_registries,id`.
- `is_cold_chain`, `is_special_control`: Boolean.
- `min_stock`: Required, integer greater than or equal to zero.
- `selling_price`: Required, numeric positive.
- `description`: Optional, max 500 characters.

---

### 4. UI/UX Livewire Volt Components

#### Creation Page (`resources/views/livewire/medicines/create.blade.php`)
- **Fields & Layout**:
  - Grid-based design using Tailwind CSS.
  - Inputs with error feedback displayed right next to them using `<x-input-error :messages="$errors->get('field')" />`.
- **Auto-generate Barcode**:
  - Button to fill the barcode field with a unique, auto-generated numeric code.
- **Interactive Selectors**:
  - Search-and-select dropdown for Laboratory and Sanitary Registry.
  - When a Category is selected, trigger category data fetch and auto-populate `is_cold_chain` and `is_special_control` checkboxes based on Category defaults. Allow manual override.
- **Quick Registration Modals**:
  - **Quick Laboratory Modal**: Standard modal to quickly add a laboratory on the fly.
  - **Quick Sanitary Registry Modal**: Standard modal to quickly add a sanitary registry on the fly.
- **Duplicate Combination Modal**:
  - If a duplicate combination is detected, block the submission and open the warning modal:
    - Display matching medicine details.
    - Button: "Vincular Código de Barras" (calls `linkBarcode` and redirects to index).
    - Button: "Cancelar" (closes the modal to correct inputs).

#### Catalog List/Management Page (`resources/views/livewire/medicines/index.blade.php`)
- Display table listing medicines with columns: Barcodes, Commercial Name, Generic Name, Presentation (combined string), Category, Laboratory, Sanitary Registry, Min Stock, Stock (if tracked), Sale Price, Cold Chain/Special Control indicators.
- Filter and search bar.
- "Eliminar" button triggering a confirmation modal.
- Soft-delete medicine inside `deleteMedicine(int $id)` method.

---

### 5. Routing and Layout

- Add routes in `routes/web.php` inside the auth middleware group:
  - `Volt::route('medicines', 'medicines.index')->name('medicines.index');`
  - `Volt::route('medicines/create', 'medicines.create')->name('medicines.create');`
- Update the layout navigation in [navigation.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/layout/navigation.blade.php) to change the "Medicamentos" sidebar href from `#` to `route('medicines.index')` with `wire:navigate`.

---

### 6. Automated Tests (PestPHP)

- **Medicine Creation Page Tests** (`tests/Feature/Pages/Medicines/CreateMedicineTest.php`):
  - Guest users are redirected to login.
  - Authorized users can access the creation page.
  - Validate all required fields and correct formats (e.g. barcode length and numeric constraint).
  - Test auto-generating barcode works.
  - Test quick laboratory registration modal works.
  - Test quick sanitary registry registration modal works.
  - Test category selection updates cold chain and special control flags, and manual overrides work.
  - Test duplicate checking blocks registration and allows barcode linking to existing product.
  - Test successful creation redirects to index and displays success flash message.

- **Medicine Catalog Management & Deletion Tests** (`tests/Feature/Pages/Medicines/MedicineManagementTest.php`):
  - Authorized users can see medicines list.
  - Test search by name/barcode.
  - Test soft-deleting a medicine records `deleted_by` and `deleted_at`.
