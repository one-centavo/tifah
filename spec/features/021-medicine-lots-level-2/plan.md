# 021 · Medicine Lots Level 2 — Plan

## Approach

We will upgrade the existing Level 2 component at `/inventory/medicines/{medicine}/lots` (`inventory.medicine-lots`) to fully satisfy the HU 28 specifications. This includes implementing table sorting capabilities on `expiration_date` and `reception_date`, calculating and rendering the remaining days to expire with corresponding visual alerts, exposing the entry date, rendering the total physical stock in the table footer, and providing a link to navigate to the Level 3 logs/movements page.

---

### 1. Livewire Volt Component Updates (`resources/views/livewire/inventory/medicine-lots.blade.php`)

The Level 2 page will manage listing, sorting, deleting, and navigation hooks.

- **State Properties:**
  - `public string $sortField = 'expiration_date';`
  - `public string $sortDirection = 'asc';`
  - Deletion modal fields: `$lotIdBeingDeleted`, `$lotBatchBeingDeleted`.

- **Component Methods:**
  - `sortBy(string $field)`: Toggles `$sortDirection` between `'asc'` and `'desc'` if the clicked field is already active, otherwise resets `$sortField` to the new field and defaults `$sortDirection` to `'asc'`. Only `'expiration_date'` and `'reception_date'` are allowed fields.
  - `confirmLotDeletion(int $id)`: Handles modal triggers for batch deletion.
  - `deleteLot(LotService $lotService)`: Performs the soft delete and records the audit trails.

- **Query Optimization (`with()` method):**
  - Retrieve active lots for the medicine, applying:
    ```php
    $lots = $this->medicine->lots()
        ->with(['purchaseOrder.supplier'])
        ->orderBy($this->sortField, $this->sortDirection)
        ->get();
    ```

---

### 2. UI Layout Architecture

- **Context Header:**
  - Display the selected medicine info card containing: `name` (Commercial Name), `generic_name` (Generic Name), and `presentation` (Presentation).

- **Lots Table Headers & Sorting Icons:**
  - Make `Fecha de Vencimiento` and `Fecha de Ingreso` headers interactive:
    ```html
    <button wire:click="sortBy('expiration_date')" class="inline-flex items-center gap-1">
        <span>Fecha de Vencimiento</span>
        <!-- SVG icons for sort direction (up/down/unsorted) -->
    </button>
    ```

- **Calculations & Visual Expiration Alerts:**
  - Calculate remaining days in Blade/PHP:
    ```php
    $daysLeft = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date)->startOfDay(), false);
    ```
  - Map days left to alert categories and Tailwind colors:
    - **Red Alert (Rojo):** `daysLeft < 0` (Expired) or `daysLeft <= 30` (Critical). Use classes like `bg-red-50 text-red-700 border-red-100` or `text-red-600 font-bold`.
    - **Orange Alert (Naranja):** `daysLeft > 30` and `daysLeft <= 90`. Use classes like `bg-orange-50 text-orange-700 border-orange-100` or `text-orange-600 font-bold`.
    - **Yellow Alert (Amarillo):** `daysLeft > 90` and `daysLeft <= 180`. Use classes like `bg-yellow-50 text-yellow-700 border-yellow-100` or `text-yellow-600 font-bold`.
    - **Safe / Standard:** `daysLeft > 180`. Plain display text with no warning highlighting.

- **Level 3 Redirection link:**
  - Under the actions column, add a link button labeled `"Ver Historial"` or `"Ver Logs"` leading to the Level 3 route.
  - Route placeholder: `route('inventory.lots.logs', $lot)` (naming mapping to HU 27).

- **Stock Summary (Table Footer):**
  - Sum the total available stock:
    ```php
    $totalStock = $lots->sum('current_quantity');
    ```
  - Render a `<tfoot>` section with the label `"Total de Existencias Físicas"` and render the calculated `$totalStock`.

---

### 3. Routing & Sidebar Navigation

- Level 2 route is already registered in `routes/web.php`:
  `Volt::route('inventory/medicines/{medicine}/lots', 'inventory.medicine-lots')->name('inventory.medicine-lots');`
- Level 3 route will be mapped to `routes/web.php` (when HU 27 is defined/coded) as:
  `Volt::route('inventory/lots/{lot}/logs', 'inventory.lot-logs')->name('inventory.lots.logs');`

---

### 4. PestPHP Testing Strategy

Verify that the requirements are met with automated tests:
1. **Initial Sorting Check:** Verify that lots are sorted by `expiration_date` ascending by default.
2. **Interactive Header Click Sorting:** Assert that calling `sortBy('reception_date')` or `sortBy('expiration_date')` alters the query order.
3. **Countdown Days Display:** Assert that remaining days until expiration are calculated correctly (positive, zero, or negative).
4. **Visual Alert Thresholds:** Test that the correct CSS classes are outputted for expired/critical (Red), warning (Orange), alert (Yellow) and safe lots.
5. **Stock Summary Verification:** Verify that the sum of `current_quantity` matches the value displayed in the table footer.
6. **Level 3 Link Presence:** Check that the `"Ver Historial"` action link pointing to the Level 3 route exists for each active lot row.
