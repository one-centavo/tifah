# 018 · Advanced Filters for Medicines

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to filter the catalog list of medicines (`/medicines`) using multiple combined and cumulative criteria: Category, Laboratory, Cold Chain requirement, Special Control requirement, and Inventory stock status alerts (Low Stock / Out of Stock).

## Why

Warehouse managers and purchasing staff need to quickly segment the medicine catalog based on supply, regulatory, logistical, and commercial properties. This enables efficient purchasing planning, stock auditing, and compliance checking without manual calculation.

## Acceptance criteria

### 1. Advanced Filter Controls in UI
The medicines index dashboard (`/medicines`) must present the following filter controls:
- [ ] **Categoría (Category):** A dropdown control allowing selection of a specific therapeutic group (e.g., Analgésicos, Antibióticos) or "Todas" (default).
- [ ] **Laboratorio (Laboratory):** A dropdown control allowing selection of a registered manufacturer laboratory or "Todos" (default).
- [ ] **Requiere Cadena de Frío (Requires Cold Chain):** A toggle switch or selector with options:
  - "Sí" (Only show products requiring cold chain).
  - "No" (Only show products not requiring cold chain).
  - "Todos" (Do not filter by cold chain - default).
- [ ] **Control Especial (Special Control):** A toggle switch or selector with options:
  - "Sí" (Only show products marked for special control).
  - "No" (Only show products not marked for special control).
  - "Todos" (Do not filter by special control - default).
- [ ] **Alerta de Inventario (Inventory Alert):** A radio button group or select filter allowing options:
  - **Bajo Stock (Low Stock):** Shows only products whose actual stock is less than or equal to the minimum stock (`min_stock`) configured.
  - **Agotados (Out of Stock):** Shows only products whose actual stock is exactly zero.
  - **Todos (All):** Shows all stock statuses (default).

### 2. Cumulative Application of Filters
- [ ] The filters must be combinable and cumulative (logical `AND` logic). Applying a new filter must further refine the current query results.
- [ ] The global text search field (search by name, generic name, or barcode) must also combine with the advanced filters.

### 3. Accurate Stock calculations
- [ ] The inventory alert filters ("Bajo Stock" and "Agotados") must calculate actual stock dynamically as the sum of `current_quantity` of all associated active, non-deleted lots (excluding any soft-deleted lots or lots from soft-deleted purchase orders).

### 4. Empty State Feedback
- [ ] If the combination of active filters returns no results, the system must display a clear message: `"No se encontraron medicamentos que coincidan con los filtros aplicados"`.

## Out of scope
- Saving custom filter presets per user.
- Exporting the filtered list directly to PDF or Excel (handled in a separate reporting feature).
