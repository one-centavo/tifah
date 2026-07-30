# 008 · Create Sanitary Register & Sanitary Register Management — Plan

## Approach

We will implement the registration, validation, search/filtering, and soft deletion of sanitary registries using a dedicated service class `SanitaryRegistryService`, standard Request classes, and Livewire Volt components.

### 1. Database & Models
- The `SanitaryRegistry` model (`App\Models\SanitaryRegistry`) and table migration are already created and configured with soft delete traits and audit foreign keys (`created_by`, `updated_by`, `deleted_by`).
- Verify model relationships:
  - `SanitaryRegistry` belongs to `Laboratory`
  - `SanitaryRegistry` has many `Medicine`
  - `SanitaryRegistry` belongs to `User` as creator, updater, and deleter.

### 2. Service Layer (`SanitaryRegistryService`)
- Create `App\Services\SanitaryRegistryService` containing:
  - `create(array $data)`: Sets the `created_by` field to `auth()->id()`, normalizes the `registration_number` to uppercase, and persists the record.
  - `delete(SanitaryRegistry $registry)`:
    - Verifies if there are any active medicines associated with the registry.
    - If active medicines exist, throws a `ValidationException` preventing deletion.
    - If no active medicines exist, sets `deleted_by` to `auth()->id()`, saves the model, and calls `delete()`.

### 3. Request Validation
- Create `App\Http\Requests\SanitaryRegistry\StoreSanitaryRegistryRequest` extending `SanitaryRegistryRequestBase`:
  - `registration_number`: Required, string, max 50, unique (message: `"Este número de registro sanitario ya se encuentra registrado"`), and matches regex `/^INVIMA\s+\d{4}[A-Z]-\d{7}$/i`.
  - `laboratory_id`: Required, integer, exists in `laboratories,id`.
  - `expiration_date`: Required, date, must be after today (`after:today`).
  - `status`: Required, string, one of `valid` (Vigente), `expired` (Vencido), or `under_renewal` (En renovación).
  - `description`: Optional, string.

### 4. Volt Components & Views
- **Creation Component** (`resources/views/livewire/sanitary-registries/create.blade.php`):
  - Form with fields: Sanitary Register Number, Laboratory Manufacturer (searchable select dropdown), Expiration Date, Status, Description.
  - Error highlighting for invalid fields.
  - Normalization to uppercase input.
  - Session success flash messaging and redirection.
  - **Quick Laboratory Registration Modal**:
    - Include a modal component `<x-modal name="quick-laboratory-creation">` inside the view.
    - If search yields no results or the user clicks `"+ Registrar Laboratorio"`, open the modal.
    - Modal form fields: `quickLabName` (binds search input query) and `quickLabDescription`.
    - Implement `saveQuickLaboratory(LaboratoryService $laboratoryService)` which:
      - Validates the new laboratory's fields (required, unique, length).
      - Registers the laboratory via `LaboratoryService`.
      - Automatically sets `laboratory_id` to the newly created laboratory, pre-selects it, closes the modal, and flashes a success message.
- **Index Component** (`resources/views/livewire/sanitary-registries/index.blade.php`):
  - Table showing: registration number, laboratory name, expiration date, status (color-coded badge), description.
  - Search by registration number.
  - Filters by Status (`valid`, `expired`, `under_renewal`, `all`) and Laboratory.
  - Soft delete action button triggering a confirmation modal.
  - Exception handling for deletion attempts when medicines are associated.

### 5. Medicine Form Integration
- Update `resources/views/livewire/medicines/create.blade.php` and `edit.blade.php` to:
  - Show a list of only Vigente (`status = 'valid'`) sanitary registries when creating a new medicine.
  - If a medicine is loaded for editing and its associated sanitary registry is expired or not valid, show a prominent visual warning indicator next to the field.

### 6. Routing & Navigation
- Register web routes under `routes/web.php` for sanitary registries (Index and Create views).
- Add navigation link to the sidebar (`resources/views/livewire/layout/navigation.blade.php`).

### 7. Automated Testing
- Implement feature tests using Pest:
  - `tests/Feature/Pages/SanitaryRegistries/CreateSanitaryRegistryTest.php`:
    - Access authorization checks (guests redirected, Warehouse Assistant and Admin allowed).
    - Validation checks for unique constraint, regex matching, and future date.
    - Check registration number normalization to uppercase.
    - Check automatic audit fields (`created_by`, `created_at`).
    - **Quick Laboratory Registration Tests**:
      - Opening modal pre-populates the laboratory name with the current search query.
      - Fails if the laboratory name is empty or duplicate.
      - Succeeds, persists the laboratory in the database, automatically selects it in the form, and closes the modal.
  - `tests/Feature/Pages/SanitaryRegistries/SanitaryRegistryManagementTest.php`:
    - Display list with correct filters.
    - Search by number and filter by status.
    - Successful soft delete when no active medicines are linked.
    - Block soft delete when active medicines are linked, asserting validation exception and error message.
