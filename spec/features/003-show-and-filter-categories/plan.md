# 003 · Show and Filter Categories — Plan

## Approach

We will enhance the existing Category Management component (`App\Livewire\Categories\Index` Volt component in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/categories/index.blade.php)) to implement the search, filtering, and sorting logic.

### 1. State Variables in Livewire Volt Component
- `$search` (string) for partial name matching.
- `$coldChain` (string: `'all'`, `'yes'`, `'no'`) to filter by `is_cold_chain`. Default: `'all'`.
- `$specialControl` (string: `'all'`, `'yes'`, `'no'`) to filter by `is_special_control`. Default: `'all'`.
- `$status` (string: `'active'`, `'archived'`, `'all'`) to filter by soft delete state. Default: `'active'`.
- `$sortField` (string: `'name'`, `'status'`) to define the current sorting column. Default: `'name'`.
- `$sortDirection` (string: `'asc'`, `'desc'`) to define the sorting direction. Default: `'asc'`.

### 2. Query Building in `with()` block
Inside the `with()` array provider of [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/categories/index.blade.php):
- Start with the query on the `Category` model.
- Conditionally apply soft-delete status scope based on `$status`:
  - `'active'` (default) -> normal query (only active/non-deleted categories).
  - `'archived'` -> use `onlyTrashed()`.
  - `'all'` -> use `withTrashed()`.
- Apply search condition if `$search` is not empty:
  - `where('name', 'like', '%' . $this->search . '%')`.
- Apply cold chain filter if `$coldChain` is not `'all'`:
  - `'yes'` -> `where('is_cold_chain', true)`
  - `'no'` -> `where('is_cold_chain', false)`
- Apply special control filter if `$specialControl` is not `'all'`:
  - `'yes'` -> `where('is_special_control', true)`
  - `'no'` -> `where('is_special_control', false)`
- Apply sorting:
  - If `$sortField` is `'name'`: `orderBy('name', $this->sortDirection)`.
  - If `$sortField` is `'status'`: Sort by `deleted_at` to distinguish between active and archived:
    - Active first: `orderByRaw('deleted_at IS NULL ' . ($this->sortDirection === 'asc' ? 'DESC' : 'ASC'))` (since `deleted_at IS NULL` evaluates to 1 for active and 0 for archived in MySQL, ordering by it descending places active first).
    - Then sub-sort by name: `orderBy('name', 'asc')`.
- Paginate the final query (10 records per page).

### 3. UI/UX implementation in [index.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/categories/index.blade.php)
- **Filters Section:**
  - Create a responsive card/section above the table.
  - Form fields:
    - Search text input (`wire:model.live.debounce.300ms="search"`).
    - Select dropdown for Cold Chain (`wire:model.live="coldChain"`).
    - Select dropdown for Special Control (`wire:model.live="specialControl"`).
    - Select dropdown for Status (`wire:model.live="status"`).
- **Sortable Columns:**
  - Update table headers for "Nombre" and "Estado" to be clickable buttons (`wire:click="sortBy('name')"` and `wire:click="sortBy('status')"`).
  - Render an indicator icon (arrow up/down) showing current sort status.
- **Table Rows:**
  - Display the "Estado" column using colored badges:
    - `Activa` (Green/Lime badge) when `deleted_at` is null.
    - `Archivada` (Gray/Red badge) when `deleted_at` is not null.
  - If a category is archived (`deleted_at` is not null), show a "Restaurar" (Restore) button instead of the "Eliminar" (Archive/Delete) button.
- **Empty State:**
  - If `$categories->isEmpty()`, display a card with: `"No se encontraron categorías que coincidan con los filtros aplicados"`.

### 4. Restore Action in CategoryService
- In [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php), implement:
  - `restore(Category $category): void`: Clears the `deleted_by` column and calls `$category->restore()`.

## Implementation Steps

1. **CategoryService Setup:**
   - In [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php), write `restore(Category $category): void` function.
2. **Volt Component Setup:**
   - Add state variables, update hooks (e.g. `updatingSearch()` to reset pagination).
   - Implement `sortBy(string $field)` toggling direction.
   - Implement `restoreCategory(int $id, CategoryService $service)` action.
   - Adjust `with()` to execute query logic.
3. **UI Layout Updates:**
   - Design layout for filters.
   - Hook up table headers for sorting.
   - Add status badges.
   - Integrate restore buttons for archived categories.
4. **Pest Tests (`CategoryManagementTest.php`):**
   - Verify guest redirection.
   - Verify default load includes only active categories.
   - Test search by name (partial and case-insensitive).
   - Test cold chain filter.
   - Test special control filter.
   - Test status filter (active, archived, all).
   - Test sorting by name and by status.
   - Test restoring an archived category.
   - Test empty state message.
