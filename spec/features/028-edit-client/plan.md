# 028 · Edit Client — Plan

## Approach

We implement the Edit Client feature leveraging the existing `Customer` model, `CustomerService`, and standard Laravel Form Requests. A reactive Livewire Volt edit component (`resources/views/livewire/customers/edit.blade.php`) handles real-time validation and DIAN Modulo 11 DV calculation, seamlessly pre-populating existing customer data.

---

### 1. Database & Models
- Reuses the `Customer` model (`App\Models\Customer`) with `SoftDeletes` trait.
- Uses `updated_by` to record the authenticated user's ID upon updating.
- Preserves `created_by` and `created_at` intact.

---

### 2. Request Validation (`App\Http\Requests\Customer\UpdateCustomerRequest`)
- Inherits from `CustomerRequestBase` or defines update validation rules:
  - **NIT**: Mandatory, max 20 chars, regex `/^\d{1,3}(\.\d{3})*$/`, unique among active customer records ignoring the current customer ID (`Rule::unique('customers', 'nit')->whereNull('deleted_at')->ignore($this->customer->id)`).
  - **name (Razón Social)**: Mandatory, max 255 chars.
  - **city (Ciudad)**: Mandatory, max 100 chars.
  - **address (Dirección de Entrega)**: Mandatory, max 255 chars.
  - **phone_number (Teléfono)**: Mandatory, max 20 chars.
  - **email (Correo Electrónico)**: Mandatory, valid email, max 255 chars.
  - **is_active (Estado)**: Boolean (`true` / `false`).
- Spanish validation messages highlighting errors under corresponding inputs.

---

### 3. Business Logic (`App\Services\CustomerService`)
- Uses or expands `update(Customer $customer, array $data): Customer` in `CustomerService`.
- Logic:
  - Sets `updated_by = auth()->id()`.
  - Recalculates DV from the sanitized NIT before persisting.
  - Updates attributes and saves the `Customer` model instance.

---

### 4. Real-time Verification Digit (DV) Calculation
- **Backend recalculation (`resources/views/livewire/customers/edit.blade.php`)**:
  - Implements an `updatedNit(string $value)` lifecycle hook.
  - Strips non-digit characters: `preg_replace('/[^\d]/', '', $value)`.
  - Applies DIAN weights: `[3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71]`.
  - Computes `remainder = sum % 11`.
  - Sets `dv = remainder > 1 ? 11 - remainder : remainder`.
- **Frontend recalculation (Alpine.js inside form)**:
  - Initializes reactive properties `nit` and `dv` entangled with Livewire.
  - A `$watch('nit', ...)` handler recalculates the DV dynamically on keystrokes.
  - Keeps the DV input readonly and disabled.

---

### 5. Livewire Volt Edit Component (`resources/views/livewire/customers/edit.blade.php`)
- Mounted with the target `Customer` instance, initializing:
  - `$nit`, `$dv`, `$name`, `$city`, `$address`, `$phone_number`, `$email`, and `$is_active`.
- Handles `save()` action:
  - Validates fields against `UpdateCustomerRequest` rules.
  - Delegates persistence to `CustomerService->update`.
  - Emits/flashes session message `"El cliente ha sido actualizado con éxito."`
  - Redirects back to `/customers` using `wire:navigate`.

---

### 6. Routing & Navigation
- Route defined in `routes/web.php` inside the `auth` middleware group:
  - `Volt::route('customers/{customer}/edit', 'customers.edit')->name('customers.edit');`
- Edit buttons on the customer index table link directly to this route.

---

### 7. Verification & Feature Tests
- Feature test cases in `tests/Feature/Pages/Customers/CustomerManagementTest.php` or `CustomerEditTest.php` cover:
  - Unauthenticated guests redirected to login.
  - Authorized access by Administrator and Warehouse Assistant.
  - Form pre-population with current customer data.
  - Validation failure for required fields or invalid email/NIT formats.
  - NIT uniqueness check excluding the customer being updated.
  - Successful update verifying `updated_by` is set and `created_by` remains untouched.
  - Immediate availability of modified customer data.
