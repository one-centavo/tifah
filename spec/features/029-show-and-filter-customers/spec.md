# 029 · Show and Filter Customers (HU 22)

**Status:** Completed


## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to visualize the complete list of registered customers (pharmacies, points of sale, and institutional clients) in a tabular format. 

Users can perform global searches across legal names (Razón Social) and tax IDs (NIT), filter records by City (Ciudad) and lifecycle status (Active / Archived via soft delete), sort the list by Razón Social (ascending/descending), and trigger management actions directly from each table row (editing customer information or initiating logical deletion/archiving).

## Why

To provide a centralized, responsive, and efficient dashboard for managing customer contact information and commercial statuses. Fast searching and granular filtering by city and status streamline customer lookups, support logistics and distribution route planning, and prevent operational clutter by displaying only active customers by default while keeping archived records accessible for audit purposes.

## Acceptance criteria

### 1. Access Control
- Only authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role can access the customer listing view (`/customers`).
- Unauthenticated guest users attempting to access the page are redirected to the login screen.

### 2. Table Visualization
The customer list must be rendered in a responsive, structured table displaying at least:
- **Razón Social**: Legal name of the customer.
- **NIT**: Tax identification number (including the calculated verification digit DV, e.g., `900.123.456-8`).
- **Ciudad**: Customer's operating city (used for geographical grouping).
- **Teléfono**: Primary contact phone number.
- **Estado**: Lifecycle status badge based on soft deletion (**Activo** / **Archivado**).

### 3. Global Search & Partial Matching
- A global text search input must be provided to filter customers by:
  - **Razón Social** (partial match / case-insensitive).
  - **NIT** (partial match across digits and formatting).
- Search input must update results reactively with debounce (e.g., `300ms`).

### 4. City Filter
- A select dropdown filter must allow filtering customers by **Ciudad**.
- The dropdown options must dynamically include all unique cities available from existing customer records or provide an "All Cities" (Todas las ciudades) default option.

### 5. Status Filter (Soft Delete)
- A select or toggle control must allow filtering by soft deletion status:
  - **Activos** (Default): Displays only active records (`deleted_at IS NULL`).
  - **Archivados**: Displays only soft-deleted records (`deleted_at IS NOT NULL`).
  - **Todos**: Displays both active and archived records.
- By default, upon initial page load, the table must display **only active** customer records.

### 6. Empty State Handling
- If any combination of search terms, city filter, or status filter produces no results, the table must display a clear and helpful empty state message:
  `"No se encontraron clientes que coincidan con los filtros aplicados"`

### 7. Sorting
- The table must support column sorting by **Razón Social** in both ascending (`asc`) and descending (`desc`) order.
- Sort order toggle indicator (e.g., chevron icons) must clearly show the active sort direction.

### 8. Action Buttons
Each table row must provide direct action buttons/links:
- **Editar**: Navigates to the customer edit form (`/customers/{customer}/edit`, HU 21).
- **Archivar (Borrado Suave)**: Triggers logical deletion of the customer record, opening a confirmation modal and enforcing business rules (e.g., verifying absence of linked historical invoices before archiving).

## Out of scope
- Batch/bulk operations (bulk archive or bulk export).
- Permanent database deletion (hard delete) of customer records.
- In-place inline editing within table cells.
