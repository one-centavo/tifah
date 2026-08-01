# 014 · Edit Medicine & Medicine Catalog Management — Tasks

- [ ] Add helper method `hasLotsOrSales()` in `App\Models\Medicine`.
- [ ] Implement `update(Medicine $medicine, array $data, array $barcodes)` in `App\Services\MedicineService`.
- [ ] Create the Edit Medicine Livewire Volt component at `resources/views/livewire/medicines/edit.blade.php`.
- [ ] Bind properties and implement loading logic in `mount(Medicine $medicine)`.
- [ ] Implement read-only condition checks for Master Data in the component.
- [ ] Implement Barcode list manager UI and logic (add, edit, remove barcodes) in the component.
- [ ] Implement unique combination checking validation rules for Master Data.
- [ ] Implement barcode uniqueness validation.
- [ ] Register edit route `/medicines/{medicine}/edit` in `routes/web.php`.
- [ ] Create feature tests for Edit Medicine page at `tests/Feature/Pages/Medicines/EditMedicineTest.php`.
- [ ] Run test suite with `docker compose exec app php artisan test` and verify success.
- [ ] Format and lint the codebase with Pint and PHPStan.
