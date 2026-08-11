# 028 · Edit Client — Tasks

## Requests Validation & Service Logic
- [x] Create/update `UpdateCustomerRequest` to validate client modification payloads.
- [x] Implement NIT unique validation rule excluding the current customer ID: `Rule::unique('customers', 'nit')->whereNull('deleted_at')->ignore($this->customer->id)`.
- [x] Configure custom Spanish validation messages for mandatory fields, formats, and uniqueness constraints.
- [x] Ensure `CustomerService->update()` captures `updated_by` ID and persists changes while preserving `created_by`.

## Livewire Edit Component
- [x] Create `resources/views/livewire/customers/edit.blade.php` Volt component.
- [x] Pre-populate form inputs (`nit`, `dv`, `name`, `city`, `address`, `phone_number`, `email`, `is_active`) on `mount()`.
- [x] Render input fields with accessible labels, placeholder text, and error indicators.
- [x] Set Verification Digit (DV) input as disabled and read-only.
- [x] Integrate Alpine.js `$watch('nit')` and Livewire `updatedNit()` for real-time DIAN Modulo 11 DV calculation.
- [x] Add active/inactive status switch or toggle control.
- [x] Display inline error feedback styling underneath invalid fields.
- [x] Flash confirmation message `"El cliente ha sido actualizado con éxito."` and redirect to `/customers` upon successful save.

## Routing & UI Integration
- [x] Register `/customers/{customer}/edit` route under `auth` middleware in `routes/web.php`.
- [x] Ensure the edit action button/icon on each row in `customers.index` navigates to `customers.edit`.

## Testing & Verification
- [x] Add test for unauthenticated access redirection.
- [x] Add test verifying edit form loads with current customer attributes.
- [x] Add test for validation errors on empty mandatory fields or malformed email/NIT.
- [x] Add test for NIT uniqueness validation ignoring the current customer being edited.
- [x] Add test verifying successful update persists `updated_by` and keeps `created_by` / `created_at` unchanged.
- [x] Update `spec/constitution/roadmap.md` to include this feature.
