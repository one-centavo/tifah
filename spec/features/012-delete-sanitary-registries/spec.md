# 012 · Sanitary Registry Soft Deletion

**Status:** Completed

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to archive obsolete or incorrect sanitary registries (INVIMA) from the registry management list. This is achieved via a soft delete process that preserves historical records for auditing and query, while preventing the deletion if active medicines are associated with the registry.

## Why

To keep the sanitary registry catalog clean and updated, correct registry entry errors, and maintain audit records of who deleted each registry and when. Restricting deletion for registries with linked active medicines prevents catalogs from breaking and preserves database integrity.

## Acceptance criteria

### 1. Initiation from Sanitary Registries List
- Authorized users must be able to initiate the archiving (delete) operation directly from the Sanitary Registries Management list (`/sanitary-registries`), specifically using an "Eliminar" (Delete/Archive) action button in the sanitary registry row.

### 2. Integrity Check (Active Medicines Validation)
- Before performing the logical delete, the system must validate that there are no active (non-soft-deleted) medicines associated with the sanitary registry.
- If one or more active medicines exist, the system must prevent the deletion and display a clear error message in Spanish: `"No se puede eliminar el registro sanitario porque tiene medicamentos activos asociados."`

### 3. Pre-Deletion Confirmation
- The system must require confirmation before executing the soft deletion.
- A confirmation dialog/modal must be shown asking the user to confirm the archive action.

### 4. Automatic State Update and Auditing
- Upon successful soft deletion:
  - The registry's status in the UI automatically reflects its archived/deleted state (e.g. not displaying as active, or showing "No" in catalog visibility).
  - The authenticated user's ID must be automatically stored in the `deleted_by` column.
  - The exact timestamp of the operation must be automatically stored in the `deleted_at` column.

### 5. Exclusion from Operations
- Once archived/soft-deleted, the sanitary registry must:
  - No longer appear in active operational lists and dropdown selections (e.g. when creating or editing medicines).
  - Remain in the database to support historical lookups, audits, and reports.

### 6. Success Feedback
- After a successful soft deletion, the system must display a clear success confirmation message in Spanish to the user: `"El registro sanitario ha sido eliminado con éxito."`
