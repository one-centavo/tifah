# 008 · Create Sanitary Register & Sanitary Register Management

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to register and manage the catalog of Sanitary Registries (INVIMA). This ensures all medicines comply with health regulations and facilitates tracking of expirations. The feature covers form registration, automatic data normalization, audit logging, soft deletes with association constraints, status-based selection restrictions in medicine registration, and quick laboratory registration from within the form.

## Why

To comply with health regulations, ensure only valid sanitary registries are assigned to active medicines, prevent expired products from being sold or used, and streamline data entry when registering new manufacturer laboratories on the fly.

## Acceptance criteria

### 1. Registration Form
- The system must display a registration form with the following fields:
  - **Número de Registro Sanitario** (Sanitary Register Number / `registration_number`): Required, unique, maximum 50 characters.
  - **Laboratorio Fabricante** (Manufacturer Laboratory / `laboratory_id`): Required.
  - **Fecha de Vencimiento** (Expiration Date / `expiration_date`): Required.
  - **Estado** (Status / `status`): Required, default "Vigente" (`valid`).
  - **Descripción** (Description / `description`): Optional, rich/long text.

### 2. Validation & Normalization
- **Sanitary Register Number (`registration_number`)**:
  - Must be unique. If it already exists, display: `"Este número de registro sanitario ya se encuentra registrado"`.
  - Must strictly match the official format via a regular expression (e.g., `INVIMA 2026M-1234567`). The format expects `INVIMA` (case-insensitive) followed by spaces, a 4-digit year, a letter (e.g., `M`), a hyphen, and a 7-digit number.
  - Must be automatically normalized to uppercase before saving (e.g., inputting `invima 2026m-1234567` saves as `INVIMA 2026M-1234567`).
- **Laboratory (`laboratory_id`)**:
  - Must allow searching and selecting from the list of previously registered laboratories.
- **Expiration Date (`expiration_date`)**:
  - When creating a new record, the date must be in the future (greater than the current date).
- **Status (`status`)**:
  - Defaults to `'valid'` (Vigente). The user can change it to `'expired'` (Vencido) or `'under_renewal'` (En renovación).
- **Form submission**:
  - Prevent saving if validation fails. Highlight the fields with errors.

### 3. Auditing and Traceability
- Upon successful creation, the system must automatically store:
  - The creator's user ID in the `created_by` field.
  - The current timestamp in the `created_at` field.

### 4. UI Feedback
- Display a clear confirmation message upon successful registration.
- Once registered, the sanitary registry must be immediately available for selection when registering new medicines.

### 5. Medicine Selection Constraints
- When registering/editing a medicine, the system must only permit selecting Sanitary Registries whose status is "Vigente" (`valid`).
- If a medicine has an expired sanitary registry (or it is in a state other than valid/Vigente), the system must display a visual warning in the user interface.

### 6. Soft Deletion (Logical Deletion)
- Implement soft deletes for sanitary registries (`deleted_at` timestamp).
- When a registry is soft-deleted:
  - It must no longer appear in active lists or selection dropdowns.
  - It remains in the database for auditing/historical records, recording `deleted_at` and `deleted_by` (the ID of the user who deleted it).
- **Deletion Constraint**: Prevent soft deletion of a sanitary registry if it is associated with active products (medicines) in the catalog. If associated active medicines exist, block deletion and display an error.

### 7. Quick Laboratory Registration
- If a manufacturer laboratory is not found in the database, the system must allow registering it quickly via a modal form without leaving the Sanitary Registry flow.
- A link or button like `"+ Registrar Laboratorio"` should be available within or next to the search dropdown.
- This opens a modal with:
  - **Nombre del Laboratorio** (prefilled with the current search query, required, unique, maximum 255 characters).
  - **Descripción** (optional, maximum 255 characters).
- Upon successful submission, create the laboratory, automatically select it in the form, close the modal, and show a success confirmation.

## Out of scope
- Bulk importing sanitary registry lists from external files.
- Advanced export functions (Excel/PDF) from the list view.
