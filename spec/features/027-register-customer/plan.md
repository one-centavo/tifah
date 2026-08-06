# 027 · Register Customer — Plan

## Approach

We implement the Customer Registration and Management feature utilizing a Laravel service layer (`CustomerService`), dedicated form requests (`StoreCustomerRequest` and `UpdateCustomerRequest`) for clean input validation, and Livewire Volt components for a reactive UI (including automated DIAN verification digit calculation and deletion confirmation).

---

### 1. Database & Models
- **`Customer` Model**:
  - Leverages the `SoftDeletes` trait to execute a soft delete on the `customers` database table.
  - The `created_by`, `updated_by`, and `deleted_by` columns track the respective users.
  - Define relations:
    - `creator()` -> `belongsTo(User, 'created_by')`
    - `updater()` -> `belongsTo(User, 'updated_by')`
    - `deleter()` -> `belongsTo(User, 'deleted_by')`
    - `bills()` -> `hasMany(Bill, 'id_customer')`

---

### 2. Form Requests & Validation
- **`StoreCustomerRequest`** & **`UpdateCustomerRequest`**:
  - Inherit from `CustomerRequestBase`.
  - Add specific NIT validation rules:
    - Required, maximum of 20 characters.
    - Matches Colombian NIT format regex: `/^\d{1,3}(\.\d{3})*$/` (digits separated by dots, e.g., `900.123.456`).
    - Unique among active customers (`deleted_at IS NULL`).
  - Validation messages in Spanish for all fields:
    - NIT, name (Razón Social), city (Ciudad), address (Dirección de Entrega), phone_number, email (valid email layout), and active status.

---

### 3. Business Logic (`App\Services\CustomerService`)
- **`create(array $data)`**:
  - Sets `created_by = auth()->id()`.
  - Persists the new Customer record.
- **`update(Customer $customer, array $data)`**:
  - Sets `updated_by = auth()->id()`.
  - Updates and saves the Customer record.
- **`delete(Customer $customer)`**:
  - Validates referential integrity: Checks if any invoices/bills are linked to the customer.
  - If `$customer->bills()->exists()`, throws a `ValidationException` with the message: `"No se puede eliminar el cliente porque tiene facturas asociadas en el histórico."`
  - Otherwise, sets `deleted_by = auth()->id()`, saves, and calls `$customer->delete()`.

---

### 4. Routing & Views
- **Routes (`routes/web.php`)**:
  - Add routes inside the `auth` middleware group:
    - `Volt::route('customers', 'customers.index')->name('customers.index');`
    - `Volt::route('customers/create', 'customers.create')->name('customers.create');`
    - `Volt::route('customers/{customer}/edit', 'customers.edit')->name('customers.edit');`
- **Navigation Menu (`resources/views/layouts/navigation.blade.php` or `app.blade.php`)**:
  - Include a link to "Clientes" for quick navigation.
- **Livewire Volt Components**:
  - **`customers.index`**:
    - Listing table with columns: NIT-DV, Razón Social, Ciudad, Dirección, Teléfono, Correo, Estado, Acciones.
    - Search input (filtering by Razón Social or NIT).
    - Status filter dropdown: "Activos" (Active, default), "Inactivos" (Inactive), "Archivados" (Archived/Soft-deleted), "Todos" (All).
    - "Archivar" action button to trigger a confirmation modal.
    - Confirmation modal `#confirm-customer-deletion` that triggers the delete service logic.
  - **`customers.create`**:
    - Form inputs for all customer fields.
    - Reactive calculation of DV digit using AlpineJS on the frontend and updatedNit on the backend.
    - Inactive status selection via an toggle switch/checkbox.
    - Submit calls `CustomerService->create` and redirects to index with flash message: `"El cliente ha sido registrado con éxito."`
  - **`customers.edit`**:
    - Form populated with existing customer details.
    - Submit calls `CustomerService->update` and redirects to index with flash message: `"El cliente ha sido actualizado con éxito."`

---

### 5. Verification & Feature Tests
- Feature test cases in `tests/Feature/Pages/Customers/CustomerManagementTest.php` cover:
  - Guest redirection to login.
  - Authorized users access to index, create, and edit pages.
  - Reactive computation of verification digit (DV).
  - Validation rules on creation/update.
  - NIT uniqueness check against other active customers, while allowing reuse of NITs from soft-deleted customers.
  - Successful customer creation with `created_by` audit logging.
  - Successful customer updating with `updated_by` audit logging.
  - Uniqueness validation ignore self on updates.
  - Soft delete and `deleted_by` logging when no bills exist.
  - Block deletion showing proper error message when bills are associated.
  - Searching by name and NIT.
  - Filtering by status (active, inactive, archived, all).
