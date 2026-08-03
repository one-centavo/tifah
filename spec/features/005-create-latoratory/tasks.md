# 005 · Create Laboratory & Laboratory Management — Tasks

- [ ] Create/Update validation request `StoreLaboratoryRequest` under `App\Http\Requests\Laboratory`.
- [ ] Implement `LaboratoryService` under `App\Services` with `create` and `delete` methods.
- [ ] Create the creation Volt component at `resources/views/livewire/laboratories/create.blade.php`.
- [ ] Create the management/list Volt component at `resources/views/livewire/laboratories/index.blade.php`.
- [ ] Add deletion confirmation modal and validation feedback to index view.
- [ ] Register web routes for laboratories in `routes/web.php`.
- [ ] Add navigation link for Laboratories to sidebar in `resources/views/livewire/layout/navigation.blade.php`.
- [ ] Create pest feature test `tests/Feature/Pages/Laboratories/CreateLaboratoryTest.php`.
- [ ] Create pest feature test `tests/Feature/Pages/Laboratories/LaboratoryManagementTest.php`.
- [ ] Run test suite using `docker compose exec app php artisan test` to ensure all tests pass.
- [ ] Run linting/formatting tools.
