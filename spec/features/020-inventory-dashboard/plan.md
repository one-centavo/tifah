# 020 · Inventory — Plan

## Approach

We will replace the general "Control de Lotes" tab on `/inventory` (`inventory.index`) with a consolidated medicines list (Level 1) under the default tab "Inventario". We will retain the "Recepción de Mercancía" tab as it is for scanning and entering new merchandise lots. We will implement Level 2 (Lots list for a specific medicine) under `/inventory/medicines/{medicine}/lots` and move the lot deletion functionality (soft delete with modal confirmation) to this page to avoid redundancy.

---

### 1. Tabbed Volt Component (`App\Livewire\Inventory\Index`)

The main component `inventory.index` will manage the tabs, searching, consolidated medicine list, and merchandise reception forms.

- **State Properties:**
  - `$activeTab = 'consolidated';` (Options: `'consolidated'` for the consolidated overview, `'reception'` for shipment entries).
  - **Reception Form State:** Keep barcode, selected medicine details, temporary lots, and supplier registration inputs.
  - **Consolidated Tab State:**
    - `$search = '';`
    - Pagination is supported. Reset page hooks will trigger when searching or switching tabs.

- **Component Methods:**
  - `switchTab(string $tab)`: Toggles between `'consolidated'` and `'reception'`, preserving reception forms.
  - `updatedBarcode()`: Triggers barcode lookup for merchandise entry.
  - `addToTemporaryList()`, `editTemporaryLot()`, `removeTemporaryLot()`, `confirmReception()`, `cancelReception()`, `saveQuickSupplier()`: Retain existing business logic.

---

### 2. Level 2 Lots Component (`App\Livewire\Inventory\MedicineLots`)

The Level 2 page displays the lot breakdown specifically for the selected medicine.

- **State Properties:**
  - `public Medicine $medicine;`
  - Deletion modal fields: `$lotIdBeingDeleted`, `$lotBatchBeingDeleted`.

- **Component Methods:**
  - `confirmLotDeletion(int $id)`: Dispatches the confirmation modal.
  - `deleteLot(LotService $lotService)`: Performs the soft delete and records the audit trails.

---

### 3. UI Layout Architecture

- **Level 1 (consolidated tab):**
  - Search bar.
  - Table: Commercial Name, Generic Name, Concentration, Total Stock (Units), Active Lots Count, Actions.
  - "Ver Detalle" redirects to Level 2 lots list.
  - No sales prices are displayed.
- **Level 2 (medicine-lots view):**
  - Back button pointing to `/inventory`.
  - Selected medicine metadata card (Generic Name, Concentration, Presentation).
  - Table: Batch Number, Expiration Date, Current Quantity, Initial Quantity, Unit Purchase Cost, Supplier, Status, Actions (Delete/Eliminar).
  - Confirmation Modal: Delete confirmation displaying batch details.

---

### 4. Routing & Sidebar Navigation

- Sidenav pointing to `/inventory` (`inventory.index`) remains unchanged.
- Register Level 2 route in `routes/web.php` inside the auth group:
  - `Volt::route('inventory/medicines/{medicine}/lots', 'inventory.medicine-lots')->name('inventory.medicine-lots');`

---

### 5. PestPHP Testing Strategy

We will update feature tests in `tests/Feature/Pages/Inventory/InventoryManagementTest.php`:
- **Consolidated View:** Verifies correct calculations of Total Stock and Active Lots Count, and dynamic search works.
- **Level 2 Page:** Verifies guest redirects, lots listing details (batch, expiration, quantity, supplier), and soft-deleting a lot.
- **Price Exclusion:** Asserts that sales prices are not rendered on Level 1 or Level 2 pages.
- **Merchandise Entry:** Verifies reception forms and database persistence logic work.
