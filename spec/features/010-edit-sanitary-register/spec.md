# 010 · Edit Sanitary Register & Sanitary Register Management

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to view and modify the details of an existing sanitary registry (INVIMA) previously registered in the system. The user can access the edit form from the Sanitary Registries Management page (Index). In the edit form, the system loads the current details (Registration Number, Manufacturer Laboratory, Expiration Date, Status, and Description) of the registry, allows modifying them, validates constraints, and automatically records the auditor user ID (`updated_by`) and update timestamp (`updated_at`) while preserving the original creation records.

## Why

To keep the sanitary registries catalog up to date, correct registration mistakes, adjust internal notes, and maintain strict traceability of who and when made the changes without altering the original registration audit data, complying with health regulations.

## Acceptance criteria

### 1. Navigation and Loading
- Authorized users (Administrators or Warehouse Assistants) can select a sanitary registry from the administrative index list (`/sanitary-registries`) to edit it.
- Each active sanitary registry row in the table must display a link or button to edit.
- Clicking the edit option navigates to `/sanitary-registries/{sanitary_registry}/edit`.
- The form on the edit page must load the sanitary registry's current data: **Número de Registro Sanitario** (Registration Number), **Laboratorio Fabricante** (Manufacturer Laboratory), **Fecha de Vencimiento** (Expiration Date), **Estado** (Status), and **Descripción** (Description).

### 2. Form Fields and Formatting
- The form must show:
  - **Número de Registro Sanitario** (`registration_number`) - Text input.
  - **Laboratorio Fabricante** (`laboratory_id`) - Searchable select dropdown.
  - **Fecha de Vencimiento** (`expiration_date`) - Date input.
  - **Estado** (`status`) - Select dropdown (Vigente / `valid`, Vencido / `expired`, En renovación / `under_renewal`).
  - **Descripción** (`description`) - Text area.
- The **Número de Registro Sanitario** is required (cannot be empty) and must be unique in the system.
- Uniqueness validation must ignore the sanitary registry currently being edited.
- The registration number must strictly match the official format via a regular expression (e.g., `INVIMA 2026M-1234567`). The format expects `INVIMA` (case-insensitive) followed by spaces, a 4-digit year, a letter, a hyphen, and a 7-digit number.
- Registration number must be automatically normalized to uppercase before saving (e.g., inputting `invima 2026m-1234567` saves as `INVIMA 2026M-1234567`).
- **Fecha de Vencimiento** is required and must not be earlier than the current date (i.e. must be today or in the future).

### 3. Validation and UI Feedback
- If validation fails (e.g., empty fields, invalid format, duplicate number, or expiration date in the past), the system must:
  - Prevent saving the updates.
  - Highlight the invalid field.
  - Display a clear validation error message next to the field.
- Validation error messages must be in Spanish:
  - Registration number required: `"El número de registro sanitario es obligatorio."`
  - Registration number format invalid: `"El formato del registro sanitario es inválido. Debe seguir el formato oficial (ej. INVIMA 2026M-1234567)."`
  - Registration number already exists: `"Este número de registro sanitario ya se encuentra registrado."`
  - Laboratory required: `"El laboratorio fabricante es obligatorio."`
  - Expiration date required: `"La fecha de vencimiento es obligatoria."`
  - Expiration date in the past: `"La fecha de vencimiento no puede ser anterior a la fecha actual."`
  - Status required: `"El estado es obligatorio."`

### 4. Auditing and Traceability
- Upon saving the changes successfully, the system must automatically log:
  - The ID of the authenticated user who performed the modification in `updated_by`.
  - The modification timestamp in `updated_at`.
- The original creator ID (`created_by`) and creation timestamp (`created_at`) must remain untouched.

### 5. Post-Save Behavior
- When the user submits valid edits and the save is successful:
  - The system must show a confirmation message in Spanish: `"El registro sanitario ha sido actualizado con éxito."`
  - The system must redirect the user back to the sanitary registries management list (`/sanitary-registries`).

## Out of scope
- Restoring or editing soft-deleted registries from this form.
- Modifying historical logs from the UI.
