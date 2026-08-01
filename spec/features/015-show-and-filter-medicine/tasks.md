# 015 · Show and Filter Medicine — Tasks

- [x] Add `totalStock` accessor/helper in `App\Models\Medicine`.
- [x] Add `hasActiveLots()` method in `App\Models\Medicine` to check for lots with `status = 'active'` and `current_quantity > 0`.
- [x] Update `delete(Medicine $medicine)` in `App\Services\MedicineService` to validate against active lots.
- [x] Eager load `lots` in the query of `index.blade.php` component.
- [x] Add the **Stock Actual / Alerta** column to the medicines table in `index.blade.php`.
- [x] Implement comparison logic for Stock Actual vs. Minimum Stock to display warning badges.
- [x] Add the **Ver Detalle** action button for each row in the table.
- [x] Add detail modal state (`$detailMedicineId` or `$selectedMedicine`) and `viewDetails(int $id)` method to the component.
- [x] Create the `<x-modal name="medicine-detail-modal">` section in `index.blade.php` to show all technical details of the selected medicine.
- [x] Write feature tests for the new columns, stock alert, view detail modal, and active lot deletion blocker in `MedicineManagementTest.php`.
- [x] Run tests using `docker compose exec app php artisan test` and verify that all test cases pass.
- [x] Run PHP Pint and PHPStan to format and lint code.
