# 007 · Show and Filter Laboratories — Tasks

- [x] Implement soft-delete validation and user tracking in `LaboratoryService` at [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php).
- [x] Add restore method `restore(Laboratory $laboratory)` in `LaboratoryService` at [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php).
- [x] Add state variables `$search`, `$status`, `$sortField`, `$sortDirection` to the Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php).
- [x] Implement filter controls in UI: search field (partial name matching) and status dropdown (Activos / Archivados / Todos) in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php).
- [x] Add update lifecycle hooks (`updatingSearch`, `updatingStatus`) to reset pagination when filters are updated.
- [x] Implement sorting functionality via `sortBy(string $field)` method in Volt component.
- [x] Design sortable columns in the UI for "Nombre" and "Estado" headers, rendering arrow indicators.
- [x] Add status badges (Activo/Archivado) in the table rows based on soft-deleted state.
- [x] Implement "Restaurar" action button for archived/soft-deleted laboratories.
- [x] Implement deletion validation and modal confirmation for active laboratories.
- [x] Handle empty state display showing `"No se encontraron laboratorios que coincidan con los filtros aplicados"` when filters yield no results.
- [x] Create and run Pest feature tests in `tests/Feature/Pages/Laboratories/LaboratoryManagementTest.php` to ensure all functionality is fully tested and verified.
- [x] Execute Pint/PHPStan check on changed files.
