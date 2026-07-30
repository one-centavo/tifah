# 011 · Show and Filter Sanitary Registries — Tasks

- [x] Add public filter properties (`$laboratoryFilter`, `$expirationStart`, `$expirationEnd`) in the Volt component at `resources/views/livewire/sanitary-registries/index.blade.php`.
- [x] Implement pagination reset hooks for all new filters in `resources/views/livewire/sanitary-registries/index.blade.php`.
- [x] Update query building in the component's `with()` method to filter by laboratory and expiration date range.
- [x] Pass the list of laboratories (`Laboratory::orderBy('name')->get()`) from `with()` to the view.
- [x] Add the Laboratory select filter to the form fields in `resources/views/livewire/sanitary-registries/index.blade.php`.
- [x] Add the Expiration Date Range (Start & End) inputs in `resources/views/livewire/sanitary-registries/index.blade.php`.
- [x] Show catalog visibility as "Sí" (for active records) or "No" (for archived/soft-deleted records) in the table columns.
- [x] Update the empty search results block to display the exact Spanish text: `"No se encontraron registros sanitarios que coincidan con los filtros aplicados"`.
- [x] Add and update tests in `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php` to verify:
  - Filtering by laboratory.
  - Filtering by expiration date range.
  - Filtering by status.
  - Filtering by catalog visibility.
  - Verification of empty state message.
  - Valid action buttons (Edit link and Delete logical action).
- [x] Run PHP tests (`php artisan test`) and verify they all pass.
- [x] Run Laravel Pint to check and fix code style.
