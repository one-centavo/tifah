# 012 · Sanitary Registry Soft Deletion — Tasks

- [x] Add active medicines check in `SanitaryRegistryService@delete` in [SanitaryRegistryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/SanitaryRegistryService.php).
- [x] Throw standard `ValidationException` when active medicines are linked.
- [x] Add confirmation modal state variables and methods in `resources/views/livewire/sanitary-registries/index.blade.php`.
- [x] Integrate deletion confirmation modal markup into the list view template.
- [x] Connect confirmation modal action button to call `deleteRegistry`.
- [x] Add try-catch block in `deleteRegistry` to handle validation errors and display a clear error message in the UI.
- [x] Display flash success message after successful deletion.
- [x] Implement feature tests verifying:
  - Deletion fails with active medicines and shows error.
  - Deletion succeeds without active medicines, setting `deleted_by` and `deleted_at`.
  - Unauthorized users (guests) cannot delete registries.
- [x] Run test suite `docker compose exec app php artisan test` to ensure all tests pass.
- [x] Run code style formatters.
