# 008 · Laboratory Soft Deletion — Tasks

- [x] Implement soft-delete logic and associated active medicines validation check in `LaboratoryService@delete` in [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php).
- [x] Implement restoration logic in `LaboratoryService@restore` in [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php).
- [x] Add confirmation modal state variables and methods (`confirmLaboratoryDeletion`, `deleteLaboratory`) to the laboratories index Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php).
- [x] Add confirmation modal HTML markup using Breeze components in the laboratories index view in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php).
- [x] Display validation errors (`deletion_error`) in the confirmation modal and flash standard success messages.
- [x] Implement status badges (Activo/Archivado) in the table columns and conditional action buttons based on whether the laboratory is soft-deleted or not.
- [x] Write Pest feature tests in [LaboratoryManagementTest.php](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Laboratories/LaboratoryManagementTest.php) to verify:
  - Deletion fails with active medicines and shows a validation error.
  - Deletion succeeds without active medicines, setting `deleted_by` and `deleted_at`.
  - Restoration of soft-deleted laboratories.
  - Unauthorized access redirection.
- [x] Run test suite to verify that all tests pass.
- [x] Verify code formatting and linting.
