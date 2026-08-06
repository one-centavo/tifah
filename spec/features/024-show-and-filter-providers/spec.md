# 024 · Show and Filter Providers (HU 17)

**Status:** Completed

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to visualize the complete list of registered suppliers (providers) in a tabular format. Users can perform global search, filter suppliers by their status (active/archived) using soft deletion, sort records by Razón Social, and access management actions such as editing or archiving.

## Why

To quickly manage contacts, check supplier commercial status, and ensure data integrity. Users need an efficient search and filter system to retrieve provider records during day-to-day warehouse and purchasing operations without loading archived suppliers by default.

## Acceptance criteria

### 1. Access Control
- Only authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role can view the suppliers list.
- Guest users attempting to access the page are redirected to the login screen.

### 2. Table Visualization
The list must be displayed in a table detailing:
- **NIT** (including the calculated verification digit DV, e.g., `900.123.456-8`).
- **Razón Social** (Supplier name).
- **Teléfono** (Contact phone number).
- **Correo Electrónico** (Contact email).
- **Estado** (Activo / Archivado badge based on soft delete).

### 3. Search and Filtering
- **Global Search**: A text field allowing partial search matches against **Razón Social** or **NIT**.
- **Status Filter**: A control selection dropdown to filter by state:
  - **Activos** (Default): Loads only active (non-deleted) suppliers.
  - **Archivados**: Loads only soft-deleted suppliers.
  - **Todos**: Loads both active and archived suppliers.
- **Empty State**: If active filters yield no results, the system must show a clear message: `"No se encontraron proveedores que coincidan con los filtros aplicados"`.

### 4. Sorting
- Users must be able to sort the supplier list by **Razón Social** in ascending or descending order.

### 5. Action Buttons
For each supplier record, the list must provide the following actions:
- **Editar**: A link redirecting the user to the provider edit form.
- **Archivar (Soft Delete)**: A button to trigger logical deletion, validating beforehand that the supplier does not have any active merchandise lots (quantity > 0) currently in inventory.

## Out of scope
- Advanced export options (CSV/PDF) for supplier records.
- Permanently deleting supplier records from the database.
