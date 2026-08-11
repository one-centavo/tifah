# 031 · Sales and Invoicing Process (HU 24)

**Status:** Draft

## What it does

Provides authorized users—primarily **Warehouse Assistants (Auxiliar de Bodega)** and **Administrators (Administrador)**—with a comprehensive point-of-sale and outbound invoicing system (`/sales/create` and `/bills`). 

The system enables users to:
1. Search and assign a registered customer by NIT/ID or business legal name.
2. Rapidly register medicines to the sale via barcode scanning or manual autocomplete search.
3. Automatically apply **FEFO (First Expired, First Out)** dispatch logic, prioritizing stock from lots with the earliest expiration dates while allowing manual lot overrides with row locking.
4. Auto-distribute requested quantities across multiple lots when a single batch has insufficient units.
5. Provide a quick modal to register missing medicines without navigating away from the sales screen.
6. Validate available batch quantities in real time and re-verify stock under concurrency controls upon final submission.
7. Support multiple payment methods (Cash, Bank Transfer, and Credit with maturity date and credit limit validation).
8. Compute line subtotals and invoice totals rounded to the nearest integer peso.
9. Execute atomic inventory deductions, logging user ID and exact timestamp.
10. Ensure immutable billing history (invoices can only be annulled, never deleted, with automatic stock restitution to original lots upon annulment).
11. Generate and download/print standardized PDF invoices containing all tax, customer, product, lot, and payment information.
12. Synchronize inventory levels and financial valuations in real time.

## Why

In pharmaceutical distribution, strict compliance with Good Storage and Distribution Practices (GSP/GDP) is mandatory. Selling medicines requires batch-level traceability to track expiration dates, enforce FEFO rotation to eliminate expired stock losses, prevent sales exceeding physical inventory, protect accounts receivable via credit limit enforcement, and provide auditable billing documents.

---

## Acceptance Criteria

### 1. Access Control
- Only authenticated users with the **Auxiliar de Bodega (Warehouse Assistant)** or **Administrador (Administrator)** role can create and process sales invoices.
- Unauthenticated users attempting to access `/sales/create` or `/bills` are redirected to the login view.

### 2. Customer Search & Selection
- The interface must provide a reactive search input allowing the user to find active clients by:
  - **NIT / Document Number**
  - **Razón Social (Commercial / Legal Name)**
- Selecting a customer binds their metadata (NIT, DV, Razón Social, delivery address, phone, credit status) to the current invoice transaction.
- Inactive or soft-deleted customers cannot be selected.

### 3. Product Identification (Barcode & Manual Search)
- **Barcode Scanner Support**: The primary input field is optimized for barcode scanners (auto-focusing and submitting on `Enter`). Scanning a barcode immediately identifies the associated medicine.
- **Manual Search**: Users can alternatively type the medicine's commercial name or generic name with debounce autocomplete.

### 4. Non-Existent Product Quick Access
- If a scanned barcode or searched name does not exist in the master medicine registry:
  - The system must display a clear warning/error message in Spanish: `"El medicamento no se encuentra registrado en el sistema."`
  - A direct action button/modal shortcut **"Registrar Medicamento"** is presented to register the new product on-the-fly without losing current cart state or leaving the sales screen.

### 5. FEFO Lot Availability & Highlight
- Upon selecting a medicine, the system queries all active lots with `current_quantity > 0` and non-expired status (`expiration_date >= today`).
- Lots are sorted chronologically by **Fecha de Vencimiento** (ascending).
- The lot with the closest expiration date is automatically highlighted to enforce the FEFO (First Expired, First Out) guideline.

### 6. Line Item Mandatory Fields
Each sales line item added to the invoice must contain and display:
- **Nombre del Producto** (Medicine commercial & generic name, concentration, presentation).
- **Número de Lote** (Selected batch number).
- **Fecha de Vencimiento** (Expiration date formatted `YYYY-MM-DD`).
- **Cantidad Solicitada** (Units to deliver).
- **Precio de Venta Unitario** (Unit selling price).
- **Valor Total del Renglón** (Calculated line subtotal: `quantity * unit_price`).

### 7. Lot Stock Limit Validation
- The requested quantity per lot cannot exceed the `current_quantity` of that specific batch.
- If a user inputs a quantity higher than available, the system immediately displays a validation error preventing the line registration.

### 8. Temporary Cart / Line Items Table
- Each added item is appended to a temporary interactive list on screen.
- The assistant can continuously scan and add more medicines.
- Any line item can be removed from the temporary list before confirmation with an instant recalibration of order totals.

### 9. Multi-Lot Auto-Allocation (Spillover Distribution)
- If the customer requires a quantity exceeding the stock of the closest expiring lot:
  - The system allocates the full available balance of the first expiring lot.
  - The remaining required units are automatically assigned to the next expiring lot(s) in sequential order.
  - If total stock across all active lots is insufficient, the system allocates all available stock and notifies the user of the unfulfilled shortfall.

### 10. Manual Lot Override & Row Locking
- The user may manually change the suggested batch or reassign quantities between available lots.
- Once a row is manually modified, it is flagged as locked (`is_locked = true`), preventing automatic FEFO recalculation from overriding the user's manual selection.

### 11. Price Loading & Manual Discount Override
- The default unit selling price is automatically populated from the medicine master record.
- The warehouse assistant can manually adjust the unit price on the line item to apply special commercial discounts.

### 12. Payment Method Selection
- Before completing the sale, the user must select a payment method:
  - **Efectivo (Cash)**
  - **Transferencia Bancaria (Bank Transfer)**
  - **Crédito (Credit)**

### 13. Credit Terms & Credit Limit Validation
- If **Crédito** is selected:
  - A **Fecha de Vencimiento de Pago (Payment Due Date)** is mandatory.
  - The system validates that the current invoice total plus the customer's existing pending credit balance does not exceed the customer's authorized credit limit (`credit_limit`).
  - If exceeded, the system blocks the credit sale with an error message: `"El cliente ha superado su límite de crédito autorizado."`

### 14. Real-time Concurrency & Final Stock Check
- When the user clicks the "Guardar Factura / Finalizar Venta" button:
  - The system opens a database transaction with pessimistic locking (`lockForUpdate`).
  - It re-verifies that every selected lot still contains the required physical quantity.
  - If a concurrent transaction depleted the stock in the meantime, the sale is aborted with an explanatory notification: `"Las existencias del lote [Lote] cambiaron durante la operación. Por favor revise el inventario."`

### 15. Automatic Totals & Integer Currency Rounding
- Subtotal, taxes (if applicable), and grand total are calculated automatically.
- Grand total is rounded mathematically to the nearest integer peso (`round($total, 0)`), preventing fractional centavo handling in financial and receipt outputs.

### 16. Transaction Confirmation, Immediate Inventory Deduction & Audit Logging
- Upon successful validation:
  - A new `Bill` record is saved with status `active`.
  - Corresponding `BillDetail` records are inserted.
  - The physical `current_quantity` of each referenced `Lot` is decremented immediately.
  - An `InventoryMovement` record is logged for each batch with type `sale` (salida), recording the quantity deducted, previous balance, and new balance.
  - The authenticated user's ID is stored in `created_by`, with the exact timestamp.

### 17. Non-Deletable Invoices (Annulment Only)
- Saved invoices cannot be physically deleted from the database (`bills` cannot be deleted).
- If an invoice is erroneous, authorized users can change its status to **`annulled` (Anulada)** with a required justification reason.

### 18. Stock Restitution on Invoice Annulment
- When an invoice transitions to `annulled`:
  - The quantities from each `BillDetail` are automatically returned to their original `Lot` records (`current_quantity += quantity`).
  - An `InventoryMovement` entry with type `annulment_return` (reingreso por anulación) is created for full audit traceability.
  - The user who annulled the invoice and the annulment timestamp are recorded.

### 19. Quantity Completeness Check & Auto-Resolve
- Prior to saving, the system checks whether the sum of units across all assigned batches equals the customer's requested total quantity per medicine.
- If a shortfall exists, the system warns the user and provides an **"Autocompletar Lotes Faltantes"** button to automatically search and allocate remaining units from other available batches.

### 20. PDF Invoice Generation & Printing
- Upon finishing the sale, the user can download or print a standardized PDF invoice containing:
  - Distributor/Company Name, NIT, Address, and Contact Info.
  - Invoice Number and Emission Date/Time.
  - Customer Full Details (Razón Social, NIT-DV, Address, Phone, City).
  - Itemized table: Medicine Name, Batch Number (`Lote`), Expiration Date (`Vence`), Quantity, Unit Price, Line Total.
  - Payment Method, Due Date (if Credit), and Grand Total.
  - Operator/Cashier Identification.

### 21. Real-time Inventory & Valuation Synchronization
- Every confirmed sale or annulment immediately updates:
  - Active stock counts in the Level 1 and Level 2 Inventory views.
  - Total warehouse stock monetary valuation based on remaining lot unit purchase prices.

---

## Out of Scope
- Direct electronic invoicing API synchronization with DIAN webservices (CUFE/QR code integration handled in separate fiscal module).
- Point-of-sale card payment terminal (datáfono) hardware integration.
- Partial returns / credit notes for single line items (handled in dedicated returns module).
