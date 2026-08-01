# 018 · Advanced Filters for Medicines — Tasks

- [x] Add state variables (`$laboratoryFilter`, `$coldChainFilter`, `$specialControlFilter`, `$stockAlertFilter`) and pagination hooks to the index component class in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php).
- [x] Retrieve and pass the laboratories collection (`'laboratories' => Laboratory::orderBy('name')->get()`) from the `with()` method in the index component.
- [x] Add query builder filters for Laboratory, Cold Chain, Special Control, and Stock Alert (using SQL subqueries comparing dynamic stock sum to `min_stock`) in the `with()` method of the index component.
- [x] Implement UI input elements in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php) under the filters section:
  - [x] Laboratory select selector.
  - [x] Requiere Cadena de Frío select / toggle.
  - [x] Control Especial select / toggle.
  - [x] Alerta de Inventario select / radio group.
- [x] Ensure that all filters are combinable and cumulative.
- [x] Write Pest feature tests in [MedicineManagementTest.php](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Medicines/MedicineManagementTest.php) to verify:
  - [x] Filtering by Laboratory.
  - [x] Filtering by Requiere Cadena de Frío (Yes / No).
  - [x] Filtering by Control Especial (Yes / No).
  - [x] Filtering by Alerta de Inventario (Bajo Stock / Agotados).
  - [x] Cumulative filter combinations.
  - [x] Displaying the empty state warning when a combination returns no results.
- [x] Run test suite using `docker compose exec app php artisan test` to confirm all tests pass successfully.
- [x] Format code and run code styling checks.
