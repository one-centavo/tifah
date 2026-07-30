# 008 · Create Sanitary Register & Sanitary Register Management — Tasks

- [ ] Create/Update validation request `StoreSanitaryRegistryRequest` under `App\Http\Requests\SanitaryRegistry`.
- [ ] Create/Update `SanitaryRegistryService` under `App\Services` with `create` and `delete` methods.
- [ ] Create the creation Volt component at `resources/views/livewire/sanitary-registries/create.blade.php`.
- [ ] Create the management/list Volt component at `resources/views/livewire/sanitary-registries/index.blade.php`.
- [ ] Update medicine registration forms (`resources/views/livewire/medicines/create.blade.php`, `edit.blade.php`) to filter active/valid registers and show warnings for expired ones.
- [ ] Add deletion confirmation modal and validation feedback to index view.
- [ ] Register web routes for sanitary registries in `routes/web.php`.
- [ ] Add navigation link for Sanitary Registries to sidebar in `resources/views/livewire/layout/navigation.blade.php`.
- [ ] Create pest feature test `tests/Feature/Pages/SanitaryRegistries/CreateSanitaryRegistryTest.php`.
- [ ] Create pest feature test `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php`.
- [ ] Run test suite using `docker compose exec app php artisan test` to ensure all tests pass.
- [ ] Run Pint/PHPStan check on changed files.
