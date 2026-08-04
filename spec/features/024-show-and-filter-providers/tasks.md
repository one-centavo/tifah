# 024 · Show and Filter Providers — Tasks

## Database, Models & Services
- [x] Configure standard soft deletes on the `Supplier` model.
- [x] Integrate `created_by`, `updated_by`, and `deleted_by` properties in `Supplier` model.
- [x] Implement the `delete(Supplier $supplier)` method in `App\Services\SupplierService`.
- [x] Perform validations in service layer to prevent soft deleting suppliers that have active merchandise lots in stock.

## Livewire Volt Component & Logic
- [x] Design index Livewire Volt component at `resources/views/livewire/suppliers/index.blade.php`.
- [x] Wire search property to model with a custom input debounce (`300ms`).
- [x] Implement select dropdown control filter for status (`active`, `archived`, `all`).
- [x] Set active status filter as the default query setting.
- [x] Integrate column sorting mechanisms for Razón Social (database field `name`).
- [x] Create error hooks to capture soft deletion constraint exceptions and display them to the user.

## UI View & Styling
- [x] Render list of suppliers in a table layout.
- [x] Match all visual elements with the design system (clean headers, custom status badges, action icons).
- [x] Create a friendly and clear empty state message: `"No se encontraron proveedores que coincidan con los filtros aplicados"`.
- [x] Embed action buttons for Edit and Archive on each table record row.
- [x] Add the confirmation modal dialog asking for soft delete confirmation.

## Testing & Verification
- [x] Run `SupplierManagementTest.php` suite asserting guest redirects, role access permissions, search queries, status filter operations, and deletion constraints.
- [x] Update the Roadmap register to add this feature.
