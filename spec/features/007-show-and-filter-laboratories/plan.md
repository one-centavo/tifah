# 007 · Show and Filter Laboratories — Plan

## Approach

We will implement the search, filtering, and sorting logic inside the existing Laboratory Index component (`App\Livewire\Laboratories\Index` Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php)).

### 1. State Variables in Livewire Volt Component
- `$search` (string) for partial name matching.
- `$status` (string: `'active'`, `'archived'`, `'all'`) to filter by soft delete state. Default: `'active'`.
- `$sortField` (string: `'name'`, `'status'`) to define the current sorting column. Default: `'name'`.
- `$sortDirection` (string: `'asc'`, `'desc'`) to define the sorting direction. Default: `'asc'`.

### 2. Query Building in `with()` block
Inside the `with()` array provider of [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php):
- Start with the query on the `Laboratory` model.
- Conditionally apply soft-delete status scope based on `$status`:
  - `'active'` (default) -> normal query (only active/non-deleted laboratories).
  - `'archived'` -> use `onlyTrashed()`.
  - `'all'` -> use `withTrashed()`.
- Apply search condition if `$search` is not empty:
  - `where('name', 'like', '%' . trim($this->search) . '%')`.
- Apply sorting:
  - If `$sortField` is `'name'`: `orderBy('name', $this->sortDirection)`.
  - If `$sortField` is `'status'`: Sort by `deleted_at` to distinguish between active and archived:
    - Active first: `orderByRaw('deleted_at IS NULL ' . ($this->sortDirection === 'asc' ? 'DESC' : 'ASC'))`
    - Then sub-sort by name: `orderBy('name', 'asc')`.
- Paginate the final query (10 records per page).

### 3. UI/UX implementation in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php)
- **Filters Section:**
  - Create a responsive card/section above the table.
  - Form fields:
    - Search text input (`wire:model.live.debounce.300ms="search"`).
    - Select dropdown for Status (`wire:model.live="status"`).
- **Sortable Columns:**
  - Update table headers for "Nombre" and "Estado" to be clickable buttons (`wire:click="sortBy('name')"` and `wire:click="sortBy('status')"`).
  - Render an indicator icon (arrow up/down) showing current sort status.
- **Table Rows:**
  - Display the "Estado" column using colored badges:
    - `Activo` (Green/Lime badge) when `deleted_at` is null.
    - `Archivado` (Gray badge) when `deleted_at` is not null.
  - If a laboratory is archived (`deleted_at` is not null), show a "Restaurar" (Restore) button instead of the "Editar" and "Eliminar" buttons.
- **Empty State:**
  - If `$laboratories->isEmpty()`, display a card with: `"No se encontraron laboratorios que coincidan con los filtros aplicados"`.

### 4. Delete Action in LaboratoryService
- In [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php), verify:
  - `delete(Laboratory $laboratory): void`: Throws a validation exception if there are active medicines associated with it. Otherwise, updates the `deleted_by` column with the authenticated user ID and calls `$laboratory->delete()`.
  - `restore(Laboratory $laboratory): void`: Clears the `deleted_by` column and calls `$laboratory->restore()`.

## Implementation Steps

1. **LaboratoryService Integration:**
   - Confirm `delete` and `restore` methods exist and are fully covered.
2. **Volt Component Setup:**
   - Expose state variables and reset pagination hooks (`updatingSearch()`, `updatingStatus()`).
   - Implement `sortBy(string $field)` for table sorting.
   - Implement `restoreLaboratory(int $id, LaboratoryService $service)` action.
   - Adjust `with()` to execute query logic.
3. **UI Layout Updates:**
   - Integrate filters in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/laboratories/index.blade.php).
   - Hook up table headers for name and status sorting.
   - Add status badges (Activo/Archivado).
   - Integrate restore action for soft-deleted laboratories.
4. **Pest Tests (`LaboratoryManagementTest.php`):**
   - Verify guest redirection.
   - Verify default load includes only active laboratories.
   - Test search by name (partial match).
   - Test status filter (active, archived, all).
   - Test sorting by name and by status.
   - Test restoring an archived laboratory.
   - Test empty state message.
