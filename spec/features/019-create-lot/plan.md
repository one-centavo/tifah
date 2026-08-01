# 019 · Merchandise Reception & Lot Registration — Plan

## Approach

We will implement the Merchandise Reception and Lot Registration features. The database schemas are already defined in the existing migrations (`create_lots_table`, `create_suppliers_table`, `create_purchase_orders_table`, and `create_inventory_movements_table`). We will create a service layer `LotService` to encapsulate business logic (handling database transactions, registering purchase orders to link lots with suppliers, and logging inventory movements). We will also define `SupplierService` for quick supplier creations. Finally, we will build two Livewire Volt components: `inventory.reception` for entering shipments, and `inventory.lots` for history/deleting lots.

---

### 1. Database & Model Integrations

- **Lot & Supplier Schema Mapping:**
  - Since the `lots` table does not have a direct `supplier_id` column, it belongs to `purchase_orders`, which links directly to `suppliers`.
  - When confirming a merchandise reception, the system must first create a parent `PurchaseOrder` with `status => 'received'` to act as the transaction container, group the lots, and record the supplier relationship.
- **Auditing Traits:**
  - Ensure the `Lot` model uses the `SoftDeletes` trait.
  - Implement dynamic methods or accessors in `Medicine` (e.g. a `current_stock` helper) that sum the `current_quantity` of active lots.

---

### 2. Business Logic Layer

#### `App\Services\SupplierService`
Create a helper service to register suppliers quickly:
- `createSupplier(array $data): Supplier`
  - Creates a `Supplier` record with NIT, DV, Name, Contact Person, Phone, Email, and Address.
  - Automatically logs `created_by => auth()->id()`.

#### `App\Services\LotService`
Create `App\Services\LotService` to orchestrate merchandise entries and deletions:
- `receiveMerchandise(array $lotsData, int $supplierId, string $receptionDate): void`
  - Wraps the operation inside a database transaction (`DB::transaction`).
  - Calculates the total purchase cost: sum of `(quantity * unit_purchase_price)` for all batches.
  - Creates a `PurchaseOrder` record:
    - `supplier_id => $supplierId`
    - `status => 'received'`
    - `received_at => $receptionDate`
    - `total_estimated => $totalCost`
    - `created_by => auth()->id()`
  - For each lot in `$lotsData`:
    - Creates a `Lot` record:
      - `medicine_id => $lot['medicine_id']`
      - `purchase_order_id => $purchaseOrder->id`
      - `batch_number => $lot['batch_number']`
      - `expiration_date => $lot['expiration_date']`
      - `initial_quantity => $lot['quantity']`
      - `current_quantity => $lot['quantity']`
      - `reception_date => $receptionDate`
      - `unit_purchase_price => $lot['unit_purchase_price']`
      - `status => $lot['status'] ?? 'active'`
      - `created_by => auth()->id()`
    - Creates an `InventoryMovement` record:
      - `lot_id => $newLot->id`
      - `type => 'entry'`
      - `quantity => $newLot->initial_quantity`
      - `previous_balance => 0`
      - `new_balance => $newLot->initial_quantity`
      - `concept => 'Merchandise reception - Batch ' . $newLot->batch_number`
      - `reference_id => $purchaseOrder->id`
      - `created_by => auth()->id()`

- `deleteLot(Lot $lot): void`
  - Set `deleted_by => auth()->id()` on the lot.
  - Soft-delete the lot using `$lot->delete()`.
  - Log an `'adjustment'` inventory movement if required to bring stock levels to 0.

---

### 3. UI/UX Livewire Volt Components

#### 3.1. Merchandise Reception (`resources/views/livewire/inventory/reception.blade.php`)
- **State Management:**
  - `$barcode`: String for scanning.
  - `$selectedMedicine`: Model instance or array representing the discovered medicine.
  - `$temporaryLots = []`: Array holding the temporary items added to the screen.
  - `$showSupplierModal = false`: Boolean to display the quick supplier addition form.
  - Form Fields: `$batch_number`, `$expiration_date`, `$quantity`, `$unit_purchase_price`, `$reception_date`, `$supplier_id`, `$status`.
- **Barcode Lookup:**
  - Method `updatedBarcode()` searches the medicine catalog. If found, populate `$selectedMedicine` and focus on lot fields.
  - If not found, display a helper link/button to register the medicine. The medicine creation modal or screen must open in a new tab or handle state without clearing the current reception list.
- **Calculations & Warnings:**
  - Computes `Subtotal = quantity * unit_purchase_price` dynamically.
  - Checks if `unit_purchase_price > selectedMedicine.selling_price` and displays a flash warning.
- **List Actions:**
  - **"Añadir a la lista":** Validate inputs. If `$batch_number` already exists in `$temporaryLots`, add the quantity to the existing row and recalculate subtotals. Otherwise, append to `$temporaryLots`. Clear form fields.
  - **Editar:** Remove row from `$temporaryLots` and load its data back into the form inputs for editing.
  - **Borrar:** Remove row from `$temporaryLots`.
- **Confirm Entry:**
  - Submits `$temporaryLots` to `LotService::receiveMerchandise`, redirects to `/inventory/lots` with a success message.

#### 3.2. Lot Management (`resources/views/livewire/inventory/lots.blade.php`)
- Displays a table listing all active lots: batch number, medicine name, expiration date, supplier, unit cost, status, and current stock.
- Provides a soft-delete action for authorized users, prompting a confirmation modal and invoking `LotService::deleteLot()`.

---

### 4. Routing and Sidenav

- Routes inside the auth middleware group in `routes/web.php`:
  - `Volt::route('inventory/reception', 'inventory.reception')->name('inventory.reception');`
  - `Volt::route('inventory/lots', 'inventory.lots')->name('inventory.lots');`
- Sidenav updates:
  - Add an **Inventario** section in the main sidebar layout with two navigation links to the respective routes.

---

### 5. Test Suite Expansion (PestPHP)

We will create the following test files:
- `tests/Feature/Pages/Inventory/MerchandiseReceptionTest.php`
  - Guest authentication wall.
  - Barcode scanning resolves valid products.
  - Form validation: alphanumeric batches, decimal unit costs, positive integers for quantity.
  - Expiration check: blocks products whose expiration date <= current date.
  - Supplier quick-registration modal inserts correct database records and auto-selects.
  - Adding to temporary list handles merges of identical batches.
  - Warning notification displays if unit cost exceeds selling price.
  - Submission creates a `PurchaseOrder`, inserts `Lot` entries, logs `InventoryMovement`, and tracks authenticated user IDs.
- `tests/Feature/Pages/Inventory/LotManagementTest.php`
  - Lists active batches and correctly computes current stocks.
  - Soft-deleting a lot removes it from lists, updates `deleted_by` and `deleted_at`, and preserves records for historical checks.
