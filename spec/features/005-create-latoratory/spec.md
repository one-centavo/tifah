# 005 · Create Laboratory & Laboratory Management

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to register and manage the catalog of medicine manufacturing laboratories. This includes registering new laboratories with a name and description, automatically auditing who created the records, listing laboratories, and soft-deleting them. It also prevents deleting a laboratory if there are active medicines associated with it.

## Why

To maintain an accurate catalog of medicine manufacturers, ensuring traceability and legal origin in the inventory, and preventing operational errors when registering new medicines.

## Acceptance criteria

### 1. Registration Form
- The system must display a simple form with two fields:
  - **Nombre del Laboratorio** (Laboratory Name) - Required, maximum 255 characters.
  - **Descripción** (Description) - Optional, maximum 255 characters for internal notes.

### 2. Validation
- The name of the laboratory is required and must be unique in the system.
- If a user attempts to register a laboratory with an existing name, the system must display the validation error message: `"Este laboratorio ya se encuentra registrado"`.
- If mandatory fields are empty or fail validation, highlight the fields with error. Prevent submission until errors are resolved.

### 3. Auditing and Traceability
- Upon successful creation, the system must automatically store:
  - The creator's user ID in the `created_by` field.
  - The current timestamp in the `created_at` field.

### 4. UI Feedback
- Display a clear confirmation message upon successful registration.
- Once registered, the laboratory should be immediately available for selection when registering new medicines.

### 5. Soft Deletion (Logical Deletion)
- The system must use soft deletes for removing a laboratory.
- When soft-deleted, the laboratory:
  - Must not be visible in active lists or forms.
  - Must remain in the database for auditing and historical records.
  - Must record the exact timestamp (`deleted_at`) and the user who deleted it (`deleted_by`).

### 6. Deletion Validation
- Prevent deletion of a laboratory if it has active products (medicines) associated with it in the medicine catalog.
- If associated active medicines exist, show an error and prevent deletion.

## Out of scope

- Restoring soft-deleted laboratories (to be addressed in a future feature).
- Bulk importing laboratory lists from spreadsheets.
