# 019 · Merchandise Reception & Lot Registration (HU 12)

**Status:** Draft

## What it does

Allows Warehouse Assistants and Administrators to record incoming inventory shipments by registering products under specific lot (batch) numbers and expiration dates. To streamline warehouse workflows, the interface permits loading multiple products and their respective lot details into a temporary list before committing them to the database. The feature includes real-time barcode scanning validation, quick registration modals for unregistered medicines and suppliers, profitability warning systems, automatic inventory stock calculations, and auditable logical deletions. All batch/lot operations are housed under the Inventory section.

## Why

To guarantee strict product traceability, prevent the entry of expired products, automate stock replenishment, monitor purchase costs against sales prices, and maintain a complete audit trail of inventory entries and deletions.

## Acceptance criteria

### 1. Route and Sidenav Integration (Inventory Section)
- The merchandise reception interface must be accessible at the route `/inventory/reception` (`inventory.reception`).
- The lot management and history table must be accessible at the route `/inventory/lots` (`inventory.lots`).
- The sidebar navigation layout must include a collapsible or direct **Inventario** menu with links to "Recepción de Mercancía" and "Control de Lotes".

### 2. Barcode Scanning and Medicine Discovery
- The entry screen must present a **Código de Barras (Barcode)** input field.
- Upon barcode scanning or manual entry:
  - If the barcode matches an existing medicine (in the `medicine_barcodes` table), the system must auto-populate the product details (e.g., Commercial Name, Generic Name, Sale Price) and set focus to the batch registration form.
  - If the barcode does not exist, the system must show a prominent button: `"Registrar nuevo medicamento"`. Clicking this button must open the medicine registration screen or a modal to register the medicine *without closing or clearing the current merchandise reception state*.

### 3. Temporary List Formulation (Pre-commit Table)
- The system must provide a stateful temporary list (or summary table) on the screen.
- Users can fill in the lot details and click an **"Añadir a la lista" (Add to List)** button. This action adds the item to the temporary table only, postponing database persistence until the user clicks the final confirmation button.

### 4. Lot Details Form and Fields Validation
The registration form for each batch must validate the following fields:
- **Producto (Product):** Required. Must display the name of the medicine.
- **Número de Lote (Batch/Lot Number) (`batch_number`):** Required. Must allow alphanumeric characters (letters and numbers). Cannot be empty.
- **Fecha de Vencimiento (Expiration Date) (`expiration_date`):** Required. Must be a valid date.
  - **Expiry Check:** The system must prevent entering products that are already expired by comparing `expiration_date` with the current server date. If expired, prevent adding to the list and show an error message: `"No se pueden ingresar productos vencidos"`.
- **Cantidad Recibida (Quantity Received) (`initial_quantity`):** Required. Must be an integer greater than zero.
- **Costo de Compra Unitario (Unit Purchase Price) (`unit_purchase_price`):** Required. Must accept positive decimal numbers.
- **Fecha de Recepción (Reception Date) (`reception_date`):** Required. Must default to the current system date but allow manual selection of a past date (in case the shipment arrived earlier).
- **Proveedor (Supplier) (`supplier_id`):** Required. Selected from a dropdown list of registered suppliers.

### 5. Quick Supplier Registration
- Adjacent to the supplier dropdown, a quick-action button must allow registering a new supplier.
- This button triggers a simple modal/form containing:
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

### 11. Auditable Logical Deletion of Batches
- Inside the lot management list (`/inventory/lots`), users with appropriate roles can delete lots.
- Deleting a lot must perform a logical soft delete (using Laravel's `SoftDeletes` trait on the `lots` table).
- The system must automatically record:
  - The ID of the authenticated user in the `deleted_by` column.
  - The date/time of deletion in `deleted_at`.
- Deleted lots are hidden from the stock lists and inventory calculations but preserved in the database for future audits.

## Out of Scope
- Integration with external physical barcode printers (handled in a separate barcode print module).
- Automatic purchase order matching (this view handles direct, manual inventory entries).
