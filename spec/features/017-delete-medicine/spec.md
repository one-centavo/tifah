# 017 · Medicine Soft Deletion

**Status:** Completed

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to archive a medicine from the medicines catalog (soft delete). This hides the medicine from active lists, dropdowns, and sales interfaces while preserving its records in the database for auditing and historical traceability.

## Why

To allow catalog managers to deprecate or remove obsolete medicines without breaking database foreign keys and maintaining access to audit and log records of inventory movement. By validating that no active stock lots exist before deletion, we ensure that the catalog remains consistent and active inventory is not accidentally orphaned or hidden.

## Acceptance criteria

### 1. Access Control & Deletion Trigger
- [x] Only authorized users (Administrators or Warehouse Assistants) can initiate the archiving process.
- [x] The archiving (soft deletion) operation can be initiated from:
  - The medicine catalog list index (`/medicines`, using the "Archivar" button in the table row).
  - The medicine detailed view modal (HU 18, using the "Archivar" button inside the modal).

### 2. Validation of Active Stock (Lotes Activos)
- [x] Before executing the soft deletion, the system must validate that there are no active stock lots associated with the medicine.
- [x] Specifically, active stock means lots with status `active` and `current_quantity > 0` (using the `hasActiveLots` check).
- [x] If active stock exists, the action must be prevented, and a clear error message must be shown to the user: `"No se puede archivar el medicamento porque existen lotes activos en el inventario asociados a este producto."`

### 3. Pre-Deletion Confirmation
- [x] Before performing the logical delete, the system must request user confirmation.
- [x] The system displays a confirmation modal showing the name of the medicine and explaining that it will be archived.

### 4. Automatic State Update and Auditing
- [x] Once deletion is confirmed:
  - The medicine's `deleted_at` timestamp is set to the current time.
  - The authenticated user's ID is stored in the `deleted_by` column.
  - All associated barcodes are also soft-deleted with the `deleted_by` attribute populated.

### 5. Exclusion from Active Operations
- [x] Soft-deleted medicines are hidden from active inventory search lists, dropdowns, and sale/purchase forms by default.
- [x] Archived medicines can only be viewed in the catalog list if the "Visibilidad en Catálogo" filter is explicitly set to "Archivados" or "Todos".

### 6. Success Feedback
- [x] Upon successful archiving, a confirmation message is displayed: `"El medicamento ha sido eliminado con éxito."`

## Out of scope
- Physical deletion of the medicine record from the database.
- Restoration of archived medicines from the detailed view (restoration is out of scope for this specific story or handled separately).
