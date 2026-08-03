# 003 · Show and Filter Categories

**Status:** Draft

## What it does

Provides comprehensive search, filter, and sort capabilities on the Category Management dashboard (`/categories`). It allows authorized users (Administrators or Warehouse Assistants) to quickly find and classify categories, and verify their sanitary and logistical settings (Cold Chain and Special Control).

## Why

As the inventory grows, warehouse staff need to be able to locate specific categories quickly, review their configurations (like temperature control requirements or restricted medication status), and retrieve archived categories without cluttering the active list.

## Acceptance criteria

### 1. Categories Table and Fields
- The table must display the following columns:
  - **Nombre** (Category Name)
  - **Manejo de Cadena de Frío** (Cold Chain Management - Yes/No indicator)
  - **Control Especial** (Special Control - Yes/No indicator)
  - **Estado** (Status - Active/Archived indicator, derived from soft delete)
  - **Acciones** (Actions - Edit, Delete/Archive, or Restore if archived)

### 2. Status Definition (Soft Delete)
- The status of a category is not determined by a direct database column, but by whether it has been soft-deleted:
  - **Activa (Active):** The record is NOT soft-deleted (`deleted_at` is null).
  - **Archivada (Archived):** The record IS soft-deleted (`deleted_at` is not null).

### 3. Filtering Options
- **Name Search**: A text input field to filter categories by Name. It must support partial case-insensitive matches.
- **Cold Chain Filter**: A select/dropdown filter with options:
  - *Todos* (All)
  - *Sí* (Only categories requiring cold chain)
  - *No* (Only categories not requiring cold chain)
- **Special Control Filter**: A select/dropdown filter with options:
  - *Todos* (All)
  - *Sí* (Only special control categories)
  - *No* (Only non-special control categories)
- **Status Filter**: A select/dropdown filter or control to filter by soft delete state:
  - *Activas* (Active categories - Default on load)
  - *Archivadas* (Archived/Soft-deleted categories)
  - *Todas* (All categories, both active and soft-deleted)

### 4. Sorting
- The list must support ordering by:
  - **Nombre** (Name - toggling ascending and descending order)
  - **Estado** (Status/Soft-deleted state - toggling between Active first or Archived first)

### 5. Empty State Messaging
- If the filters applied return zero results, the system must display the message: `"No se encontraron categorías que coincidan con los filtros aplicados"`.

### 6. Default State
- On initial page load, the table must only load active categories (where `deleted_at` is null), sorted by Name ascending by default.

## Out of scope
- Bulk importing or exporting categories from spreadsheets.
- Advanced combination filters using tag chips.
