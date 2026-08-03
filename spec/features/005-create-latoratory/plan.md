# 005 · Create Laboratory & Laboratory Management — Plan

## Approach

We will implement the registration and management of laboratories using a service class `LaboratoryService` and Livewire Volt components for creating, listing, and deleting laboratories.

### 1. Database & Models
- The `Laboratory` model (`App\Models\Laboratory`) and the migration are already defined and support soft deletes (`SoftDeletes` trait, `deleted_at`, `deleted_by`).
- Verify the relationship between `Laboratory` and `Medicine`: `Laboratory` has many `Medicine`.

### 2. Service Layer (`LaboratoryService`)
- Create `App\Services\LaboratoryService`:
  - `create(array $data)`: Create a new laboratory, setting `created_by` to `auth()->id()`.
  - `delete(Laboratory $laboratory)`: Check if active medicines exist. If yes, throw a `ValidationException` with an appropriate message. If no, set `deleted_by` to `auth()->id()`, save, and call `delete()`.

### 3. Validation and Requests
- Create request classes in `App\Http\Requests\Laboratory`:
  - `StoreLaboratoryRequest`: Extends `LaboratoryRequestBase`, validates `name` (required, unique, max 255) and `description` (optional, max 255) with custom messages.
  - `UpdateLaboratoryRequest`: For editing/updating (if applicable).

### 4. Volt Components & Views
- Create `resources/views/livewire/laboratories/create.blade.php`:
  - Simple form for Name and Description.
  - Validation feedback, error highlighting.
  - Session success flash messaging.
- Create `resources/views/livewire/laboratories/index.blade.php` (if listing and deletion are managed there):
  - Table of active laboratories.
  - Delete action button.
  - Deletion confirmation modal.
  - Handle delete action via `LaboratoryService`, catching validation exceptions and displaying the error in the UI.

### 5. Routing & Navigation
- Define routes in `routes/web.php` for laboratories index and create.
- Add laboratory navigation links to the sidebar layout (`resources/views/livewire/layout/navigation.blade.php`).

### 6. Automated Testing
- Create feature tests:
  - `tests/Feature/Pages/Laboratories/CreateLaboratoryTest.php`:
    - Authorized users (Admin/Warehouse Assistant) can create a laboratory.
    - Guest is redirected to login.
    - Validate name uniqueness, required, and length constraints.
    - Validate successful creation sets auditing fields (`created_by`, `created_at`).
  - `tests/Feature/Pages/Laboratories/LaboratoryManagementTest.php`:
    - Display laboratory list.
    - Can soft delete a laboratory with no active medicines (verifies `deleted_by`, `deleted_at`).
    - Cannot delete a laboratory with active medicines (shows error).
