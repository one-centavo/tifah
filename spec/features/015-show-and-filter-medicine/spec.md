# 015 · Show and Filter Medicine

**Status:** Draft

## What it does

Provides a comprehensive, filterable, and searchable dashboard of all registered medicines (`/medicines` / `route('medicines.index')`). It allows authorized users (Administrators or Warehouse Assistants) to check current stock levels, view alerts, search globally by various fields, inspect detailed technical specifications, edit product entries, and safely archive medicines (soft-deletion) only if they have no active lots in the inventory.

## Why

Warehouse staff and sales agents need to quickly find medicines, verify their stock availability, check if they are near or below the minimum required quantity (stock alert), and inspect invima sanitary registries and other technical specifications, while ensuring that active stock is protected from accidental deletion.

## Acceptance criteria

### 1. Access Control
- [ ] Authorized users (Administrators or Warehouse Assistants) can access the medicines dashboard at `/medicines`.
- [ ] Guest or unauthorized users attempting to access the page are redirected to the login page.

### 2. Medicine Catalog Table
The table must list all registered medicines, with the following fields:
- [ ] **Nombre Comercial:** Commercial name of the medicine.
- [ ] **Nombre Genérico:** Generic name/active ingredient of the medicine.
- [ ] **Concentración:** Technical concentration formatted as a concatenated string (e.g., `500 mg`, `10 ml`).
- [ ] **Presentación:** Logistical description formatted as a concatenated string (e.g., `Caja x 30 Tabletas`, `Frasco x 100 ml`).
- [ ] **Categoría:** Associated category.
- [ ] **Precio de Venta:** Formatted unit selling price (e.g., `$15,200.00`).
- [ ] **Stock Actual / Alerta:**
  - Displays the sum of `current_quantity` of all associated active lots.
  - Compares the total actual stock against the configured minimum stock (`min_stock`).
  - Displays a visual alert indicator (e.g., color warning badge or alert icon) if the actual stock is equal to or lower than the minimum stock.

### 3. Global Search and Filters
- [ ] **Global Search Field:** A search input that filters products by **Nombre Comercial**, **Nombre Genérico**, or **Código de Barras** (barcodes).
  - Must support partial, case-insensitive searches.
- [ ] **Category Filter:** A dropdown filter to view medicines belonging to a specific category.
- [ ] **Status (Visibility) Filter:** A dropdown filter to view:
  - *Activos (Active):* Show active medicines (default on load).
  - *Archivados (Archived):* Show soft-deleted medicines.
  - *Todos (All):* Show both active and archived medicines.
- [ ] **Sorting:** Allow sorting the table by columns like Commercial Name, Category, and others.

### 4. Row Action Buttons
Each medicine row must include the following quick actions:
- [ ] **Ver Detalle (View Detail):** Opens a modal or section displaying all technical details of the selected medicine:
  - Full Commercial and Generic Names.
  - Technical Specifications (Concentration, Presentation, Laboratory, Sanitary Registry INVIMA).
  - Flags (Cold Chain, Special Control).
  - List of all associated Barcodes (with the main barcode highlighted).
  - Pricing and Stock details (Selling Price, Minimum Stock, Total Stock).
  - Auditing info (Created by/date, Updated by/date).
- [ ] **Editar (Edit):** Redirects the user to the Edit Medicine form at `/medicines/{id}/edit` with all data pre-populated (HU 07).
- [ ] **Archivar (Archive - Soft Delete):**
  - Triggers a deletion confirmation modal.
  - **Validation:** Before allowing the deletion, the system must check that no active lots exist in inventory associated with this product (lots with `status = 'active'` and `current_quantity > 0`).
  - If active lots are found, the deletion must be blocked, and an error message must be shown to the user.
  - If no active lots exist, the medicine and its associated barcodes are soft-deleted.

## Out of scope
- Bulk actions (e.g., archiving multiple medicines in one click).
- Creating new lots or adjusting inventory directly from this list view.
