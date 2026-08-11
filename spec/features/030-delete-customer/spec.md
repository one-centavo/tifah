# 030 · Delete Customer (HU 23)

**Status:** Completed

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to archive an existing customer (pharmacy, point of sale, or institutional client) from the customer management dashboard (`/customers`). 

This is performed via a soft delete mechanism which transitions the customer status to "Archivado", preserves their historical records for audit compliance and reporting, but excludes them from active operations and forms. Before archiving, the system enforces a business constraint preventing the deletion if the customer has any associated sales invoices (`bills`) in history to protect sales data integrity.

## Why

To maintain a clean and up-to-date customer directory while preserving historical transaction and billing traceability. Preventing the logical deletion of customers with linked invoices ensures relational integrity and accounting consistency for regulatory and auditing requirements.

## Acceptance criteria

### 1. Initiation from Customer List (HU 22)
- Authorized users (Administrators or Warehouse Assistants) can initiate the archiving (soft delete) operation directly from the customer listing table (`/customers`).
- An "Archivar" (Archive) action button is available in the row of each active customer.

### 2. Integrity Check (Associated Invoices Validation)
- Before executing the soft delete, the system must validate that there are no invoices/bills (`bills`) associated with the selected customer.
- If linked invoices exist, the system blocks the action and displays a clear error message to the user: `"No se puede eliminar el cliente porque tiene facturas asociadas en el histórico."`

### 3. Pre-Deletion Confirmation
- The system prevents accidental deletions by prompting the user with a confirmation modal/dialog before performing the operation.
- The confirmation modal displays the customer's legal name (Razón Social) and prompts the user to confirm the archive action, clarifying that the record will be archived while historical logs are preserved.

### 4. Automatic State Update and Auditing
- Upon confirmation and validation success:
  - The customer's status automatically transitions to "Archivado" (displayed as an archived status badge in the list).
  - Automatically captures the authenticated user's ID and saves it in the `deleted_by` column.
  - Automatically stores the exact timestamp of deletion in the `deleted_at` column.

### 5. Exclusion from Operations
- Once a customer is archived:
  - They are excluded from active list views by default (unless the "Archivados" or "Todos" status filter is selected).
  - They do not appear in active operational forms or select dropdowns (such as billing or dispatch forms).
  - They remain in the database for historical and reporting purposes.

### 6. Success Feedback
- A successful archive action closes the modal, refreshes the customer list reactively, and displays a confirmation success message in Spanish: `"El cliente ha sido archivado con éxito."`.

## Out of scope
- Permanent database deletion (hard delete) of customer records.
- Bulk archiving of multiple customers simultaneously.
- Restoring/unarchiving customer records directly from this interface.
