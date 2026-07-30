# 006 · Edit Laboratory

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to modify the details of an existing laboratory previously registered in the system. The user can access the edit form from the Laboratory Management page (Index). In the edit form, the system loads the current details (Name and Description) of the laboratory, allows modifying them, validates constraints, and automatically records the auditor user ID (`updated_by`) and update timestamp (`updated_at`) while preserving the original creation records.

## Why

To keep the contact and catalog details of medicine manufacturers (suppliers) up to date, correct registration mistakes, and maintain strict traceability of who and when made the changes without altering the original registration audit data.

## Acceptance criteria

### 1. Navigation and Loading
- Authorized users (Administrators or Warehouse Assistants) can select a laboratory from the administrative index list (`/laboratories`) to edit it.
- Each active laboratory row in the table must display a link or button to edit.
- Clicking the edit option navigates to `/laboratories/{laboratory}/edit`.
- The form on the edit page must load the laboratory's current data: **Nombre del Laboratorio** (Name) and **Descripción** (Description).

### 2. Form Fields and Formatting
- The form must show:
  - **Nombre del Laboratorio** (Name) - Text input.
  - **Descripción** (Description) - Text area.
- The Name field is required (cannot be empty) and must be unique in the system among active (non-soft-deleted) laboratories.
- Name field validation must ignore the laboratory currently being edited.
- The Name field has a maximum length constraint of 255 characters.
- The Description field is optional and has a maximum length constraint of 255 characters.

### 3. Validation and UI Feedback
- If validation fails (e.g., name left empty, name exceeds 255 chars, description exceeds 255 chars, or the name is already taken by another active laboratory), the system must:
  - Prevent saving the updates.
  - Highlight the invalid field.
  - Display a clear validation error message next to the field.
- Validation error messages must be in Spanish:
  - Name required: `"El nombre del laboratorio es obligatorio."`
  - Name too long: `"El nombre del laboratorio no debe exceder los 255 caracteres."`
  - Name already exists: `"Este laboratorio ya se encuentra registrado."`
  - Description too long: `"La descripción no debe exceder los 255 caracteres."`

### 4. Auditing and Traceability
- Upon saving the changes successfully, the system must automatically log:
  - The ID of the authenticated user who performed the modification in `updated_by`.
  - The modification timestamp in `updated_at`.
- The original creator ID (`created_by`) and creation timestamp (`created_at`) must remain untouched.

### 5. Post-Save Behavior
- When the user submits valid edits and the save is successful:
  - The system must show a confirmation message in Spanish: `"El laboratorio ha sido actualizado con éxito."`
  - The system must redirect the user back to the laboratories management list (`/laboratories`).
  - The laboratory must remain active and available to be selected in other forms (like creating or updating medicines).

## Out of scope
- Restoring or editing soft-deleted laboratories from this form.
- Modifying historical logs from the UI.
