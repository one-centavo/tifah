# 010 · Edit Sanitary Register & Sanitary Register Management — Plan

## Approach

We will implement the Sanitary Register Edit feature and its Management/List view using Volt components, validation requests, and the service layer.

- **Service Layer (`App\Services\SanitaryRegistryService`):**
  - Implement `create(array $data): SanitaryRegistry` to set `created_by` and uppercase-normalize `registration_number`.
  - Implement `update(SanitaryRegistry $registry, array $data): SanitaryRegistry` to persist modifications, assign `updated_by` to the authenticated user, and uppercase-normalize `registration_number`.
  - Implement `delete(SanitaryRegistry $registry): void` to soft delete registries if no active medicines are associated with them.
  - Implement `restore(SanitaryRegistry $registry): void` to restore soft-deleted registries.
- **Form Requests (`App\Http\Requests\SanitaryRegistry`):**
  - Create `StoreSanitaryRegistryRequest` for creating registries with format/uniqueness validations.
  - Create `UpdateSanitaryRegistryRequest` for updating registries, verifying that `registration_number` is unique except for the current record.
- **Livewire Volt Components:**
  - **Index View (`resources/views/livewire/sanitary-registries/index.blade.php`):**
    - List table for sanitary registries showing number, laboratory name, expiration date, status (color-coded badge), and description.
    - Search by registration number.
    - Filters by status (`active`, `archived`, `all`) and laboratory.
    - Sortable columns.
    - Action buttons to Edit (redirects) and Delete (triggers soft delete confirmation modal).
  - **Edit View (`resources/views/livewire/sanitary-registries/edit.blade.php`):**
    - Preloads current registry details.
    - Standard form with fields: registration number, laboratory, expiration date, status, description.
    - Uppercase normalization and submission handling via `SanitaryRegistryService`.
    - Session flash notification and redirection.

## Implementation Steps

1. **Service Layer Setup:**
   - Create [SanitaryRegistryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/SanitaryRegistryService.php) with `create`, `update`, `delete`, and `restore` methods.
2. **Form Request validation:**
   - Create `app/Http/Requests/SanitaryRegistry/StoreSanitaryRegistryRequest.php` and `UpdateSanitaryRegistryRequest.php`.
   - Implement format and date validations (`after_or_equal:today`).
3. **Livewire Component - Index Page:**
   - Create Volt component at `resources/views/livewire/sanitary-registries/index.blade.php` using layout `layouts.app`.
   - Setup searching, sorting, and filtering options.
4. **Livewire Component - Edit Page:**
   - Create Volt component at `resources/views/livewire/sanitary-registries/edit.blade.php` using layout `layouts.app`.
   - Define bound fields, mount values, validate via `UpdateSanitaryRegistryRequest`, and call `SanitaryRegistryService@update`.
5. **Routing & Sidebar Layout:**
   - Register routes in `routes/web.php` for index and edit.
   - Update sidebar link in `resources/views/layouts/app.blade.php`.
6. **Automated Testing:**
   - Create `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php` and `EditSanitaryRegistryTest.php`.
7. **Verification:**
   - Run Laravel Pint and php artisan test to confirm everything is working.
