# 030 · Delete Customer — Tasks

## Database, Model & Service Layer
- [x] Ensure `Customer` model uses `SoftDeletes` trait and has `deleted_by` in `$fillable`.
- [x] Configure `deleter` relationship in `Customer` model mapping to `User`.
- [x] Configure `bills` relationship in `Customer` model mapping to `Bill`.
- [x] Implement `delete(Customer $customer)` method in `CustomerService`.
- [x] Enforce business validation in `CustomerService->delete()` preventing deletion when linked invoices exist (`$customer->bills()->exists()`).
- [x] Throw `ValidationException` with localized Spanish message if linked invoices are found.
- [x] Record `deleted_by` with `auth()->id()` prior to executing `$customer->delete()`.

## Livewire Integration & UI
- [x] Add an "Archivar" action button to each active customer row in `resources/views/livewire/customers/index.blade.php`.
- [x] Implement `confirmCustomerDeletion(int $id)` in `customers.index` to capture target customer ID/name and trigger modal display.
- [x] Implement `deleteCustomer(CustomerService $customerService)` in `customers.index` component to handle deletion, catch validation exceptions, close modal, and flash success session message.
- [x] Create the confirmation modal (`confirm-customer-deletion`) inside `customers.index` displaying the customer's legal name and inline error feedback if deletion fails.
- [x] Style status column in the table list to display a gray badge "Archivado" for soft-deleted customers and a green badge "Activo" for active customers.
- [x] Ensure customer table query filters by soft deletion status (Active, Archived, All) as defined in HU 22.

## Verification & Tests
- [x] Write Pest test to verify guest users are redirected from the pages.
- [x] Write Pest test to verify authorized roles can initiate and perform soft deletion.
- [x] Write Pest test to verify validation error when attempting to soft delete a customer with associated invoices (`bills`).
- [x] Write Pest test to verify successful soft delete, `deleted_by` user ID assignment, and `deleted_at` timestamp recording.
- [x] Write Pest test to verify that archived customers are excluded from default active list queries.
- [x] Register this feature in `spec/constitution/roadmap.md`.
