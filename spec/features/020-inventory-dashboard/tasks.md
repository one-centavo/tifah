# 020 · Inventory — Tasks

## Database & Models Configuration
- [x] Ensure that `Medicine` model has appropriate relationships to `Lot` and `MedicineBarcode` models.
- [x] Verify that `Lot` query scope only retrieves active, non-deleted records for calculations.

## Business Logic & Queries
- [x] Create/optimize query logic to fetch medicines with `total_stock` and `active_lots_count` efficiently without N+1 queries.

## Livewire Volt Components (UI/UX)
- [x] Update the `inventory.index` component in `resources/views/livewire/inventory/index.blade.php`:
  - [x] Set default tab to `'consolidated'` ("Inventario").
  - [x] Implement search bar for name/barcode.
  - [x] Render consolidated table without prices (Commercial Name, Generic Name, Concentration, Total Stock, Active Lots).
  - [x] Implement pagination.
  - [x] Add "Ver Detalle" button redirecting to Level 2.
- [x] Create the Level 2 `inventory.medicine-lots` component in `resources/views/livewire/inventory/medicine-lots.blade.php`:
  - [x] Render all batches/lots specifically for the selected medicine.
  - [x] Move lot deletion modal and action logic to this page.

## Routing & Navigation
- [x] Register new Level 2 route in `routes/web.php`:
  - [x] `/inventory/medicines/{medicine}/lots` (`inventory.medicine-lots`)
- [x] Ensure Sidenav "Inventario" link points to `/inventory` (`inventory.index`).

## Automated Testing & Quality Checks
- [x] Implement and update Pest feature tests in `tests/Feature/Pages/Inventory/InventoryManagementTest.php`:
  - [x] Guest redirect & access permissions.
  - [x] Search medicines by name/barcode.
  - [x] Correct stock calculations and active lot counts.
  - [x] Assert sale prices are not rendered.
  - [x] Redirection to Level 2 lot list page.
  - [x] Soft-delete lot from Level 2 lots page.
- [x] Run the test suite: `docker compose exec app php artisan test`.
