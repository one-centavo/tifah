# 003 · Show and Filter Categories — Tasks

- [ ] Add `restore(Category $category)` method to `CategoryService` at [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php).
- [ ] Add properties `$search`, `$coldChain`, `$specialControl`, `$status`, `$sortField`, `$sortDirection` to the Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/categories/index.blade.php).
- [ ] Define the query logic inside the component's `with()` method to handle search, cold chain, special control, status, and sorting filters.
- [ ] Add update lifecycle hooks (`updatingSearch`, `updatingColdChain`, `updatingSpecialControl`, `updatingStatus`) to reset pagination on filter changes.
- [ ] Add `sortBy(string $field)` method to control dynamic table header sorting.
- [ ] Add `restoreCategory(int $id, CategoryService $service)` method to handle category restoration.
- [ ] Implement filter controls in the UI: search field, dropdowns for Cold Chain, Special Control, and Status.
- [ ] Update the table markup to support sortable columns for "Nombre" and "Estado".
- [ ] Update the table row display to include the "Estado" column with badges (Activa / Archivada).
- [ ] Display an empty state message if no categories match the search criteria.
- [ ] Update Pest tests in `tests/Feature/Pages/Categories/CategoryManagementTest.php` to cover searching, filtering, sorting, empty states, and restoration.
- [ ] Run test suite `docker compose exec app php artisan test` to ensure all tests pass.
- [ ] Execute Pint/PHPStan check on changed files.
