# 017 · Medicine Soft Deletion — Tasks

- [x] Add validation check in `Medicine@hasActiveLots` to check for active lots with stock.
- [x] Implement soft-delete logic in `MedicineService@delete` in [MedicineService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/MedicineService.php) to validate active lots, set `deleted_by` on both the medicine and its barcodes, and trigger soft deletion in a transaction.
- [x] Define confirmation modal state variables (`$medicineIdBeingDeleted`, `$medicineNameBeingDeleted`) in the medicines list component [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php).
- [x] Implement the `confirmMedicineDeletion` and `deleteMedicine` actions in the medicines list component controller logic.
- [x] Implement the confirmation modal HTML markup using `<x-modal name="confirm-medicine-deletion">` and Breeze components in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/medicines/index.blade.php).
- [x] Condition the "Archivar" action button in the list and in the detailed view modal to only be visible if the medicine is not soft-deleted.
- [x] Implement visibility filtering logic (`$softDeleteFilter`) in the search query to show active, archived, or all medicines.
- [x] Render soft delete audit metadata (deleted by, deleted at) in the detailed view modal when the selected medicine is soft-deleted.
- [x] Write Pest feature tests in [MedicineManagementTest.php](file:///home/one-centavo/Proyectos/tifah/tests/Feature/Pages/Medicines/MedicineManagementTest.php) to verify:
  - [x] Deletion fails and displays a validation error if the medicine has active lots.
  - [x] Deletion succeeds if there are no active stock lots, setting `deleted_by` and `deleted_at` on the medicine and barcodes.
  - [x] Eager loading and displaying deletion audit details (deleter name, timestamp) in the detailed view.
- [x] Run test suite using `docker compose exec app php artisan test` to confirm everything passes.
- [x] Format code and run code styling checks.
