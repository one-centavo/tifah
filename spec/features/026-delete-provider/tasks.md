# 026 · Delete Provider — Tasks

## Database, Model & Service logic
- [x] Configure `Supplier` model to use `SoftDeletes` trait.
- [x] Include `deleted_by` in `Supplier` model `$fillable` array.
- [x] Create relationships in `Supplier` model for `deleter` mapping to `User`.
- [x] Implement the `delete(Supplier $supplier)` method in `SupplierService`.
- [x] Implement database constraint checks in `SupplierService->delete()` to verify that no active lots exist associated with the supplier's purchase orders.
- [x] Throw a `ValidationException` with an appropriate message in Spanish if active lots are found.

## Livewire Integration & UI
- [x] Add an "Archivar" button to each provider row in the table inside `resources/views/livewire/suppliers/index.blade.php`.
- [x] Implement `confirmSupplierDeletion(int $id)` in `suppliers.index` to capture target ID/name, reset error bags, and trigger modal display.
- [x] Implement `deleteSupplier(SupplierService $supplierService)` in `suppliers.index` component to handle deletion, catch validation exceptions, close modal, and flash success session message.
- [x] Create the confirmation modal (`confirm-supplier-deletion`) inside `suppliers.index` listing the provider name and displaying `deletion_error` messages inline if they occur.
- [x] Style status column in the table list to display a gray badge "Archivado" for soft-deleted suppliers and a green badge "Activo" for active suppliers.
- [x] Support status filtering (Active, Archived, All) inside the table query logic.

## Verification & Tests
- [x] Add Pest test to verify guest users are redirected from the pages.
- [x] Add Pest test to verify successful soft delete and auditing columns logging.
- [x] Add Pest test to verify validation blocking deletion when active lots exist.
- [x] Add Pest test to verify that the NIT of a soft-deleted supplier can be reused during registration or update.
- [x] Register this feature in `spec/constitution/roadmap.md`.
