# 021 · Medicine Lots Level 2 — Tasks

## Livewire Volt Component (Backend)
- [x] Add `$sortField` and `$sortDirection` state properties to the `inventory.medicine-lots` component class.
- [x] Implement `sortBy(string $field)` method to toggle sorting direction or set a new sorting column.
- [x] Update the `with()` method query to dynamically apply sorting by `$sortField` and `$sortDirection` on the `lots` relationship.

## Blade Template & UI/UX (Frontend)
- [x] Render the medicine's master metadata (Commercial Name, Generic Name, Presentation) in the context header card.
- [x] Add sorting triggers and dynamic icons (chevron up/down) to the table headers for:
  - [x] **Fecha de Vencimiento** (Expiration Date)
  - [x] **Fecha de Ingreso** (Entry Date)
- [x] Implement the **Fecha de Ingreso** column mapping `reception_date` for each lot.
- [x] Implement the **Días para Vencer** column:
  - [x] Calculate the difference in days from today to `expiration_date`.
  - [x] Render it as a clear text label (e.g., `"15 días"`, `"Vencido hace 3 días"`).
  - [x] Highlight the text or badge using warning alert colors:
    - [x] **Red (Rojo):** Expired or <= 30 days left.
    - [x] **Orange (Naranja):** 31 to 90 days left.
    - [x] **Yellow (Amarillo):** 91 to 180 days left.
    - [x] **None:** > 180 days left.
- [x] Add the **Ver Historial** or **Ver Logs** link in the actions column pointing to the Level 3 route (`/inventory/lots/{lot}/logs`).
- [x] Implement the table footer summary row (`<tfoot>`):
  - [x] Calculate the total of all lot quantities shown.
  - [x] Display `"Total de Existencias Físicas"` along with the sum.

## Automated Testing & Validation
- [x] Write/update feature tests in `tests/Feature/Pages/Inventory/InventoryManagementTest.php` (or a dedicated test file):
  - [x] Verify default sorting order by expiration date.
  - [x] Verify sorting by entry date works when requested.
  - [x] Test the remaining days warning color badge calculations for each threshold.
  - [x] Verify the total physical stock footer shows the correct sum.
  - [x] Verify the Level 3 action link is properly outputted.
- [x] Run the test suite: `docker compose exec app php artisan test`.
