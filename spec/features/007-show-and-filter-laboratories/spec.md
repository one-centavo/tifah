# 007 · Show and Filter Laboratories

**Status:** Completed

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to view a complete list of registered laboratories, search for specific manufacturers by name, sort them alphabetically, and filter them by their active/archived status. It also serves as the main dashboard containing actions to edit or soft-delete laboratories.

## Why

To quickly manage, locate, and audit medicine manufacturers (laboratories) in the system. Facilitating filters and state management (Active/Archived) prevents cluttering the active list with inactive or soft-deleted laboratories while maintaining historical traceability.

## Acceptance criteria

### 1. Laboratories Table and Fields
- The table must display the following columns:
  - **Nombre** (Laboratory Name)
  - **Descripción** (Laboratory Description)
  - **Estado** (Status - Active/Archived indicator, derived from soft delete)
  - **Acciones** (Actions - Edit, Delete/Archive, or Restore if archived)

### 2. Status Definition (Soft Delete)
- The status of a laboratory is determined by its soft delete state:
  - **Activo (Active):** The record is NOT soft-deleted (`deleted_at` is null).
  - **Archivado (Archived):** The record IS soft-deleted (`deleted_at` is not null).

### 3. Filtering Options
- **Name Search**: A text input field to filter laboratories by Name (supporting partial case-insensitive matches).
- **Status Filter**: A select/dropdown control to filter laboratories by state:
  - *Activos* (Active - Default on initial load)
  - *Archivados* (Archived/Soft-deleted)
  - *Todos* (All, both active and soft-deleted)

### 4. Sorting
- The listing must allow sorting the records by the Name field:
  - Ascending (A-Z)
  - Descending (Z-A)
- In addition, sorting by status must group active first or archived first.

### 5. Empty State Messaging
- If the filters applied return zero results, the system must display a clear message: `"No se encontraron laboratorios que coincidan con los filtros aplicados"`.

### 6. Default State
- By default, the page must load only active laboratories sorted by Name in ascending order.

### 7. Actions per Record
- **Editar (Edit)**: Action link/button redirecting the user to the edit form (HU 20: `/laboratories/{id}/edit`).
- **Eliminar (Soft Delete)**: Action button to initiate a logical deletion.
  - Validation: Prevents deletion if the laboratory has active medicines associated with it.
  - Displays a confirmation modal before deletion.

## Out of scope
- Advanced export functions (Excel/PDF) from this view.
- Recovering or viewing audit details of the updater or deleter from this view (handled via database/backend audits).
