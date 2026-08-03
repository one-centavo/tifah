# 020 · Inventory Dashboard — Plan

## Approach

We will implement a new Livewire Volt component for the Inventory Dashboard (`resources/views/livewire/inventory/dashboard.blade.php`) representing the route `/inventory-dashboard`. Since `/inventory` currently maps to `inventory.index` which houses "Control de Lotes" and "Recepción de Mercancía", we will define a separate route `/inventory-dashboard` (`inventory.dashboard`) and the Level 2 lots breakdown under `/inventory/medicines/{medicine}/lots`.

---

### 1. Volt Component (`App\Livewire\Inventory\Dashboard`)

The component `inventory.dashboard` will handle searching and retrieving the list of medicines.

- **State Properties:**
  - `$searchTerm = '';`
  - Pagination is supported (e.g., `WithPagination` trait).
  - Reset pagination on updated search terms.

- **Data Querying:**
  - Query the `Medicine` model eager loading active `lots` (filtered by active status and not soft-deleted).
  - Calculate `total_stock` and `active_lots_count` using query aggregates or collection mapping for performance.
  - Search filter applies a query scope searching on `commercial_name`, `generic_name`, or the primary barcode.

---

### 2. UI Layout Architecture

The view `resources/views/livewire/inventory/dashboard.blade.php` will be styled with Tailwind CSS:
- **Header:** Title "Dashboard de Inventario" and sub-description.
- **Search Bar:** Simple input for dynamic search by name or barcode.
- **Table:** Columns: Commercial Name, Generic Name, Concentration, Total Stock (Units), Active Lots Count, Actions.
- **Actions:** A clear styled primary button/link "Ver Detalle" pointing to the Level 2 lots list.
- **No Pricing:** Ensure no price columns or billing information is rendered.

---

### 3. Level 2 (Lots List for Medicine) Route and Component
- We will define a Level 2 Volt component `inventory.medicine-lots` at `resources/views/livewire/inventory/medicine-lots.blade.php`.
- The route will be `/inventory/medicines/{medicine}/lots` (`inventory.medicine-lots`).
- This page displays the specific list of lots for the selected medicine, allowing the warehouse assistant to see the detailed breakdown.

---

### 4. Routing & Sidebar Navigation

- Add route `/inventory-dashboard` in `routes/web.php` inside the auth group:
  - `Volt::route('inventory-dashboard', 'inventory.dashboard')->name('inventory.dashboard');`
- Add route `/inventory/medicines/{medicine}/lots` in `routes/web.php` inside the auth group:
  - `Volt::route('inventory/medicines/{medicine}/lots', 'inventory.medicine-lots')->name('inventory.medicine-lots');`
- Update Sidenav:
  - Add a link to the layout sidebar pointing to the new inventory dashboard.

---

### 5. PestPHP Testing Strategy

We will write feature tests in `tests/Feature/Pages/Inventory/InventoryDashboardTest.php`:
- **Access Control:** Guest redirect, Warehouse Assistant allowed.
- **Dashboard Data:** Verifies the correct calculation of Total Stock (sum of active lot quantities) and Active Lots Count.
- **Search Logic:** Verifies filtering medicines by commercial name, generic name, or barcode.
- **Price Exclusion:** Asserts that the sales price is not displayed or present in the response content.
- **Navigation:** Verifies the "Ver Detalle" action redirects to the correct Level 2 route.
