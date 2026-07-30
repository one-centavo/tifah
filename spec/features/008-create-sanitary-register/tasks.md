# 008 · Create Sanitary Register & Sanitary Register Management — Tasks

- [x] Create/Update validation request `StoreSanitaryRegistryRequest` under `App\Http\Requests\SanitaryRegistry`.
- [x] Create/Update `SanitaryRegistryService` under `App\Services` with `create` and `delete` methods.
- [x] Create the creation Volt component at `resources/views/livewire/sanitary-registries/create.blade.php`.
- [x] Create the management/list Volt component at `resources/views/livewire/sanitary-registries/index.blade.php`.
- [ ] Update medicine registration forms (`resources/views/livewire/medicines/create.blade.php`, `edit.blade.php`) to filter active/valid registers and show warnings for expired ones.
- [x] Add deletion confirmation modal and validation feedback to index view.
- [x] Register web routes for sanitary registries in `routes/web.php`.
- [x] Add navigation link for Sanitary Registries to sidebar in `resources/views/livewire/layout/navigation.blade.php`.
- [ ] Implement Quick Laboratory Registration modal and logic in `create.blade.php`.
- [ ] Implement Quick Laboratory Registration modal and logic in `edit.blade.php`.
- [x] Create pest feature test `tests/Feature/Pages/SanitaryRegistries/CreateSanitaryRegistryTest.php`.
- [x] Create pest feature test `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php`.
- [ ] Add tests for Quick Laboratory Registration in creation and editing tests.
- [x] Run test suite using `docker compose exec app php artisan test` to ensure all tests pass.
- [x] Run Pint/PHPStan check on changed files.
