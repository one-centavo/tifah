# 011 · Show and Filter Sanitary Registries — Plan

## Approach

We will modify the existing Sanitary Registries index page to implement the required search, selection, and range filters using the Livewire Volt component.

- **Livewire Volt Component (`resources/views/livewire/sanitary-registries/index.blade.php`):**
  - Define new public properties for filters:
    - `public string $search = '';` (Registration number search)
    - `public string $laboratoryFilter = 'all';` (Dropdown to filter by laboratory ID)
    - `public string $expirationStart = '';` (Date input for range start)
    - `public string $expirationEnd = '';` (Date input for range end)
    - `public string $statusFilter = 'all';` (Dropdown to filter by status: `valid`, `expired`, `under_renewal`)
    - `public string $softDeleteFilter = 'active';` (Filter visibility: `active` / Yes, `archived` / No, or `all` / Todos)
  - Implement lifecycle hooks `updatingSearch`, `updatingLaboratoryFilter`, `updatingExpirationStart`, `updatingExpirationEnd`, `updatingStatusFilter`, `updatingSoftDeleteFilter` to call `$this->resetPage()` to avoid pagination issues when filters are changed.
  - In the `with()` method, retrieve:
    - The paginated `$registries` query matching all active filter constraints.
    - All available laboratories (`Laboratory::orderBy('name')->get()`) to populate the laboratory filter dropdown.
  - Update the Blade template:
    - Layout the filter controls (search input, laboratory dropdown, start date, end date, status dropdown, catalog visibility dropdown) cleanly using Tailwind classes.
    - Map visibility in the table: active records display "Sí" and soft-deleted/archived records display "No".
    - Check if the filtered collection is empty (`$registries->isEmpty()`) and show the alert message: `"No se encontraron registros sanitarios que coincidan con los filtros aplicados"`.
    - Provide the edit route button/icon and the delete trigger modal button.

- **Tests (`tests/Feature/Pages/SanitaryRegistryManagementTest.php`):**
  - Update existing test assertions to align with the new specifications.
  - Add tests validating that searching by registration number works correctly.
  - Add tests verifying the laboratory filter.
  - Add tests verifying the expiration date range filter (start and/or end dates).
  - Add tests verifying the status filter.
  - Add tests verifying the visibility (catalog) filter (active/archived/all).
  - Add tests verifying that the empty state message `"No se encontraron registros sanitarios..."` is rendered when filters return nothing.

## Implementation Steps

1. **Volt Component Class Properties & Hooks:**
   - Modify the class definition in `resources/views/livewire/sanitary-registries/index.blade.php` to define the filter properties.
   - Add the necessary `updating*` lifecycle hooks to reset pagination.

2. **Query Filtering Logic:**
   - Update the `with()` query to conditionally apply `where('laboratory_id', ...)` when a laboratory is chosen.
   - Add range filters for `expiration_date` using `where('expiration_date', '>=', ...)` and `where('expiration_date', '<=', ...)`.
   - Update `with()` to pass the list of all laboratories to the view.

3. **Blade Template Controls & Columns:**
   - Add the laboratory selection filter.
   - Add the date picker inputs for expiration start and end date range.
   - Adjust the catalog visibility column in the table to display "Sí" or "No" based on the soft-deleted state.
   - Ensure the empty state message matches the translation strictly.

4. **Test Implementation:**
   - Add detailed assertions for each filtering criteria in `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php`.

5. **Validation:**
   - Run Pint and Pest test suite.
