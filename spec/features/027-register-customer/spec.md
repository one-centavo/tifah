# 027 · Register Customer (HU 20)

**Status:** Draft

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to register and manage customers (pharmacies and institutional clients) in the system. This includes:
- Creating a customer with fields: NIT, Razón Social, Ciudad, Dirección de Entrega, Teléfono, Correo Electrónico, and Estado (Active/Inactive toggle).
- Validating NIT for uniqueness among active customer records and automatically computing the DIAN verification digit (DV) reactively.
- Displaying customers in a general list layout.
- Updating existing customer details.
- Logical deletion (soft delete) of customers, tracking the user who deleted them and when.
- Blocking deletion (even logical) if the customer has associated bills (facturas) in the historical database.

## Why

To streamline the billing process, organize shipping routes by city, maintain commercial control over customer states, and ensure transactional and regulatory integrity by preventing deletion of customers with past financial records.

## Acceptance criteria

### 1. Customer Registration Form
- The system must provide a form containing:
  - **NIT**: Tax identification number. Mandatorily unique among active customers. Formatted with dot separators (e.g., `900.123.456`).
  - **DV**: Verification digit, automatically calculated based on the NIT using DIAN's algorithm, visible but disabled/readonly on the form.
  - **Razón Social (Name)**: Required field, up to 255 characters.
  - **Ciudad (City)**: Required field, up to 100 characters. Used for logistical routing and regional reporting.
  - **Dirección de Entrega (Address)**: Required field, up to 255 characters to support detailed drop-off instructions.
  - **Teléfono (Phone)**: Required field, up to 20 characters.
  - **Correo Electrónico (Email)**: Required field, validated email format, up to 255 characters (used for electronic invoicing).
  - **Estado (State)**: A toggle switch or checkbox to mark the customer as Active or Inactive.

### 2. Uniqueness & Validation
- The NIT must be validated as unique among active customers (records where `deleted_at` is null). Soft-deleted customer NITs can be reused.
- Standard validations (required fields, maximum length, valid email format) must be enforced.

### 3. Auditing & Metadata
- **created_by**: Automatically captures and records the authenticated user ID on creation.
- **updated_by**: Automatically captures and records the authenticated user ID on updates.
- **created_at** / **updated_at**: Automatically tracked via Laravel model timestamps.

### 4. Logical Deletion & Audit Tracking
- When a customer is deleted, the system performs a soft delete.
- The `deleted_at` timestamp is set to the current date/time.
- The `deleted_by` column stores the ID of the user performing the deletion.

### 5. Referential Integrity Check
- The system must check if the customer has any associated bills (`bills` table records) in the database before deletion.
- If bills exist, the deletion action must be blocked, and an error message in Spanish must be shown: `"No se puede eliminar el cliente porque tiene facturas asociadas en el histórico."`

### 6. Listing & General View
- A general listing page (`/customers`) must show registered customers.
- By default, the general view displays active customers.
- The listing should support:
  - Searching by Razón Social or NIT.
  - Filtering by status (Active, Inactive, Archived/Deleted, All).
  - Sorting and pagination.
- Flash messages in Spanish must confirm successful actions:
  - Create: `"El cliente ha sido registrado con éxito."`
  - Update: `"El cliente ha sido actualizado con éxito."`
  - Delete: `"El cliente ha sido archivado con éxito."`
