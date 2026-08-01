# 018 · Advanced Filters for Medicines — Tasks

- [ ] Add state variables (`$laboratoryFilter`, `$coldChainFilter`, `$specialControlFilter`, `$stockAlertFilter`) and pagination hooks to the index component class in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php).
- [ ] Retrieve and pass the laboratories collection (`'laboratories' => Laboratory::orderBy('name')->get()`) from the `with()` method in the index component.
- [ ] Add query builder filters for Laboratory, Cold Chain, Special Control, and Stock Alert (using SQL subqueries comparing dynamic stock sum to `min_stock`) in the `with()` method of the index component.
- [ ] Implement UI input elements in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php) under the filters section:
  - [ ] Laboratory select selector.
  - [ ] Requiere Cadena de Frío select / toggle.
  - [ ] Control Especial select / toggle.
  - [ ] Alerta de Inventario select / radio group.
- [ ] Ensure that all filters are combinable and cumulative.
- [ ] Write Pest feature tests in [MedicineManagementTest.php](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Medicines/MedicineManagementTest.php) to verify:
  - [ ] Filtering by Laboratory.
  - [ ] Filtering by Requiere Cadena de Frío (Yes / No).
  - [ ] Filtering by Control Especial (Yes / No).
  - [ ] Filtering by Alerta de Inventario (Bajo Stock / Agotados).
  - [ ] Cumulative filter combinations.
  - [ ] Displaying the empty state warning when a combination returns no results.
- [ ] Run test suite using `docker compose exec app php artisan test` to confirm all tests pass successfully.
- [ ] Format code and run code styling checks.
