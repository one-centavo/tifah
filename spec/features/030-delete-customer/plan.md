# 030 · Delete Customer — Plan

## Approach

We implement the Delete Customer feature leveraging Laravel's native Eloquent `SoftDeletes` trait, enforcing historical sales invoice integrity checks through `App\Services\CustomerService`, and integrating the confirmation modal and exception handling workflow into the Livewire Volt customer management listing component (`resources/views/livewire/customers/index.blade.php`).

---

### 1. Database & Models
- **`Customer` Model (`App\Models\Customer`)**:
  - Uses the `SoftDeletes` trait to execute logical deletion on the `customers` database table.
  - The `deleted_by` column tracks the authenticated user ID performing the deletion.
  - The `deleted_at` column tracks the timestamp of the operation.
  - Relational mapping to `User` via `deleter()` relationship.
  - Relational mapping to `Bill` via `bills()` relationship (`id_customer`).
- **Relational Integrity & Operational Scopes**:
  - Soft-deleted customers are automatically excluded from default queries and operational dropdowns, satisfying out-of-operations criteria while remaining intact for historical audit.

---

### 2. Business Logic (`App\Services\CustomerService`)
- Uses `delete(Customer $customer): void` in `CustomerService`:
  - Validates historical sales invoice integrity: checks `$customer->bills()->exists()`.
  - Throws `ValidationException` if linked invoices exist with the localized message: `"No se puede eliminar el cliente porque tiene facturas asociadas en el histórico."`
  - Sets `deleted_by = auth()->id()`.
  - Triggers `$customer->delete()` to apply the soft delete and set `deleted_at`.

---

### 3. Livewire List Component (`resources/views/livewire/customers/index.blade.php`)
- **Initiation**:
  - Each customer row displays an "Archivar" action button invoking `confirmCustomerDeletion(int $id)`.
- **Confirmation Flow**:
  - `confirmCustomerDeletion(int $id)` loads the selected customer data and dispatches the `open-modal` event targeting the confirmation modal `#confirm-customer-deletion`.
- **Execution**:
  - Submitting the confirmation modal triggers `deleteCustomer(CustomerService $customerService)`.
  - Catches `ValidationException` to expose inline error messages (`deletion_error`) if referential integrity constraints fail.
  - On success, closes the modal, clears transient error state, flashes a confirmation message `"El cliente ha sido archivado con éxito."`, and refreshes the reactive table.

---

### 4. Verification & Feature Tests
- Feature test cases in `tests/Feature/Pages/Customers/CustomerManagementTest.php` or `CustomerDeleteTest.php` cover:
  - Redirection of unauthenticated guest users.
  - Authorized role access (Administrator and Warehouse Assistant).
  - Business constraint blocking deletion when associated invoices (`bills`) exist.
  - Successful soft delete, verifying `deleted_by` and `deleted_at` timestamp population.
  - Exclusion of soft-deleted customer from default active customer listings while preserving historical records.
