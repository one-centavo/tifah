# 025 · Edit Provider — Tasks

## Requests Validation & Service logic
- [x] Create `UpdateSupplierRequest` to validate input updates.
- [x] Configure NIT unique rule in request, excluding the current supplier ID: `unique:suppliers,nit,{$id}`.
- [x] Add custom friendly error messages in Spanish for field limits, regex patterns, and required constraints.
- [x] Update `SupplierService->update()` method to capture `updated_by` ID and perform the update.

## Livewire Edit Component
- [x] Create `resources/views/livewire/suppliers/edit.blade.php` Volt component.
- [x] Pre-fill the form properties with the existing supplier instance attributes in `mount()`.
- [x] Render all mandatory input fields (NIT, DV, Razón Social, Teléfono, Correo Electrónico, Dirección Física) and optional fields (Persona de Contacto).
- [x] Set the verification digit input (DV) to read-only/disabled.
- [x] Integrate Alpine.js `$watch('nit')` to calculate the Modulo 11 verification digit in real-time.
- [x] Add error message output styling and CSS highlighting underneath invalid fields.
- [x] Flash session confirmation message `"El proveedor ha sido actualizado con éxito."` and redirect to supplier index on success.

## Routing & Integration
- [x] Register the edit route `/suppliers/{supplier}/edit` named `suppliers.edit` under the auth middleware in `routes/web.php`.
- [x] Add the edit link action to the table list on the suppliers index view.

## Testing & Audit Verifications
- [x] Add Pest test to verify guest users cannot access the edit view.
- [x] Add Pest test to verify validation rules and uniqueness checks during edit.
- [x] Add Pest test to verify updating a supplier preserves the original creator (`created_by`) and updates `updated_by` correctly.
- [x] Update `spec/constitution/roadmap.md` to register this feature.
