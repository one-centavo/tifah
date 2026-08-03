# 019 · Inventory Management & Lot Reception (HU 12)

**Status:** Draft

## What it does

Allows Warehouse Assistants and Administrators to manage inventory lots and register incoming shipments in a single, consolidated **Inventario** page. The interface contains a tab-based layout:
1. **Control de Lotes (Lot Control):** A table displaying active inventory batches, their current stock, statuses, and allowing logical soft deletes for tracing.
2. **Recepción de Mercancía (Merchandise Reception):** A dynamic form for scanning/typing barcodes, entering batch numbers, expiration dates, unit purchase costs, quantities, and suppliers. It supports loading multiple items into a temporary summary table, calculating subtotals, checking profit warning margins, and registering new suppliers or medicines on the fly.

Both control and reception operations are housed under the single route `/inventory`.

## Why

To simplify warehouse administration by centralizing all lot operations, preventing the entrance of expired products, monitoring margins, and keeping a comprehensive audit trail of inventory entries and logical deletions in one place.

## Acceptance criteria

### 1. Unified Sidenav and Route Integration (Inventory Section)
- The entire feature must be accessible at the route `/inventory` (`inventory.index`).
- The sidebar navigation layout must include a single **Inventario** menu link pointing to `/inventory`.

### 2. Tabbed UI Layout
The `/inventory` dashboard must present two clear tabs:
- **"Control de Lotes" (Default Tab):** Lists active lot information.
- **"Recepción de Mercancía":** Contains the entry interface.
Switching tabs must preserve the temporary state of the merchandise reception form and list so the user does not lose unsaved progress when verifying current stock.

### 3. Barcode Scanning and Medicine Discovery (Reception Tab)
- The merchandise reception tab must show a **Código de Barras (Barcode)** input field.
- Upon barcode scanning or manual entry:
  - If the barcode matches an existing medicine, the system must auto-populate the product details (Commercial Name, Generic Name, Sale Price) and set focus to the batch registration form.
  - If the barcode does not exist, the system must display a button: `"Registrar nuevo medicamento"`. Clicking this opens the medicine registration screen in a new window/tab to avoid closing the current reception state.

### 4. Lot Details Form and Fields Validation
The registration form in the reception tab must validate the following fields:
- **Producto (Product):** Required. Must show the name of the medicine.
- **Número de Lote (Batch/Lot Number) (`batch_number`):** Required. Must allow alphanumeric characters (letters and numbers). Cannot be empty.
- **Fecha de Vencimiento (Expiration Date) (`expiration_date`):** Required. Must be a valid date.
  - **Expiry Check:** The system must prevent entering products that are already expired by comparing `expiration_date` with the current server date. If expired, prevent adding to the list and show an error message: `"No se pueden ingresar productos vencidos"`.
- **Cantidad Recibida (Quantity Received) (`initial_quantity`):** Required. Must be an integer greater than zero.
- **Costo de Compra Unitario (Unit Purchase Price) (`unit_purchase_price`):** Required. Must accept positive decimal numbers.
- **Fecha de Recepción (Reception Date) (`reception_date`):** Required. Must default to the current system date but allow manual selection of a past date (in case the shipment arrived earlier).
- **Proveedor (Supplier) (`supplier_id`):** Required. Selected from a dropdown list of registered suppliers.

### 5. Quick Supplier Registration
- Adjacent to the supplier dropdown, a quick-action button must allow registering a new supplier.
- This button triggers a simple modal containing:
  - **NIT** (Required, unique)
  - **DV** (Required, single digit)
  - **Nombre / Razón Social** (Required)
  - **Persona de Contacto** (Required)
  - **Teléfono** (Required)
  - **Correo Electrónico** (Required, email format)
  - **Dirección** (Required)
- Upon submission, the new supplier is saved, the modal closes, and the supplier is automatically selected in the dropdown.

### 6. Calculations and Margin Warning
- **Total Calculation:** The screen must dynamically calculate the total value of the batch row (`quantity * unit_purchase_price`) and display it in real time before the item is added to the summary table.
- **Profitability Warning:** If the unit purchase cost entered is greater than the medicine's configured sales price (`selling_price`), the system must display a warning message: `"Advertencia: El costo de compra es mayor al precio de venta asignado ($XX.XX)"`. This warns the user of potential losses but does not block them from adding the item.

### 7. Duplicate Merging in Temporary List
- If the user attempts to add a product + batch number combination that *already exists* in the temporary summary table:
  - The system must not create a new row.
  - It must sum the new quantity to the existing row's quantity and update its total purchase value dynamically.

### 8. Temporary Table Operations
- The summary table must list: Medicine, Lot, Expiration Date, Quantity, Unit Cost, Total Cost, Supplier, Status.
- Each row in the summary table must provide two actions:
  - **Editar (Edit):** Load the row's values back into the form fields to allow correction, and temporarily remove it from the summary table.
  - **Borrar (Delete):** Remove the item entirely from the temporary list.

### 9. Final Persistence and Stock Update
- Clicking **"Confirmar Ingreso" (Confirm Entry)** will trigger database operations:
  - Verify the list is not empty.
  - Write all records in a single database transaction.
  - Save each batch as a new record in the `lots` table.
  - Set `current_quantity` and `initial_quantity` to the received quantity.
  - Assign the authenticated user ID to the `created_by` column.
  - Update the dynamic total stock for each affected medicine.
  - Show a success message: `"Ingreso de mercancía registrado con éxito"`.
  - Clear the temporary list and reset the form.

### 10. Default Lot Status and Custom Status Options
- Newly registered lots are automatically set to `"active"` by default, making them immediately available for sale.
- During the registration phase, the user can override the status dropdown to select `"blocked"` or `"damaged"` if they detect visual damage, packaging issues, or logistical problems.

### 11. Auditable Logical Deletion of Batches (Control Tab)
- In the "Control de Lotes" tab, users with appropriate roles can delete lots.
- Deleting a lot must perform a logical soft delete (using Laravel's `SoftDeletes` trait on the `lots` table).
- The system must automatically record:
  - The ID of the authenticated user in the `deleted_by` column.
  - The date/time of deletion in `deleted_at`.
- Deleted lots are hidden from the stock lists and inventory calculations but preserved in the database for future audits.

## Out of Scope
- Integration with external physical barcode printers.
- Automatic purchase order matching.
