# 019 · Merchandise Reception & Lot Registration — Tasks

## Database & Models Configuration
- [ ] Verify model relationships in `App\Models\Lot`, `App\Models\Supplier`, `App\Models\PurchaseOrder`, and `App\Models\InventoryMovement`.
- [ ] Add the soft deletes handling to the `Lot` model class and verify auditing fields (`created_by`, `updated_by`, `deleted_by`).

## Business Logic Services
- [ ] Create [`App\Services\SupplierService`](file:///home/one-centavo/Proyectos/tifah/app/Services/SupplierService.php) with `createSupplier` to support rapid supplier addition.
- [ ] Create [`App\Services\LotService`](file:///home/one-centavo/Proyectos/tifah/app/Services/LotService.php) with:
  - `receiveMerchandise(array $lotsData, int $supplierId, string $receptionDate)` executing database transaction, creating a `PurchaseOrder`, saving multiple `Lot` records, and logging `InventoryMovement` entries.
  - `deleteLot(Lot $lot)` to handle logical soft deletes and assign user ID to `deleted_by`.

## Livewire Volt Components (UI/UX)
- [ ] Implement the `inventory.reception` Volt component:
  - [ ] Layout grid for entering a batch.
  - [ ] Real-time barcode input validation and medicine discovery.
  - [ ] Alphanumeric validation for batch number, positive integer for quantity, and decimal validation for unit cost.
  - [ ] Expiry check comparing expiration date with current date.
  - [ ] Dynamic row subtotal calculator.
  - [ ] Profitability check (Warning if unit cost > medicine selling price).
  - [ ] Temporary state array with merge logic for duplicate batch entries.
  - [ ] Temporary table displaying the list with Edit and Delete options.
  - [ ] Quick Supplier registration modal.
  - [ ] Medicine registration link out.
- [ ] Implement the `inventory.lots` Volt component:
  - [ ] Table displaying active lots (batch, medicine, expiration, quantity, status, unit cost, actions).
  - [ ] Deletion action with confirmation triggering soft delete.

## Routing & Navigation
- [ ] Register `/inventory/reception` and `/inventory/lots` routes in [`routes/web.php`](file:///home/one-centavo/Proyectos/tifah/routes/web.php).
- [ ] Update layout sidebar in [`resources/views/layouts/app.blade.php`](file:///home/one-centavo/Proyectos/tifah/resources/views/layouts/app.blade.php) with the new **Inventario** navigation links.

## Automated Testing & Quality Checks
- [ ] Implement Pest feature tests in [`tests/Feature/Pages/Inventory/MerchandiseReceptionTest.php`](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Inventory/MerchandiseReceptionTest.php):
  - [ ] Guest redirect.
  - [ ] Barcode scanning.
  - [ ] Form input validations (expiry, numeric bounds, alphanumeric lots).
  - [ ] Profit warning conditions.
  - [ ] List insertion and merge logic.
  - [ ] Supplier modal registration.
  - [ ] Persistent database transactions.
- [ ] Implement Pest feature tests in [`tests/Feature/Pages/Inventory/LotManagementTest.php`](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Inventory/LotManagementTest.php):
  - [ ] Lot lists rendering.
  - [ ] Auditable logical soft deletes.
- [ ] Run the test suite: `docker compose exec app php artisan test`.
