# 019 · Inventory Management & Lot Reception — Tasks

## Database & Models Configuration
- [x] Verify model relationships in `App\Models\Lot`, `App\Models\Supplier`, `App\Models\PurchaseOrder`, and `App\Models\InventoryMovement`.
- [x] Add the soft deletes handling to the `Lot` model class and verify auditing fields (`created_by`, `updated_by`, `deleted_by`).

## Business Logic Services
- [x] Create [`App\Services\SupplierService`](file:///home/one-centavo/Proyectos/tifah/app/Services/SupplierService.php) with `createSupplier` to support rapid supplier addition.
- [x] Create [`App\Services\LotService`](file:///home/one-centavo/Proyectos/tifah/app/Services/LotService.php) with:
  - [x] `receiveMerchandise(array $lotsData, int $supplierId, string $receptionDate)` executing database transaction, creating a `PurchaseOrder`, saving multiple `Lot` records, and logging `InventoryMovement` entries.
  - [x] `deleteLot(Lot $lot)` to handle logical soft deletes and assign user ID to `deleted_by`.

## Unified Livewire Volt Component (UI/UX)
- [x] Implement the `inventory.index` Volt component in [`resources/views/livewire/inventory/index.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/inventory/index.blade.php):
  - [x] Tab navigation controls ("Control de Lotes" and "Recepción de Mercancía") preserving component states.
  - [x] **Tab 1: Control de Lotes**
    - [x] Dynamic search by medicine name or batch number.
    - [x] Batches list table showing active lots with pagination.
    - [x] Deletion action with confirmation triggering soft delete.
  - [x] **Tab 2: Recepción de Mercancía**
    - [x] Barcode scanning input and real-time medicine discovery.
    - [x] Alphanumeric validation for batch number, positive integer for quantity, and decimal validation for unit cost.
    - [x] Expiry check comparing expiration date with current date.
    - [x] Dynamic row subtotal calculator.
    - [x] Profitability check (Warning if unit cost > medicine selling price).
    - [x] Temporary state array with merge logic for duplicate batch entries.
    - [x] Temporary table displaying the list with Edit and Delete options.
    - [x] Quick Supplier registration modal.
    - [x] Final confirm submission and transaction execution.

## Routing & Navigation
- [x] Register `/inventory` route in [`routes/web.php`](file:///home/one-centavo/Proyectos/tifah/routes/web.php).
- [x] Update layout sidebar in [`resources/views/layouts/app.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/layouts/app.blade.php) with the new **Inventario** navigation link.

## Automated Testing & Quality Checks
- [x] Implement Pest feature tests in [`tests/Feature/Pages/Inventory/InventoryManagementTest.php`](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Inventory/InventoryManagementTest.php) covering:
  - [x] Guest redirect.
  - [x] Tab switching and preservation of temporary state.
  - [x] Search lots by medicine name or batch number.
  - [x] Deleting a lot (logical soft delete, logs creator/deleter, logs adjustment movement).
  - [x] Barcode scanning and medicine discovery.
  - [x] Input validation rules (expiry, bounds, alphanumeric lots).
  - [x] Profit warning conditions.
  - [x] Merging duplicate batch numbers in temporary list.
  - [x] Quick supplier registration via modal.
  - [x] Successful database persistence on merchandise reception.
- [x] Run the test suite: `docker compose exec app php artisan test`.
