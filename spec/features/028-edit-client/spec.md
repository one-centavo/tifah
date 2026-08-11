# 028 · Edit Client (HU 21)

**Status:** Completed

## What it does

Allows authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role to modify the details of previously registered clients (customers, pharmacies, and institutional buyers). 

When a user selects a client from the administrative listing (`/customers`), the system navigates to the edit form pre-populated with the client's current data:
- **NIT (Número de Identificación Tributaria)**
- **Dígito de Verificación (DV)**
- **Razón Social (Legal Name)**
- **Ciudad (City)**
- **Dirección de Entrega (Delivery Address)**
- **Teléfono (Phone Number)**
- **Correo Electrónico (Email)**
- **Estado (Commercial Status: Active / Inactive)**

If the NIT is modified, the system reactively recalculates the Verification Digit (DV) using the DIAN Modulo 11 algorithm. The DV field remains read-only and disabled. Editing enforces strict validation rules: mandatory fields cannot be empty, email format must be valid, and the modified NIT must not collide with any other active client record in the database (excluding the client currently being updated).

Upon successful submission, the system logs the ID of the authenticated modifier user (`updated_by`) and updates timestamps while preserving the original creation metadata (`created_by` / `created_at`). A confirmation flash message is shown, the user is redirected back to the client list, and all updated information is immediately reflected across all dependent modules such as billing (facturación) and dispatch (despacho).

## Why

To maintain accurate, up-to-date commercial and contact information, delivery addresses, and status controls for all clients. Ensuring data accuracy avoids invoicing errors, delivery misplacements, and regulatory inconsistencies, while providing complete traceability of updates.

## Acceptance criteria

### 1. Access Control
- Only authenticated users with the **Administrator (Administrador)** or **Warehouse Assistant (Auxiliar de Bodega)** role can access the client edit page and perform modifications.
- Unauthenticated guest users attempting to access the edit route are redirected to the login view.

### 2. Client Selection & Form Pre-population
- The user can select an edit action on any client row from the administrative listing (`/customers`), redirecting to the edit view (`/customers/{customer}/edit`).
- The edit form is pre-loaded with all current values from the selected client record:
  - NIT
  - DV
  - Razón Social
  - Ciudad
  - Dirección de Entrega
  - Teléfono
  - Correo Electrónico
  - Estado (Active/Inactive toggle or switch)

### 3. Fields & Requirements
- **NIT**: Mandatory, editable. String/digits pattern formatted with dot separators (e.g., `900.123.456`). Maximum 20 characters.
- **DV (Dígito de Verificación)**: Read-only, disabled, and calculated automatically using DIAN Modulo 11. Cannot be edited manually.
- **Razón Social**: Mandatory, editable, maximum 255 characters.
- **Ciudad**: Mandatory, editable, maximum 100 characters.
- **Dirección de Entrega**: Mandatory, editable, maximum 255 characters.
- **Teléfono**: Mandatory, editable, maximum 20 characters.
- **Correo Electrónico**: Mandatory, editable, valid email format, maximum 255 characters.
- **Estado (Status)**: Toggle switch or checkbox allowing the commercial status to be set to Active (`is_active = true`) or Inactive (`is_active = false`).

### 4. Real-time Verification Digit (DV) Recalculation
- Modifying the NIT triggers a reactive calculation of the verification digit in real-time (both via AlpineJS on the frontend and Livewire lifecycle hooks on the backend).
- If the NIT input is cleared, the DV field is automatically cleared.

### 5. Validations & Error Handling
- **NIT Uniqueness**: Validates that the modified NIT does not belong to any other active client (`deleted_at IS NULL`), ignoring the current client record being edited. If a conflict occurs, the system shows: `"Este NIT ya se encuentra registrado."`
- **Mandatory Fields & Format Constraints**: Prevents saving if required fields are missing or if formats (such as invalid email or excessive string length) are violated.
- **UI Error Highlighting**: Fields with validation errors must be visibly highlighted, and localized Spanish error messages must be displayed directly beneath each invalid input.

### 6. Audit Trail Logging
- Upon a successful update, the system automatically records the authenticated user's ID in `updated_by` and updates the `updated_at` timestamp.
- The original creation metadata (`created_by` and `created_at`) remains strictly intact and unchanged.

### 7. Feedback & Redirection
- On successful update, the system displays a confirmation flash message in Spanish: `"El cliente ha sido actualizado con éxito."`
- The user is redirected back to the client administrative list (`/customers`).

### 8. Immediate Consistency & Cross-Module Availability
- The updated client information (legal name, address, tax ID, active/inactive state) is immediately updated and available across all modules where client selection is required, such as billing/invoicing (facturación) and dispatch logistics (despacho).

## Out of scope
- Visual change log / audit history viewer in the client profile.
- Direct invoice creation from the client edit screen.
