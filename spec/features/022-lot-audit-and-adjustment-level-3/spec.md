# 022 · Lot Audit and Adjustment Level 3 (HU 30)

**Status:** Completed

## What it does

Allows Warehouse Assistants and Administrators to access a detailed audit log ("Kardex" / movements history) for a specific lot. The screen lists all historical movements (such as initial reception, sales, and corrections) in a specific order: each adjustment movement is displayed directly below the original movement it compensates.

Instead of modifying the total stock of a lot globally, users correct a **specific movement** (e.g. correcting a digitizing error on a particular reception entry). The system obliges creating a new compensating transaction of type "adjustment" referencing the original movement's ID via `reference_id`, ensuring the original remains unchanged and the audit history is fully preserved. Access to perform adjustments is strictly restricted: only users with the **Administrator (Administrador)** role can execute corrections.

## Why

To comply with pharmaceutical regulations requiring strict traceability of drug batches. Every inventory modification must be recorded transparently, preventing database edits or deletions of previous logs, and ensuring that any stock discrepancy is resolved with a clear, justified, and authorized compensating entry associated with the exact erroneous transaction.

## Acceptance criteria

### 1. Access Control
- Access to the lot movements page is restricted to authenticated users with **Warehouse Assistant (Auxiliar de Bodega)** or **Administrator (Administrador)** roles.

### 2. Navigation & Context
- Accessible at the route `/inventory/lots/{lot}/logs` (`inventory.lots.logs`).
- Display context information of the lot at the top:
  - Batch number (`Número de Lote`)
  - Commercial and generic name of the medicine
  - Expiration date
  - Current physical quantity
- Provide a back button to return to the medicine's lot level 2 view (`/inventory/medicines/{medicine}/lots`).

### 3. Movements Log (Kardex Table) & Grouped Order
- Displays all `inventory_movements` associated with the lot.
- **Sorting Requirement:** Each adjustment movement must appear **directly below** the original movement it corrects.
  - Sorted primarily by the parent movement's ID (`CASE WHEN type = 'adjustment' THEN reference_id ELSE id END`) ascending, and secondarily by `id` ascending.
- For each movement, the table must display:
  - Exact timestamp (date and time) of the operation
  - Quantity changed (e.g., `+50`, `-1`)
  - Previous balance and new balance
  - Detailed Concept (e.g. `"Registro Inicial"`, `"Ajuste por Error - Error de digitación"`, `"Salida por Venta"`)
  - Observations (for adjustments, showing the observations text; for others, showing reference tags)
  - Name and ID of the user who performed the operation
  - Action column containing an **"Ajustar"** button for eligible row.

### 4. Editing Rule (Compensating Adjustment on a Specific Log)
- Modifying or deleting existing movements is strictly forbidden.
- To correct a specific movement's quantity:
  - The user clicks **"Ajustar"** on the target movement row.
  - The system creates a new transaction of type `adjustment` referencing the original movement's ID via `reference_id`.
  - The difference `quantityDiff = newQuantity - originalMovementQuantity` is calculated and saved as the movement's `quantity`.
  - The lot's `current_quantity` must be updated by adding the `quantityDiff`.

### 5. Adjustment Form & Validation
- The adjustment modal requests:
  - **Cantidad Corregida (New Quantity):** Numeric, integer, minimum 0.
  - **Motivo del Ajuste (Reason):** Dropdown select list:
    - `Error de digitación`
    - `Unidad dañada`
    - `Unidad faltante`
    - `Otro`
  - **Observaciones adicionales:** Text area, mandatory, explaining the error details.
- When the adjustment is saved:
  - An `inventory_movements` record of type `adjustment` is created.
  - The `previous_balance` is set to the current lot quantity, and `new_balance` is set to `currentQuantity + quantityDiff`.
  - The concept is stored as: `"Ajuste de cantidad del movimiento #[ID]"`.
  - The `adjustment_reason` stores the reason, and `observations` stores the description text.
  - The lot's `current_quantity` is updated by adding the difference.

### 6. Restricción de Acceso (Security)
- The **"Ajustar"** action on rows is visible and enabled **only** for users with the **Administrator** role.
- Warehouse Assistants will see "Sólo Admin" in the Actions column and cannot trigger or submit corrections.
