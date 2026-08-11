# 029 · Show and Filter Customers — Plan

## Approach

Implement the customer listing, searching, filtering, and sorting system using a reactive Livewire Volt component (`resources/views/livewire/customers/index.blade.php`). The component will dynamically build Eloquent queries against the `Customer` model, handle pagination, and render an interactive table with real-time UI state updates.

---

### 1. Database & Eloquent Query Architecture
- **Model**: `App\Models\Customer`
- **Soft Deletes**:
  - `active` status: `Customer::query()` (default global scope excludes trashed records).
  - `archived` status: `Customer::onlyTrashed()`.
  - `all` status: `Customer::withTrashed()`.
- **Global Search**:
  - Filter by `name LIKE %search%` OR `nit LIKE %search%`.
- **City Filter**:
  - When a specific city is selected (`$city !== ''`), apply `where('city', $this->city)`.
  - Provide a distinct list of registered cities (`Customer::withTrashed()->select('city')->distinct()->pluck('city')`) to populate the filter dropdown.
- **Sorting**:
  - Sort by `$sortField` (default `'name'`) in `$sortDirection` (`'asc'` or `'desc'`).
- **Pagination**:
  - Use `WithPagination` trait (10 or 15 items per page) to ensure optimal performance.

---

### 2. Livewire Volt Component (`resources/views/livewire/customers/index.blade.php`)
- **Reactive Properties**:
  - `public string $search = ''`: Global search term.
  - `public string $city = ''`: Selected city filter.
  - `public string $status = 'active'`: Soft-delete status filter (defaults to `'active'`).
  - `public string $sortField = 'name'`: Field to sort by.
  - `public string $sortDirection = 'asc'`: Sort direction.
  - `public ?int $customerToDeleteId = null`: ID of the customer pending archiving confirmation.
- **Lifecycle & Query Reset Hooks**:
  - `updatingSearch()`: Reset pagination page to 1.
  - `updatingCity()`: Reset pagination page to 1.
  - `updatingStatus()`: Reset pagination page to 1.
- **Actions & Methods**:
  - `sortBy(string $field)`: Toggle sort direction if same column, or change sort column and reset direction to `'asc'`.
  - `confirmCustomerDeletion(int $id)`: Set target customer ID and display the archiving confirmation modal.
  - `archiveCustomer(CustomerService $customerService)`: Delegate soft deletion logic and referential integrity checks to `CustomerService`.
  - `with()`: Prepare and pass paginated customers and list of available cities to the Blade template.

---

### 3. User Interface & Blade Template
- **Search & Filter Bar**:
  - Text input for `$search` with `wire:model.live.debounce.300ms` and a search icon.
  - Select dropdown for `$city` with `wire:model.live` populated with distinct cities.
  - Select dropdown for `$status` (`Activos`, `Archivados`, `Todos`) with `wire:model.live`.
- **Data Table**:
  - Columns:
    - **Razón Social** (clickable sorting header with active indicator).
    - **NIT** (rendered as `NIT-DV`).
    - **Ciudad**.
    - **Teléfono**.
    - **Estado** (colored badges: Green for Activo, Gray/Red for Archivado).
    - **Acciones** (Edit and Archive action buttons).
- **Empty State**:
  - Responsive empty alert/card displaying: `"No se encontraron clientes que coincidan con los filtros aplicados"`.
- **Confirmation Modal**:
  - Standard modal component prompting the user before archiving a customer record.
  - Display inline error banner if soft delete cannot proceed (e.g., linked bills check).

---

### 4. Service Layer Integration (`App\Services\CustomerService`)
- Maintain soft deletion execution inside `CustomerService->delete(Customer $customer)`:
  - Verify that the customer has no associated invoices/bills (`bills()->exists()`).
  - Set `deleted_by = auth()->id()`.
  - Call `$customer->delete()`.
- Return proper validation feedback or exceptions to the UI layer upon business rule violations.

---

### 5. Testing Plan (Pest PHP)
- **Access Control**:
  - Verify unauthenticated users are redirected to `/login`.
  - Verify `Administrator` and `Warehouse Assistant` can view the `/customers` route.
- **Default Load**:
  - Assert that only active customer records are displayed on initial page load.
- **Search & Filters**:
  - Assert searching by name or partial NIT returns matching customers.
  - Assert filtering by city returns only customers from that city.
  - Assert filtering by status displays active, archived, or both accordingly.
- **Empty State**:
  - Assert empty message `"No se encontraron clientes que coincidan con los filtros aplicados"` appears when no records match.
- **Sorting**:
  - Assert records are sorted alphabetically by Razón Social when clicking table header.
- **Action Navigation**:
  - Assert Edit link points to `/customers/{id}/edit`.
  - Assert Archiving soft deletes the customer and updates status badge.
