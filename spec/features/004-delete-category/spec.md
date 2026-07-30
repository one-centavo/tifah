# 004 · Category Soft Deletion

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to archive obsolete or unused medicine categories from the category management list. This is achieved via a soft delete process that preserves historical records for auditing and reporting while preventing the category from being used in active operations.

## Why

To maintain a clean and accurate catalog of medicine categories without losing historical data. Ensuring that active products associated with a category prevent its archiving protects database integrity and prevents operational errors.

## Acceptance criteria

### 1. Initiation from Category List
- Authorized users must be able to initiate the archiving (delete) operation directly from the Category Management list (`/categories`), specifically using an "Archive" (or "Delete") action button in the category row.

### 2. Integrity Check (Active Medicines Validation)
- Before performing the logical delete, the system must validate that there are no active (non-soft-deleted) medicines associated with the category.
- If one or more active medicines exist within the category, the system must prevent the deletion and display a clear error message to the user, such as: `"No se puede eliminar la categoría porque tiene medicamentos activos asociados."`

### 3. Pre-Deletion Confirmation
- The system must require confirmation before executing the soft deletion.
- A confirmation dialog/modal must be shown asking the user to confirm the archive action.

### 4. Automatic State Update and Auditing
- Upon successful soft deletion:
  - The category's state automatically changes to "Archivada" (Archived). In the UI, it is displayed as archived (e.g., status badge "Archivada").
  - The authenticated user's ID must be automatically stored in the `deleted_by` column.
  - The exact timestamp of the operation must be stored in the `deleted_at` column.

### 5. Exclusion from Operations
- Once archived, the category must:
  - Not appear in active operational list views by default.
  - Not be selectable in forms (such as medicine creation or editing dropdowns).
  - Remain in the database for historical and reporting purposes.

### 6. Success Feedback
- After a successful soft deletion, the system must display a clear success confirmation message to the user (e.g., `"La categoría ha sido eliminada con éxito."`).
