# 016 · Detailed View of Medicine

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to view the complete technical sheet of a registered medicine. This view provides in-depth information not visible in the rapid search list, including legal fields, full INVIMA sanitary registration details, a complete list of associated barcodes for inventory reference check, full audit data (created by, creation date, updated by, modification date), and soft delete metadata (date of deletion and user who deleted), if applicable. All information displayed in this detailed view is read-only. It also provides quick actions to edit or archive the medicine if allowed.

## Why

Warehouse staff and administrators need access to the full legal, tracing, and inventory identification data of a medicine without leaving the dashboard or viewing search lists, ensuring traceability and operational efficiency.

## Acceptance criteria

### 1. Access Control
- [ ] Only authorized users (Administrators or Warehouse Assistants) can access the detailed view of a medicine.

### 2. Access Trigger
- [ ] The detailed view must be opened by selecting the "Ver Detalle" button on any medicine row in the catalog list (HU 17 - Visualización y Filtrado de Medicamentos).

### 3. Displayed Data (Core Specs)
- [ ] **Nombre Comercial:** Commercial name of the medicine.
- [ ] **Nombre Genérico:** Generic name or active ingredient.
- [ ] **Concentración:** Concentrated technical strength formatted (e.g., `500 mg`).
- [ ] **Presentación:** Automated presentation string (e.g., `Caja x 30 Tabletas`).
- [ ] **Categoría:** Associated category name.
- [ ] **Precio de Venta:** Formatted unit selling price (e.g., `$15,200.00`).
- [ ] **Stock Actual / Alerta:** Sum of the actual quantities of all associated active lots, with visual warning indicator (badge/icon) if stock is lower or equal to minimum stock (`min_stock`).

### 4. Displayed Data (Detailed Specs)
- [ ] **Laboratorio:** Associated manufacturer laboratory.
- [ ] **Registro Sanitario (INVIMA):** Full sanitary registration details (registration number, expiration date, manufacturing laboratory, status).

### 5. Description Section
- [ ] Dedicated section displaying the full description text (up to 500 characters, according to HU 06) preserving formatting.

### 6. Barcodes Section
- [ ] Displays the complete list of all barcodes associated with the medicine, highlighting the main barcode clearly.

### 7. Traceability and Audit Section
- [ ] **Creado por:** Name of the user who registered the medicine.
- [ ] **Fecha de Creación:** Timestamp of creation.
- [ ] **Última Modificación:** Name of the user and timestamp of the last update.
- [ ] **Borrado Suave (Soft Delete) Data:** If the medicine has been soft-deleted (archived), it must display:
  - **Fecha de Eliminación:** Timestamp of deletion.
  - **Usuario que Eliminó:** Name of the user who performed the deletion.

### 8. Read-Only Enforcement
- [ ] All information within the detailed view must be strictly read-only and prevent direct inline editing or form submission.

### 9. Quick Actions inside View
- [ ] **Editar (Edit):** A quick action button/link to redirect the user to the Edit Medicine form (HU 07) at `/medicines/{id}/edit` (disabled/hidden if the medicine is archived).
- [ ] **Archivar (Archive - Soft Delete):** A button to trigger the soft-deletion process (HU 07 / HU 15) which performs safety checks (validating that no active lots exist in inventory) before deleting (disabled/hidden if the medicine is already archived).

## Out of scope
- Inline editing of individual fields from within the detailed view modal/page.
- Direct inventory adjustments or lot registrations inside the detailed view.
