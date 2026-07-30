# 004 · Category Soft Deletion — Tasks

- [ ] Add check for active associated medicines in `CategoryService@delete` in [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php).
- [ ] Throw standard `ValidationException` or handle custom error in `CategoryService@delete` when active medicines exist.
- [ ] Add confirmation modal state variables and methods to the Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/categories/index.blade.php).
- [ ] Add confirmation modal HTML markup and triggers to the categories index view.
- [ ] Connect the delete confirmation button to trigger `deleteCategory` in the Volt component.
- [ ] Add try-catch block in `deleteCategory` to handle validation errors and display a clear error message in the UI.
- [ ] Add a success message alert/flash to the UI upon successful deletion.
- [ ] Write/update Pest tests in `tests/Feature/Pages/Categories/CategoryManagementTest.php` to verify:
  - Deletion fails with active medicines and shows validation error.
  - Deletion succeeds without active medicines, setting `deleted_by` and `deleted_at`.
  - Unauthorized users (guests) cannot delete categories.
- [ ] Run test suite `docker compose exec app php artisan test` to ensure all tests pass.
- [ ] Run code formatters and static analysis.
