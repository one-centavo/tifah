# 020 · Inventory Dashboard — Tasks

## Database & Models Configuration
- [ ] Ensure that `Medicine` model has appropriate relationships to `Lot` and `MedicineBarcode` models.
- [ ] Verify that `Lot` query scope only retrieves active, non-deleted records for calculations.

## Business Logic & Queries
- [ ] Create/optimize query logic (e.g., in a service or model scope) to fetch medicines with `total_stock` and `active_lots_count` efficiently without N+1 queries.

## Livewire Volt Components (UI/UX)
- [ ] Create the `inventory.dashboard` component in `resources/views/livewire/inventory/dashboard.blade.php`:
  - [ ] Implement search bar for name/barcode.
  - [ ] Render consolidated table without prices (Commercial Name, Generic Name, Concentration, Total Stock, Active Lots).
  - [ ] Implement pagination.
  - [ ] Add "Ver Detalle" button with redirect.
- [ ] Create the Level 2 `inventory.medicine-lots` component in `resources/views/livewire/inventory/medicine-lots.blade.php`:
  - [ ] Render all batches/lots specifically for the selected medicine.

## Routing & Navigation
- [ ] Register new routes in `routes/web.php`:
  - [ ] `/inventory-dashboard` (`inventory.dashboard`)
  - [ ] `/inventory/medicines/{medicine}/lots` (`inventory.medicine-lots`)
- [ ] Update layout sidebar in `routes/web.php` or main layout component to include a link to the new dashboard.

## Automated Testing & Quality Checks
- [ ] Implement Pest feature tests in `tests/Feature/Pages/Inventory/InventoryDashboardTest.php`:
  - [ ] Guest redirect.
  - [ ] Access permission for Warehouse Assistant.
  - [ ] Search medicines by name/barcode.
  - [ ] Correct stock calculation (excluding soft-deleted or blocked/inactive lots).
  - [ ] Correct active lot count.
  - [ ] Assert sale prices are not rendered.
  - [ ] Verify redirection to the Level 2 lot list page.
- [ ] Run the test suite: `docker compose exec app php artisan test`.
