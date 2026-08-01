# 014 · Edit Medicine & Medicine Catalog Management — Tasks

- [x] Add helper method `hasLotsOrSales()` in `App\Models\Medicine`.
- [x] Implement `update(Medicine $medicine, array $data, array $barcodes)` in `App\Services\MedicineService`.
- [x] Create the Edit Medicine Livewire Volt component at `resources/views/livewire/medicines/edit.blade.php`.
- [x] Bind properties and implement loading logic in `mount(Medicine $medicine)`.
- [x] Implement read-only condition checks for Master Data in the component.
- [x] Implement Barcode list manager UI and logic (add, edit, remove barcodes) in the component.
- [x] Implement unique combination checking validation rules for Master Data.
- [x] Implement barcode uniqueness validation.
- [x] Register edit route `/medicines/{medicine}/edit` in `routes/web.php`.
- [x] Create feature tests for Edit Medicine page at `tests/Feature/Pages/Medicines/EditMedicineTest.php`.
- [x] Run test suite with `docker compose exec app php artisan test` and verify success.
- [x] Format and lint the codebase with Pint and PHPStan.
