# 023 · Provider Registration — Tasks

## Database, Models & Requests Validation
- [ ] Create `StoreSupplierRequest` and `UpdateSupplierRequest` validation request classes under `app/Http/Requests/Supplier/`.
- [ ] Update `SupplierRequestBase` to make `contact_person` optional (`nullable`) and adjust length limits.
- [ ] Add NIT formatting pattern checks (`regex`) and custom Spanish validation message for uniqueness.

## Business Logic (Service Layer)
- [ ] Implement `update(Supplier $supplier, array $data)` method in `App\Services\SupplierService`.
- [ ] Implement `delete(Supplier $supplier)` method in `App\Services\SupplierService` with check for active inventory lots (`Lot` model with `current_quantity > 0` connected through `PurchaseOrder`).
- [ ] Ensure `deleted_by` is recorded on soft delete, and `updated_by` is recorded on updates.

## Livewire Volt Components (Backend & UI)
- [ ] Create the index component `resources/views/livewire/suppliers/index.blade.php` listing providers with search and soft-delete trigger.
- [ ] Create the registration page `resources/views/livewire/suppliers/create.blade.php` with all required inputs.
- [ ] Implement reactive DIAN Modulo 11 DV calculation script/logic in the create/edit forms.
- [ ] Create the edit component `resources/views/livewire/suppliers/edit.blade.php` pre-filling current supplier data.
- [ ] Add highlight visual styles for form validation errors and session flash feedback.

## Routing & Navigation Layout
- [ ] Add `/suppliers`, `/suppliers/create`, and `/suppliers/{supplier}/edit` routes to `routes/web.php` with authentication guards.
- [ ] Add the "Proveedores" (Suppliers) navigation link to the dashboard sidebar layout.

## Testing & Quality Assurance
- [ ] Create feature tests at `tests/Feature/Pages/Suppliers/SupplierManagementTest.php` using Pest to verify:
  - Page access and roles verification.
  - NIT uniqueness, format, and automatic DV validation.
  - Creation, editing audit trail (preserves `created_by`, updates `updated_by`).
  - Soft-delete auditing (`deleted_at`, `deleted_by`).
  - Prevention of soft delete for suppliers with active inventory lots.
- [ ] Execute tests using `docker compose exec app php artisan test` and check code with linting tools.
- [ ] Update `spec/constitution/roadmap.md` to register this feature.
