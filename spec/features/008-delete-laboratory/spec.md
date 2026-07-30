# 008 · Laboratory Soft Deletion

**Status:** Completed

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to archive obsolete or inactive laboratories from the laboratory management list. This is achieved via a soft delete process that preserves historical records for auditing and reporting while preventing the laboratory from being used in active operations or appearing in active views.

## Why

To maintain a clean and accurate catalog of medicine manufacturers (laboratories) without losing historical data. Ensuring that active medicines associated with a laboratory prevent its archiving protects database integrity and prevents operational errors.

## Acceptance criteria

### 1. Initiation from Laboratory List
- Authorized users must be able to initiate the archiving (delete) operation directly from the Laboratory Management list (`/laboratories`), specifically using an "Eliminar" (Delete/Archive) action button in the laboratory row.

### 2. Integrity Check (Active Medicines Validation)
- Before performing the logical delete, the system must validate that there are no active (non-soft-deleted) medicines associated with the laboratory.
- If one or more active medicines exist, the system must prevent the deletion and display a clear error message: `"No se puede eliminar el laboratorio porque tiene medicamentos activos asociados."`

### 3. Pre-Deletion Confirmation
- The system must require confirmation before executing the soft deletion.
- A confirmation dialog/modal must be shown asking the user to confirm the archive action, displaying the name of the laboratory.

### 4. Automatic State Update and Auditing
- Upon successful soft deletion:
  - The laboratory's state automatically changes to "Archivado" (Archived). In the UI, it is displayed as archived (status badge "Archivado").
  - The authenticated user's ID must be automatically stored in the `deleted_by` column.
  - The exact timestamp of the operation must be stored in the `deleted_at` column.

### 5. Exclusion from Operations
- Once archived, the laboratory must:
  - Not appear in active operational list views by default.
  - Not be selectable in forms (such as medicine creation or editing dropdowns).
  - Remain in the database for historical and reporting purposes.

### 6. Success Feedback
- After a successful soft deletion, the system must display a clear success confirmation message to the user: `"El laboratorio ha sido eliminado con éxito."`
