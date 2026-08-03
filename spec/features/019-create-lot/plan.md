# 019 · Inventory Management & Lot Reception — Plan

## Approach

We will implement a single, unified Inventory dashboard under a tabbed Livewire Volt component (`resources/views/livewire/inventory/index.blade.php`) representing the route `/inventory`. The page will combine lot tracking/management ("Control de Lotes" tab) and merchandise entry ("Recepción de Mercancía" tab) in one place. We will utilize the previously created `LotService` and `SupplierService` for business orchestration and database transactions.

---

### 1. Unified Volt Component (`App\Livewire\Inventory\Index`)

The single component `inventory.index` will manage the tab selections, search, lot lists, temporary forms, modals, and final persistence.

- **State Properties:**
  - `$activeTab = 'lots';` (Options: `'lots'` for control table, `'reception'` for shipment entries).
  - **Reception Form State:**
    - `$barcode`: string
    - `$selectedMedicine`: model instance or array
    - `$temporaryLots = []`: array of uncommitted batches
    - `$showSupplierModal = false`: boolean for supplier creation modal
    - Inputs: `$batch_number`, `$expiration_date`, `$quantity`, `$unit_purchase_price`, `$reception_date`, `$supplier_id`, `$status`.
  - **Lots Control State:**
    - `$searchTerm = '';`
    - Pagination is supported. Reset page hooks will trigger when searching or switching tabs.

- **Component Methods:**
  - `switchTab(string $tab)`: Toggles between `'lots'` and `'reception'`, keeping the `$temporaryLots` list and inputs preserved in state.
  - `updatedBarcode()`: Triggers barcode lookup. Searches `MedicineBarcode` and updates `$selectedMedicine` if found, setting the focus to batch inputs.
  - `addToTemporaryList()`: Validates inputs. If `$batch_number` already exists for this medicine in `$temporaryLots`, it increments its quantity and recalculates row totals. Else, pushes new item. Resets batch input fields (keeping `$supplier_id` and `$reception_date` as convenient defaults).
  - `editTemporaryLot(int $index)`: Removes the lot from `$temporaryLots` and populates the input fields with its values for correction.
  - `removeTemporaryLot(int $index)`: Removes the lot from `$temporaryLots` entirely.
  - `confirmReception()`: Calls `LotService::receiveMerchandise()` inside transaction, clears the temporary list/fields, switches back to `'lots'` tab, and displays a success alert.
  - `deleteLot(int $id)`: Triggers soft-delete inside confirmation, calls `LotService::deleteLot()`, and refreshes the lots table.

---

### 2. UI Layout Architecture

The single view `resources/views/livewire/inventory/index.blade.php` will be styled with Tailwind CSS:
- **Header:** Title "Gestión de Inventario y Lotes".
- **Tab Nav Bar:** Two tab items ("Control de Lotes" and "Recepción de Mercancía") with visual active state highlights.
- **Tab 1 Content (Control de Lotes):**
  - Search bar.
  - Table showing current lots: Medicine Name, Batch Number, Expiration Date, Stock Quantity, Purchase Unit Cost, Supplier Name, Status Badge (`active`, `blocked`, `damaged`), Actions (Soft-delete).
  - Standard empty-state warning: `"No se encontraron lotes registrados."` if empty.
- **Tab 2 Content (Recepción de Mercancía):**
  - **Form Grid:** Inputs for Barcode, Product Name (read-only), Batch (Lot) Number, Expiration Date, Quantity, Unit Cost, Reception Date, Supplier Dropdown, Status Dropdown.
  - Subtotal calculator displaying total cost in real-time.
  - Margins warning badge when unit cost > selling price.
  - **Quick Add Supplier Modal:** Compact inline popup form.
  - **Temporary Table:** Summary list of products currently added to the shipment.
  - **Final Action Buttons:** "Confirmar Ingreso" and "Cancelar".

---

### 3. Routing & Sidebar Navigation

- Add a single route in `routes/web.php` inside the auth middleware group:
  - `Volt::route('inventory', 'inventory.index')->name('inventory.index');`
- Sidenav Update:
  - Add a single "Inventario" link in [`resources/views/layouts/app.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/layouts/app.blade.php) pointing to `route('inventory.index')`.

---

### 4. PestPHP Testing Strategy

We will write features tests in `tests/Feature/Pages/Inventory/InventoryManagementTest.php`:
- **General Page Behavior:** Guest redirects, tabs switching preserves states.
- **Control Tab:** Lists active batches, dynamic search, soft deleting a lot works (checks `deleted_by`, changes quantity to 0, logs adjustment inventory movement).
- **Reception Tab:**
  - Discovering medicine via barcode works.
  - Validations (alphanumeric lot numbers, positive integers, decimal unit costs).
  - Expiration block (blocks dates <= today).
  - Warning notification displays if cost exceeds selling price.
  - Merging duplicate batch numbers inside temporary list.
  - Adding new suppliers via inline modal.
  - Final confirmation persists `PurchaseOrder`, `Lot` and `InventoryMovement` structures inside a single transaction.
