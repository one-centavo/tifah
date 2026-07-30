# 011 · Show and Filter Sanitary Registries

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to view the complete list of registered sanitary registries (INVIMA) in a table and dynamically search/filter them. The user can search by the registration number (global text search), filter by manufacturer laboratory, filter by expiration date range, filter by registry status, and filter by catalog visibility. If no records match the applied filters, a clear message is displayed. Each record includes quick action buttons to Edit or Delete.

## Why

To allow staff to monitor the validity and legal status of products, correct mistakes, track expiration schedules, and ensure compliance with healthcare guidelines by easily filtering out expired or soon-to-expire sanitary registries.

## Acceptance criteria

### 1. View and Table Columns
- The page must be accessible at `/sanitary-registries`.
- It must present a table showing all sanitary registries with at least:
  - **Número de Registro** (Registration Number)
  - **Laboratorio Fabricante** (Manufacturer Laboratory)
  - **Fecha de Vencimiento** (Expiration Date)
  - **Estado** (Vigente / Vencido / En renovación)
  - **Visibilidad en catálogo** (Sí / No) - representing whether the registry is active or logically deleted/archived.

### 2. Search and Filtering Controls
The index page must include the following controls:
- **Global Search**: A text field that filters results in real-time or via debounce by matching the **Número de Registro Sanitario** (case-insensitive, partial matching).
- **Laboratory Filter**: A select dropdown allowing the user to select a specific laboratory to filter the registries.
- **Expiration Date Range Filter**: Two date input fields (Start Date and End Date) to filter registries whose **Fecha de Vencimiento** falls within that range.
- **Status Filter**: A select dropdown to filter registries by their status:
  - **Vigente** (`valid`)
  - **Vencido** (`expired`)
  - **En renovación** (`under_renewal`)
- **Catalog Visibility Filter**: A control (select dropdown or similar) to filter by visibility in the catalog:
  - **Sí** (Active / Not soft-deleted)
  - **No** (Archived / Soft-deleted)
  - **Todos** (Show both active and archived)

### 3. Empty State Message
- If the user applies filters that return no results, the table must be replaced by or display a clear message in Spanish:
  `"No se encontraron registros sanitarios que coincidan con los filtros aplicados"`

### 4. Actions
Each row must include action buttons:
- **Editar**: Redirige al formulario de edición (`/sanitary-registries/{sanitary_registry}/edit`).
- **Eliminar**: Inicia la eliminación lógica del registro (soft delete), validando previamente que no existan medicamentos activos asociados. If active medicines are associated, it must show an error: `"No se puede eliminar el registro sanitario porque tiene medicamentos activos asociados."`

## Out of scope
- Permanent deletion (hard delete) of sanitary registries.
- Restoring archived registries from this specific view (handled in HU 21/other management actions if needed).
