# 026 · Delete Provider (HU 19)

**Status:** Completed

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to archive an existing supplier (provider) from the supplier management dashboard. This is performed via a soft delete mechanism which transitions the supplier status to "Archived", preserves their historical records for audit compliance and reporting, but excludes them from active operations and forms. Before archiving, the system enforces a business constraint preventing the deletion if the supplier has any active inventory lots associated with them.

## Why

To support clean catalog maintenance and archive obsolete or inactive suppliers without losing historical transaction and lot traceability. Verifying that active merchandise in stock is not orphaned ensures inventory integrity and prevents accounting or compliance discrepancies.

## Acceptance criteria

### 1. Initiation from Provider List
- Authorized users (Administrators or Warehouse Assistants) can initiate the deletion/archiving operation directly from the Provider Management list (`/suppliers`).
- An "Archivar" (Archive) action button is available in the row of each active provider.

### 2. Integrity Check (Active Lots Validation)
- Before executing the soft delete, the system must validate that there are no active (non-soft-deleted) inventory lots (`current_quantity > 0`) associated with purchase orders from the selected supplier.
- If active lots exist, the system blocks the action and displays a clear validation error message: `"No se puede eliminar el proveedor porque tiene lotes de mercancía activos en el inventario."`

### 3. Pre-Deletion Confirmation
- The system prevents accidental deletions by prompting the user with a confirmation dialog/modal.
- The confirmation modal displays the supplier name to be deleted (e.g., `"¿Está seguro de que desea archivar el proveedor?"`) and highlights that the action cannot be undone but historical logs will be preserved.

### 4. Automatic State Update and Auditing
- Upon confirmation and validation success, the system:
  - Changes the provider's status to "Archivado" (Archived), displayed as an archived status badge in the list.
  - Automatically captures the authenticated user's ID and saves it in the `deleted_by` column.
  - Automatically stores the exact timestamp in the `deleted_at` column.

### 5. Exclusion from Operations
- Once a provider is archived:
  - They are excluded from active list views by default (unless the "Archivados" or "Todos" status filter is selected).
  - They do not appear in active operational forms or select dropdowns (such as incoming merchandise logs or purchase orders).
  - They remain in the database for history tracking and reporting.

### 6. Success Feedback
- A successful archive action closes the modal, refreshes the supplier list, and flashes a confirmation success message in Spanish: `"El proveedor ha sido archivado con éxito."`.
