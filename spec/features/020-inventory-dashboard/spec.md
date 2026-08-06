# 020 · Inventory (HU 26)

**Status:** Completed

## What it does

Provides a consolidated, high-level Inventory page at `/inventory` (`inventory.index`) under the default **Inventario** tab, where Warehouse Assistants can view a summary of all medicines, their total accumulated stock across all active batches, and their active lot counts. It includes a search bar to filter medicines by name or barcode, and a "View Detail" (`Ver Detalle`) action that redirects the user to the Level 2 page showing the lot breakdown for the selected medicine. This view is strictly focused on stock management and does not display any sales prices.

## Why

To allow Warehouse Assistants to have a panoramic, real-time overview of current stock levels for all medicines in one centralized place, without needing to inspect individual batches or navigate details of each product. This streamlines inventory counting, identification of low-stock items, and general warehousing operations.

## Acceptance criteria

### 1. Consolidated Inventory Table (Level 1)
The primary tab "Inventario" must display a table listing all medicines with the following columns:
- **Commercial Name** (`Nombre Comercial`)
- **Generic Name** (`Nombre Genérico`)
- **Concentration** (`Concentración`)
- **Total Stock (Units)** (`Stock Total (Unidades)`)
- **Active Lots Count** (`Cantidad de Lotes Activos`)
- **Actions** containing a "View Detail" (`Ver Detalle`) link/button.

### 2. Automatic Total Stock Calculation
- The **Total Stock** for each medicine must be calculated dynamically as the sum of `current_quantity` of all associated active, non-deleted lots.
- Lots that are blocked, damaged, or soft-deleted must be excluded from this sum.

### 3. Active Lots Count
- The **Active Lots Count** must indicate the number of active, non-deleted batch/lot records associated with that medicine.
- Closed, expired, or soft-deleted lots are excluded from this count (only active lots are counted).

### 4. Search and Filtering
- A search bar must be present on the page.
- Users must be able to search/filter the list of medicines dynamically by their **Commercial Name**, **Generic Name**, or **Barcode**.

### 5. Detail Navigation (Level 2 Redirect)
- Clicking the "View Detail" (`Ver Detalle`) button for a medicine must redirect the user to Level 2 (Lots List page) showing the detailed list of batches/lots specifically for that medicine at `/inventory/medicines/{medicine}/lots`.

### 6. Exclusion of Sales Prices
- To maintain the focus entirely on physical stock management, the consolidated inventory table and the Level 2 lots table must **not** display sales prices or any pricing details.

## Out of scope
- Bulk uploading categories or medicines.
- Exporting the summary list to CSV/Excel (handled in a separate report feature).
