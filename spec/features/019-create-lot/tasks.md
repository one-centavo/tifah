# 019 · Inventory Management & Lot Reception — Tasks

## Database & Models Configuration
- [x] Verify model relationships in `App\Models\Lot`, `App\Models\Supplier`, `App\Models\PurchaseOrder`, and `App\Models\InventoryMovement`.
- [x] Add the soft deletes handling to the `Lot` model class and verify auditing fields (`created_by`, `updated_by`, `deleted_by`).

## Business Logic Services
- [x] Create [`App\Services\SupplierService`](file:///home/one-centavo/Proyectos/tifah/app/Services/SupplierService.php) with `createSupplier` to support rapid supplier addition.
- [x] Create [`App\Services\LotService`](file:///home/one-centavo/Proyectos/tifah/app/Services/LotService.php) with:
  - `receiveMerchandise(array $lotsData, int $supplierId, string $receptionDate)` executing database transaction, creating a `PurchaseOrder`, saving multiple `Lot` records, and logging `InventoryMovement` entries.
  - `deleteLot(Lot $lot)` to handle logical soft deletes and assign user ID to `deleted_by`.

## Unified Livewire Volt Component (UI/UX)
- [ ] Implement the `inventory.index` Volt component in [`resources/views/livewire/inventory/index.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/inventory/index.blade.php):
  - [ ] Tab navigation controls ("Control de Lotes" and "Recepción de Mercancía") preserving component states.
  - [ ] **Tab 1: Control de Lotes**
    - [ ] Dynamic search by medicine name or batch number.
    - [ ] Batches list table showing active lots with pagination.
    - [ ] Deletion action with confirmation triggering soft delete.
  - [ ] **Tab 2: Recepción de Mercancía**
    - [ ] Barcode scanning input and real-time medicine discovery.
    - [ ] Alphanumeric validation for batch number, positive integer for quantity, and decimal validation for unit cost.
    - [ ] Expiry check comparing expiration date with current date.
    - [ ] Dynamic row subtotal calculator.
    - [ ] Profitability check (Warning if unit cost > medicine selling price).
    - [ ] Temporary state array with merge logic for duplicate batch entries.
    - [ ] Temporary table displaying the list with Edit and Delete options.
    - [ ] Quick Supplier registration modal.
    - [ ] Final confirm submission and transaction execution.

## Routing & Navigation
- [ ] Register `/inventory` route in [`routes/web.php`](file:///home/one-centavo/Proyectos/tifah/routes/web.php).
- [ ] Update layout sidebar in [`resources/views/layouts/app.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/layouts/app.blade.php) with the new **Inventario** navigation link.

## Automated Testing & Quality Checks
- [ ] Implement Pest feature tests in [`tests/Feature/Pages/Inventory/InventoryManagementTest.php`](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Inventory/InventoryManagementTest.php) covering:
  - [ ] Guest redirect.
  - [ ] Tab switching and preservation of temporary state.
  - [ ] Search lots by medicine name or batch number.
  - [ ] Deleting a lot (logical soft delete, logs creator/deleter, logs adjustment movement).
  - [ ] Barcode scanning and medicine discovery.
  - [ ] Input validation rules (expiry, bounds, alphanumeric lots).
  - [ ] Profit warning conditions.
  - [ ] Merging duplicate batch numbers in temporary list.
  - [ ] Quick supplier registration via modal.
  - [ ] Successful database persistence on merchandise reception.
- [ ] Run the test suite: `docker compose exec app php artisan test`.
