# 025 · Edit Provider (HU 18)

**Status:** Completed

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to modify the details of previously registered suppliers (providers). The edit form is pre-filled with the supplier's current data: NIT (Número de Identificación Tributaria), Dígito de Verificación (DV), Razón Social (Legal Name), Nombre de la Persona de Contacto (Contact Person Name), Teléfono (Phone Number), Correo Electrónico (Email), and Dirección Física (Physical Address).

If the user modifies the NIT, the system reactively recalculates the Verification Digit (DV) using the DIAN Modulo 11 algorithm. The DV itself is read-only. Editing is validated to ensure required fields are not empty, the email format is correct, and the NIT does not conflict with another active supplier in the system. Upon successful submission, modifications are persisted along with audit trails tracking which user performed the update while preserving the original creator's information, and the user is redirected to the supplier list with a success message.

## Why

To keep the contact and commercial information of active suppliers up-to-date and rectify potential registration errors. Maintaining exact supplier data is essential to ensure the compliance, legal traceability, and consistency of lot records and purchase orders across the entire application modules.

## Acceptance criteria

### 1. Access Control
- Only authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role can access the provider edit page and perform updates.
- Unauthenticated guest users are redirected to the login screen.

### 2. Selection and Form Loading
- The administrator selects a supplier from the provider listing (`spec/features/024-show-and-filter-providers/spec.md`) and is redirected to the edit form.
- The form is pre-populated with the current values of the selected provider.

### 3. Fields & Formatting
The form contains the following inputs:
- **NIT (Número de Identificación Tributaria)**: Pre-loaded, editable, mandatory. Must match standard digits pattern (dots formatting optional, e.g., `900.123.456`).
- **Dígito de Verificación (DV)**: Automatically recalculated when the NIT field changes using the DIAN Modulo 11 algorithm. Read-only, disabled, and cannot be modified manually.
- **Razón Social**: Pre-loaded, editable, mandatory. Maximum 150 characters.
- **Nombre de la Persona de Contacto**: Pre-loaded, editable, optional. Maximum 100 characters.
- **Teléfono**: Pre-loaded, editable, mandatory. Maximum 20 characters.
- **Correo Electrónico**: Pre-loaded, editable, mandatory. Must be a valid email format, maximum 255 characters.
- **Dirección Física**: Pre-loaded, editable, mandatory. Maximum 200 characters.

### 4. Real-time Verification Digit Calculation
- Changing the NIT triggers the calculation of the DV dynamically on the frontend/backend.
- If the NIT field is cleared, the DV must be cleared immediately.

### 5. Validations & Error Handling
- **NIT Uniqueness**: Validates that the modified NIT does not match any other active (non-soft-deleted) supplier. Uniqueness check must exclude the current record being updated. If a conflict occurs, show: `"Este NIT ya se encuentra registrado"`.
- **Formatting & Empty Constraints**: The system must fail validation and block saving if any mandatory field is empty, if the email format is incorrect, or if character limits are breached.
- **UI Error Highlighting**: Input fields with validation errors must be styled/highlighted, with error feedback messages shown directly beneath the field.

### 6. Audit Trail Logging
- On successful update, the system automatically records the authenticated user's ID who performed the edit under `updated_by` along with the update timestamp.
- The original creator ID (`created_by`) and creation timestamp (`created_at`) must remain untouched.

### 7. Feedback and Redirection
- When the user successfully saves, the system must flash a confirmation message in Spanish: `"El proveedor ha sido actualizado con éxito."` and redirect the user back to the suppliers listing page.

### 8. Immediate Consistency
- The edited supplier details must immediately reflect in all active dependent modules of the system (such as purchasing orders or merchandise incoming logs).

## Out of scope
- Historical change log visualization for supplier profiles.
