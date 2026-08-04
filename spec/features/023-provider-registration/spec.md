# 023 · Provider Registration (HU 16)

**Status:** Draft

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to register and manage the contact and legal information of wholesale distributors and laboratories (suppliers/providers). The system provides a unified interface to list, register, edit, and soft-delete suppliers. 

The registration form dynamically calculates the DIAN verification digit (DV) as the user enters the NIT. Soft deletes are used to retire suppliers from active operations while maintaining historical audit records. The system blocks deletion of suppliers that have active merchandise lots in stock.

## Why

To formalize the entry of merchandise into the warehouse, ensuring the legal traceability of each received lot. Maintaining an accurate, audited supplier database is critical for compliance, ordering, and inventory audit trails.

## Acceptance criteria

### 1. Access Control
- Only authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role can access the provider registration page and manage providers.
- Guest users attempting to access these routes are redirected to the login screen.

### 2. Registration Form Fields & Validations
The system must render a form with the following inputs:
- **NIT (Número de Identificación Tributaria)**:
  - Mandatory field.
  - Must match a number format with dots (e.g., `900.123.456`).
  - Must be unique in the system. If it already exists among active (non-deleted) suppliers, show validation error: `"Este NIT ya se encuentra registrado"`.
- **Dígito de Verificación (DV)**:
  - Automatically calculated in real-time as the user types the NIT using the DIAN Modulo 11 algorithm.
  - The calculated value must be visible to the user but read-only (disabled/non-editable).
  - Only single-digit values (from 0 to 9) are acceptable.
  - If the NIT field is modified or cleared, the DV must be updated or cleared immediately to maintain consistency.
- **Razón Social (Business/Legal Name)**:
  - Mandatory field.
  - Maximum of 150 characters.
- **Nombre de la Persona de Contacto (Contact Person)**:
  - Optional field.
  - Maximum of 100 characters.
- **Teléfono (Phone Number)**:
  - Mandatory field.
  - Must be a valid phone number (fijo or celular), maximum 20 characters.
- **Correo Electrónico (Email)**:
  - Mandatory field.
  - Must be a valid email format (e.g., `ejemplo@proveedor.com`), maximum 255 characters.
- **Dirección Física (Physical Address)**:
  - Mandatory field.
  - Maximum of 200 characters.

### 3. Error Handling and Feedback
- The system must prevent saving the supplier if there are any validation errors.
- Input fields with errors must be highlighted with proper CSS styles, showing the validation message underneath.
- Upon successful registration, the system must save the information and display a clear confirmation message in Spanish (Colombia).

### 4. Editing Supplier Information
- The system must allow users to edit existing supplier details to keep contact info up-to-date.
- Editing validation rules must mirror the registration rules (e.g., NIT uniqueness must ignore the supplier record currently being edited).
- When a supplier is updated, the system must record which user performed the edit (`updated_by`), while preserving the original creator's user ID (`created_by`).

### 5. Soft Deletion & Audit Trail
- The system must use Soft Deletes (logical deletion) when "deleting" a supplier. The record must remain in the database but be hidden from active lists and search filters.
- Upon deletion, the system must automatically record:
  - The date and time of the deletion (`deleted_at`).
  - The ID of the authenticated user who executed the deletion (`deleted_by`).

### 6. Deletion Restrictions
- The system must block the deletion of a supplier if that supplier is associated with any active merchandise lots currently in inventory.
- If active lots exist, the system must show a validation error message preventing the action.

## Out of scope
- Advanced supplier analytics or purchase order historical reporting.
- Restoring soft-deleted suppliers (to be addressed in a future feature).
