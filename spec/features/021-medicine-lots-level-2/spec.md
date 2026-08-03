# 021 · Medicine Lots Detail Level 2 (HU 28)

**Status:** Completed

## What it does

Allows Warehouse Assistants and Administrators to view a detailed breakdown of all active, non-deleted batches (lots) for a specific medicine. This Level 2 detail screen display is triggered from the Level 1 Inventory Dashboard via the "Ver Detalle" option. The screen provides crucial tracking data, showing the medicine's master info, batch numbers, entry and expiration dates, dynamic countdown of days until expiry with color-coded warning alerts (Red, Orange, Yellow), and available physical quantities. It also calculates a final stock summary at the foot of the table and includes actions to delete a batch (soft-delete) or navigate to Level 3 logs to inspect the full movement history of a specific lot.

## Why

To give warehouse personnel and administrators granular, real-time control over physical stock batches. This ensures they can easily check entry dates, monitor upcoming expiration dates to minimize financial losses, track statuses (Active, Blocked, Damaged), and audit movement histories, maintaining strict traceability across the supply chain.

## Acceptance criteria

### 1. Access Control
- Access is restricted to authenticated users with **Warehouse Assistant (Auxiliar de Bodega)** or **Administrator (Administrador)** roles.

### 2. Access Trigger (Navigation from Level 1)
- This view is rendered at the route `/inventory/medicines/{medicine}/lots` (`inventory.medicine-lots`).
- It is displayed when the user clicks the **"Ver Detalle"** link/button on any medicine row in the consolidated Inventory Dashboard (HU 26 / Feature 020).
- A clear **"Volver al Inventario"** (Back to Inventory) button must be present to return to `/inventory`.

### 3. Context Header
- The top section of the screen must display the medicine's master metadata to keep the user oriented:
  - **Commercial Name** (`Nombre Comercial`)
  - **Generic Name** (`Nombre Genérico`)
  - **Presentation** (`Presentación`)

### 4. Lots Table Columns
The page must list active batches associated with the medicine in a structured table containing the following mandatory columns:
- **Número de Lote** (Batch/Lot Number)
- **Fecha de Ingreso** (Entry/Reception Date)
- **Fecha de Vencimiento** (Expiration Date)
- **Días para Vencer** (Days to Expire): A dynamically calculated label showing the remaining days (e.g., `"15 días"`, `"Vencido hace 3 días"`).
- **Cantidad Disponible** (Available Quantity): Displays the `current_quantity` of the lot.
- **Estado** (Status): Badge showing either `Activo` (Active), `Bloqueado` (Blocked), or `Dañado` (Damaged).
- **Acciones** (Actions): Contains navigation to Level 3 and deletion actions.

### 5. Sorting Behavior
- The table records must be sortable by:
  - **Fecha de Vencimiento** (Expiration Date)
  - **Fecha de Ingreso** (Entry Date)
- The sorting is triggered by clicking on the respective column headers.
- **Default sorting:** Sorted by **Fecha de Vencimiento** in ascending order (closest to expiration first).

### 6. Visual Expiration Alerts
Based on the remaining days until expiration, rows or status badges must highlight batches using a color-coded warning system:
- **Red (Rojo):** Expired batches (remaining days < 0) or critical window (0 to 30 days remaining).
- **Orange (Naranja):** Near expiration (31 to 90 days remaining).
- **Yellow (Amarillo):** Moderate warning (91 to 180 days remaining).
- **Default/No Highlight:** Safe batches (> 180 days remaining).

### 7. Level 3 Logs Navigation
- Under the **Acciones** column, there must be a button or link labeled **"Ver Historial"** or **"Ver Logs"**.
- Clicking this redirects the user to the Nivel 3 view (**HU 27**) at `/inventory/lots/{lot}/logs` (or similar) to consult the audit log and movement history of that specific batch.

### 8. Physical Stock Summary (Footer)
- At the foot of the table, a summary row must display the **"Total de Existencias Físicas"** (Total Physical Stock).
- This is the sum of the `current_quantity` of all batches currently shown in the table for this medicine.

### 9. Lot Deletion
- Contains a deletion action that triggers a modal to confirm the soft-delete of the batch (preserving audit history but removing it from active saleable stock).

## Out of Scope
- Registering a new batch directly on this page (handled under Merchandise Reception in HU 12).
- Printing physical barcode or batch labels from this view.
